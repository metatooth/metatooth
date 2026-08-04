## 1. Schema and dependency setup

- [x] 1.1 Move `device.schema.json` into `fabrique/` (e.g.
      `fabrique/device.schema.json`) and remove the untracked root copy
- [x] 1.2 Add `nlohmann_json` to `fabrique/conanfile.py` requirements and
      regenerate/verify the Conan lockfile builds

## 2. Device model

- [x] 2.1 Add `fabrique/fabrique/libfabrique/Device.hpp` with a `Device`
      struct (`width`, `height` in nanometers, no `units` member, and
      `materials` of `{name, boundary}`, where `boundary` is a list of one
      or more non-intersecting polygons)
- [x] 2.2 Add `fabrique/fabrique/libfabrique/Device.cpp` with a `to_json`
      function producing output matching `device.schema.json`
- [x] 2.3 Register `Device.cpp` in `fabrique/fabrique/CMakeLists.txt`
      (`libfabrique` sources) and install `Device.hpp`

## 3. Wafer command JSON output

- [x] 3.1 Add a `height` argument to `WaferCmd`'s CLI syntax
      (`wafer [material] [width] [height] [miller]`) alongside the existing
      `width` argument
- [x] 3.2 Add a helper in `WaferCmd` (or `Device`) to split an optional unit
      suffix from a `width`/`height` token (e.g. `500nm`, `0.5um`) and
      convert the value to nanometers; a token with no suffix is assumed to
      already be in nanometers
- [x] 3.3 Update `WaferCmd::execute()` to build/update the `Device` from
      `_material` and the parsed width/height (both in nanometers), adding
      or extending the affected material's boundary polygon(s) in
      `materials`
- [x] 3.4 Update `WaferCmd::execute()` to serialize the `Device` to JSON and
      print it to stdout in place of the current plain-text line

## 4. Error handling

- [x] 4.1 Update `fabrique/fabrique/main.cpp` to catch `std::exception`
      around command parsing/execution, print the error to stderr, and
      return a non-zero exit code without emitting JSON

## 5. Tests

- [x] 5.1 Add/extend tests under `fabrique/tests/` covering: valid `wafer`
      command with explicit `width`/`height` produces JSON matching
      `device.schema.json`'s required properties (with no `units`
      property), unitless `width`/`height` tokens are treated as
      nanometers, non-nanometer unit suffixes (e.g. `um`) are converted to
      nanometers, and malformed Miller indices produce a non-zero exit with
      no JSON on stdout
