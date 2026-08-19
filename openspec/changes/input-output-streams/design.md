## Context

`fabrique/fabrique/main.cpp` currently dispatches a single command:
`argv[1] == "wafer"` triggers `WaferCmd::parse(argc - 2, argv + 2)` followed
by `cmd.execute()`, which writes one JSON document to stdout
(`json-output` capability). There is no notion of reading more than one
command per process invocation. See proposal.md - Why.

## Goals / Non-Goals

**Goals:**

- Let `fabrique` execute a sequence of commands, one per line, sourced from
  either a `--file <path>` argument or piped `stdin`, reusing the existing
  per-command parse/execute/JSON-output path unchanged.
- Keep existing single-command `argv` invocation (`fabrique wafer ...`)
  working exactly as it does today, with no output or dispatch change.
- Make command-line tokenization for batch-input lines behave the same as
  `argv` tokenization today (whitespace-separated tokens; the existing
  `WaferCmd::parse` already handles quoting-free tokens like `<1,1,1>`).

**Non-Goals:**

- Supporting multiple distinct command types beyond `wafer` — batch input
  reuses whatever command dispatch exists today (currently just `wafer`)
  and is not adding new commands.
- Shell-style quoting/escaping in batch-input lines — lines are split on
  whitespace the same way `argv` tokens already are; no extra quoting
  syntax is introduced.
- Parallel or out-of-order execution — commands run strictly in the order
  they appear in the input.
- Continuing past a malformed command in a batch (see Decisions below) —
  batch execution stops at the first error, matching the fail-fast
  behavior of a single `argv` command today.

## Decisions

- **Command source precedence**: `argv` command (e.g. `fabrique wafer
  ...`) takes precedence over `--file`/`stdin`. If `argv[1]` is a known
  command name, `fabrique` runs exactly that one command as it does today
  and never touches `--file` or `stdin`. This avoids ambiguity about what
  "the command" is when both an argv command and piped stdin are present,
  and keeps today's behavior byte-for-byte unchanged for existing callers.
- **`--file` vs. `stdin` selection**: `--file <path>` is an explicit,
  unambiguous request to read batch input from a named file. Bare `stdin`
  batch mode only activates when `fabrique` is invoked with **no** command
  argument and **no** `--file`, and `isatty(STDIN_FILENO)` is false
  (`stdin` has been redirected or piped). This preserves the existing
  no-argument interactive behavior (exit 0, no output) since a stdin
  connected to a terminal is not batch input. Alternative considered: an
  explicit `--stdin` flag instead of TTY detection — rejected because it
  makes the common `command_file | fabrique` / `fabrique < commands.txt`
  usage from the issue require an extra flag; TTY detection is a standard
  Unix CLI convention (used by tools like `cat`, `grep`) and keeps the
  ergonomics the issue asks for.
- **Line format**: one command per line, tokenized the same way `argv`
  tokens are today (split on whitespace, no quoting support), so a batch
  line for `wafer` looks exactly like the `argv` form minus the leading
  `wafer`... actually including it, e.g. `wafer Si 500nm 300nm <1,1,1>` —
  the first token on the line selects the command the same way `argv[1]`
  does today. This keeps one shared tokenizer/dispatcher between `argv`
  mode and batch mode instead of two divergent argument grammars.
- **New component**: add a small batch reader/dispatcher in `libfabrique`
  (e.g. `libfabrique/commands/CommandStream.{hpp,cpp}`) that: (a) reads
  lines from a `std::istream`, (b) skips blank/`#`-comment lines, (c)
  splits each remaining line into tokens, (d) dispatches to the matching
  command's `parse`/`execute` (currently only `wafer`), reusing the same
  dispatch logic `main.cpp` uses for the `argv` case rather than
  duplicating it. `main.cpp` calls this component for both the `--file`
  and `stdin` cases, passing an `std::ifstream` or `std::cin` respectively.
- **Error handling / fail-fast**: a malformed command line during batch
  execution throws `std::invalid_argument` (or lets an existing exception
  propagate) the same way a bad `argv` command does today; the batch
  reader lets it propagate out to `main.cpp`'s existing top-level
  `std::exception` handler, which prints to stderr and exits non-zero.
  Commands before the failing one have already written their JSON to
  stdout and are not rolled back — matching the single-command semantics
  of "each command's success/failure is independent and immediately
  visible." Alternative considered: collect all errors and continue
  processing remaining commands — rejected as unnecessary complexity not
  requested by the issue; can be added later without a breaking change if
  a real use case appears.

## Risks / Trade-offs

- [TTY detection (`isatty`) is platform-specific (POSIX `unistd.h`)] →
  `fabrique` already targets a POSIX-like build (CMake/Conan on the
  platforms this repo targets); no Windows target exists today, so this is
  acceptable. If Windows support is ever added, this call needs a
  platform-guarded equivalent.
- [Reusing `argv`-style whitespace tokenization for batch lines means a
  value containing a space (not currently possible for any `wafer`
  argument) can't be expressed] → no current command argument needs
  embedded spaces; revisit if a future command does.
- [Fail-fast batch execution means one bad line in a long file aborts
  everything after it, with no way to skip-and-continue] → matches
  existing single-command error semantics and keeps scope minimal; a
  `--continue-on-error` style flag can be added later without breaking
  this design if needed.

## Migration Plan

- Add `CommandStream.{hpp,cpp}` to `libfabrique`, register in
  `fabrique/fabrique/CMakeLists.txt`.
- Update `main.cpp` to: keep the existing `argv[1] == "wafer"` fast path
  unchanged; add `--file <path>` parsing that opens the file and hands it
  to `CommandStream`; otherwise, when no command argument is present, check
  `isatty(STDIN_FILENO)` and hand `std::cin` to `CommandStream` when it's
  false.
- No data migration; this is additive CLI behavior with no persisted
  state and no change to existing `argv`-mode output.
