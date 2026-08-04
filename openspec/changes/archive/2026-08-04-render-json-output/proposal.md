## Why

`fabrique` currently prints command results (e.g. `wafer`) as a human-readable
line of text. There is no machine-readable output, which blocks scripting,
automation, and integration with other tooling that needs to consume device
definitions programmatically. Issue #34 requests that `fabrique` emit a JSON
response, conforming to a published `device.schema.json` schema, after
parsing commands. Producing that JSON also exposes gaps in `wafer`'s current
arguments: it has no way to specify a device `height` (the JSON schema
requires one), no defined unit system for the numbers it emits, and no way
to represent a material occupying more than one disjoint region of the
device.

## What Changes

- Add a `Device` model in `libfabrique` representing width, height (both
  always in nanometers), and an array of materials (name + one or more
  non-intersecting boundary polygons), matching `device.schema.json`.
  `Device` has no `units` member — nanometers is fabrique's fixed system
  unit, not a per-device setting.
- Add JSON serialization for `Device` (e.g. via a JSON library dependency)
  producing output that validates against `device.schema.json`.
- **BREAKING**: extend `wafer`'s command-line syntax to accept a `height`
  argument in addition to the existing `width` argument (e.g. `wafer Si
  500nm 300nm <1,1,1>`), instead of deriving `height` from `width`.
- Users may supply units on `width`/`height` command-line tokens (e.g.
  `500nm`, `0.5um`); `WaferCmd` converts any supplied unit to nanometers
  before building the `Device`. A bare number with no unit suffix is
  assumed to already be in nanometers.
- Update `WaferCmd::execute()` (and `main.cpp`) so that after a command is
  parsed, `fabrique` renders the resulting device as JSON to stdout instead
  of (or in addition to) the current plain-text summary. **BREAKING**: the
  `wafer` command's stdout format changes from plain text to JSON.
- Ship `device.schema.json` as a tracked schema file in the repository (e.g.
  under `fabrique/`) so it can be referenced by tests and consumers.

## Capabilities

### New Capabilities
- `json-output`: `fabrique` renders parsed command results as JSON conforming
  to `device.schema.json`.

### Modified Capabilities
<!-- none: no existing capability spec covers `wafer`'s CLI syntax or
     output format today, so there is nothing to mark Modified; the syntax
     and output changes are captured as part of the new `json-output`
     capability above -->

## Impact

- Affected code: `fabrique/fabrique/libfabrique/commands/WaferCmd.{hpp,cpp}`,
  `fabrique/fabrique/main.cpp`, `fabrique/fabrique/libfabrique/`.
- New dependency: a JSON serialization library (e.g. `nlohmann/json`) added
  via Conan.
- New tracked file: `device.schema.json` (currently untracked at repo root;
  to be relocated/added under `fabrique/`).
- Consumers of `fabrique wafer` output relying on the current plain-text
  format will need to update to parse JSON instead.
- Consumers/scripts invoking `fabrique wafer material width miller` will
  need to update to the new `material width height miller` argument order,
  since `height` is inserted before `miller`.
