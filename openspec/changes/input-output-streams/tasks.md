## 1. Command stream reader/dispatcher

- [x] 1.1 Add `fabrique/fabrique/libfabrique/commands/CommandStream.hpp`/`.cpp`
      that reads lines from a `std::istream`, skips blank lines and lines
      whose first non-whitespace character is `#`, and splits each
      remaining line into whitespace-separated tokens
- [x] 1.2 Extract the existing `argv[1] == "wafer"` dispatch logic in
      `main.cpp` into a shared dispatch function usable by both the `argv`
      fast path and `CommandStream` (so the token-to-command mapping is
      defined once)
- [x] 1.3 Have `CommandStream` dispatch each parsed line's tokens through
      the shared dispatch function, executing each command in order and
      letting parse/execute errors propagate immediately (fail-fast, no
      catch-and-continue)
- [x] 1.4 Register `CommandStream.cpp` in
      `fabrique/fabrique/CMakeLists.txt` (`libfabrique` sources) and
      install `CommandStream.hpp`

## 2. CLI wiring

- [x] 2.1 Add `--file <path>` (`-f <path>`) parsing to `main.cpp`: open the
      file as an `std::ifstream`, error out (propagate exception) if it
      cannot be opened, and pass it to `CommandStream`
- [x] 2.2 Add stdin batch mode to `main.cpp`: when no command argument and
      no `--file` are given, check `isatty(STDIN_FILENO)`; if false, pass
      `std::cin` to `CommandStream`; if true, preserve existing behavior
      (exit 0, no output)
- [x] 2.3 Confirm the existing `argv[1] == "wafer"` fast path is checked
      before `--file`/stdin handling and short-circuits them, per
      design.md's precedence decision

## 3. Tests

- [x] 3.1 Add tests for `CommandStream` covering: multiple valid commands
      produce one JSON document per command in order, blank lines and `#`
      comment lines are skipped without error, and a malformed command
      line stops processing and propagates an error without executing
      subsequent lines
- [x] 3.2 Add/extend `fabrique` CLI-level tests covering: `--file
      <path>` with a file of valid commands, `--file <path>` with a
      nonexistent file (non-zero exit, no JSON), commands piped via stdin,
      an `argv` command taking precedence over piped stdin content, and no
      command with no piped stdin (interactive) exiting 0 with no output
      (the interactive/TTY case is not covered by an automated test — CI
      test runners connect stdin from a non-TTY source, e.g. `/dev/null`,
      so a real interactive TTY cannot be simulated in this harness; the
      no-input non-TTY path is exercised indirectly by `CommandStream`'s
      unit tests, which show an empty/blank stream produces no output)
