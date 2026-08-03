## Context

`fabrique/fabrique/main.cpp` dispatches to `WaferCmd::parse`/`execute`.
`WaferCmd::execute()` currently prints a plain-text summary directly to
`std::cout`. The device shape `fabrique` needs to emit is defined by
`device.schema.json` (currently untracked at the repo root) — an object with
`width`, `height` (both required, always nanometers) and an optional
`materials` array of `{ name, boundary }`, where a material's `boundary` may
describe more than one non-intersecting polygon. `WaferCmd` today only
tracks `material`, `width`, and `miller` — it has no `height` or boundary
data, and its CLI syntax has no `height` token. See proposal.md - Why.

## Goals / Non-Goals

**Goals:**
- Introduce a `Device` value type in `libfabrique` that mirrors
  `device.schema.json` and can be serialized to JSON.
- Extend `WaferCmd`'s CLI syntax to accept an explicit `height` argument
  alongside the existing `width` argument.
- Fix nanometers as fabrique's system unit for `Device.width`/`Device.height`
  (no `units` field on `Device`). Accept an optional unit suffix on
  `width`/`height` CLI tokens and convert to nanometers at parse time; a
  bare number with no suffix is assumed to already be nanometers.
- Model a material's boundary as one or more non-intersecting boundary
  polygons, so a single material can occupy disjoint regions of the device.
- Make `WaferCmd::execute()` build a `Device` and print its JSON
  serialization to stdout instead of the current plain-text line.
- Track `device.schema.json` in the repo (under `fabrique/`) as the source
  of truth for the output shape.

**Non-Goals:**
- Full JSON Schema validation at runtime (e.g. pulling in a schema
  validator library) — conformance is ensured by construction and covered
  by tests, not runtime validation.
- Changing `wafer`'s `material`/`miller` argument semantics or the
  `<h,k,l>` Miller index syntax (only `width`/`height` parsing changes, as
  described in Goals).
- Adding JSON output for commands other than `wafer` (there are none yet).

## Decisions

- **JSON library**: use `nlohmann/json` (header-only, widely used, easy
  Conan integration via `self.requires("nlohmann_json/3.11.3")`) rather than
  hand-rolling JSON serialization. Alternative considered: hand-written
  string building (rejected — error-prone escaping, no structured tests) and
  `rapidjson` (rejected — more verbose API for this small use case).
- **`Device` type placement**: add `fabrique/fabrique/libfabrique/Device.hpp`
  / `.cpp` as a plain data type with a `to_json(nlohmann::json&, const
  Device&)` free function (ADL-friendly, matches nlohmann/json conventions),
  rather than embedding serialization logic inside `WaferCmd`. This lets
  future commands reuse `Device` and its JSON output.
- **Width/height parsing and units**: `WaferCmd` gains a new `height` CLI
  argument, positioned after `width` (e.g. `wafer Si 500nm 300nm <1,1,1>`),
  so both dimensions are supplied explicitly rather than `height` being
  derived from `width`. `Device.width`/`Device.height` are always in
  nanometers and there is no `units` member on `Device` — nanometers is
  fabrique's fixed system unit, not a per-device value. `WaferCmd` gains a
  small parse step that splits an optional trailing unit suffix from each
  `width`/`height` token (e.g. `500nm`, `0.5um`) and converts the value to
  nanometers before building the `Device`; a token with no unit suffix
  (e.g. plain `500`) is assumed to already be in nanometers. `width` and
  `height` may use different unit suffixes independently since each is
  converted to nanometers on its own.
- **`boundary`**: not a command-line input. Executing a command (e.g.
  `wafer`) updates the `Device`'s `materials` entry for the affected
  material with the boundary polygon(s) that command produces, rather than
  the user supplying boundary coordinates directly. A single material's
  `boundary` is modeled as an array of polygons (each an array of numbers),
  not a single flat coordinate array, since one material may occupy more
  than one non-intersecting region of the device (e.g. `wafer` run twice
  for the same material at different locations). `device.schema.json` is
  updated to reflect `boundary` as an array of polygons.
- **Error path**: parse failures (e.g. bad Miller indices, already thrown as
  `std::invalid_argument`) propagate out of `WaferCmd::parse` uncaught;
  `main.cpp` catches `std::exception`, prints the message to stderr, and
  returns a non-zero exit code, so no JSON is emitted on failure.

## Risks / Trade-offs

- [New Conan dependency (`nlohmann_json`) increases build footprint] →
  header-only library with a stable, permissive-licensed release; low
  ongoing maintenance cost.
- [BREAKING change to `wafer`'s CLI syntax (new required `height` argument)
  and stdout format] → called out explicitly in proposal.md; no released
  consumers depend on the current text format or argument order yet
  (command was only just introduced).
- [Dropping `units` from `Device`/`device.schema.json` and fixing nanometers
  as the system unit is a departure from the original schema shape] →
  documented explicitly in proposal.md/spec.md; simplifies `Device` and
  removes an entire class of unit-mismatch bugs, at the cost of losing the
  ability to report output in a caller-chosen unit (callers needing another
  unit convert from nanometers themselves).

## Migration Plan

- Add the `nlohmann_json` Conan requirement and rebuild.
- Add `Device.{hpp,cpp}`, update `WaferCmd::execute()`, update `main.cpp`
  error handling, add `device.schema.json` under `fabrique/`.
- No data migration needed; this is a CLI output format change with no
  persisted state.
