## Why

`fabrique` currently executes exactly one command per invocation, taken
directly from `argv` (e.g. `fabrique wafer Si 500nm 300nm <1,1,1>`). There is
no way to run a batch of commands without shelling out once per command, which
blocks scripting workflows that want to describe a whole device (or several)
in one file and get back a stream of JSON results. Issue #36 requests that
`fabrique` also accept commands from `stdin` or from a file, and continue
writing its results to `stdout`.

## What Changes

- Add a batch input mode: `fabrique` reads a sequence of commands, one per
  line, using the same syntax as the existing `argv` form (e.g. `wafer Si
  500nm 300nm <1,1,1>`), and executes them in order.
- Commands may be supplied from a file via a new `--file <path>` (`-f
  <path>`) flag, or from `stdin` when no command is given on the command
  line and input is piped/redirected in.
- Blank lines and lines beginning with `#` in batch input are ignored
  (comments/spacing), not treated as commands.
- Each successfully executed command writes its own JSON document to
  `stdout`, in input order, using the existing per-command JSON output
  (`json-output` capability) unchanged.
- Existing single-command `argv` usage (`fabrique wafer ...`) is unaffected
  and continues to take precedence over batch input.
- **BREAKING**: none to existing `argv` usage. Running `fabrique` with no
  `argv` command and no piped `stdin` (interactive, nothing redirected)
  previously exited 0 with no output; behavior for that case is preserved
  (see design.md for the stdin-vs-TTY decision).

## Capabilities

### New Capabilities
- `command-input`: `fabrique` reads a batch of commands from a file or
  `stdin`, one command per line, and executes each in order, in addition to
  the existing single-command `argv` mode.

### Modified Capabilities
<!-- none: json-output's per-command JSON contract (device.schema.json,
     error handling) is reused unchanged for each command in a batch; no
     requirement in that spec changes. -->

## Impact

- Affected code: `fabrique/fabrique/main.cpp` (command dispatch), likely a
  new `libfabrique` component to read/parse a command stream and dispatch
  each line to the appropriate command (currently only `WaferCmd`).
- No changes to `WaferCmd` itself or to `device.schema.json` / JSON output
  format.
- Scripts that previously invoked `fabrique` once per command can switch to
  a single invocation with `--file` or piped `stdin`, but are not required
  to.
