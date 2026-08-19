## Purpose

Defines how `fabrique` accepts a batch of commands from a file or `stdin`,
one command per line, and executes each in order, so a whole sequence of
device-building commands can be run in a single invocation and scripted.

## ADDED Requirements

### Requirement: Batch commands from a file

`fabrique` SHALL accept a `--file <path>` (or `-f <path>`) command-line
option. When given, `fabrique` SHALL read the file at `<path>`, treat each
non-empty, non-comment line as a single command using the same syntax as
the existing `argv` command form, and execute the commands in the order
they appear in the file.

#### Scenario: Commands read from a file

- **WHEN** a user runs `fabrique --file commands.txt`, and `commands.txt`
  contains two `wafer` command lines
- **THEN** `fabrique` executes both commands in the order they appear in
  the file

#### Scenario: File does not exist

- **WHEN** a user runs `fabrique --file missing.txt` and `missing.txt`
  does not exist
- **THEN** `fabrique` reports an error and exits with a non-zero status
- **AND** no JSON is written to stdout

### Requirement: Batch commands from stdin

When `fabrique` is invoked with no command arguments and `stdin` is not an
interactive terminal (i.e. it has been piped or redirected), `fabrique`
SHALL read commands from `stdin`, one per line, using the same syntax as
the existing `argv` command form, and execute them in the order they are
read. `fabrique` invoked with no command arguments and no piped/redirected
`stdin` (an interactive terminal) SHALL exit 0 without reading `stdin` or
emitting output, matching prior behavior.

#### Scenario: Commands piped via stdin

- **WHEN** a user runs `printf 'wafer Si 500nm 300nm\nwafer Ge 200nm
  200nm\n' | fabrique`
- **THEN** `fabrique` executes both `wafer` commands in the order they were
  written to stdin

#### Scenario: No command and no piped input

- **WHEN** a user runs `fabrique` with no arguments at an interactive
  terminal (stdin is not redirected or piped)
- **THEN** `fabrique` exits with status 0 and produces no output

### Requirement: Single-command argv mode takes precedence

When `fabrique` is invoked with a command directly on the command line
(e.g. `fabrique wafer ...`), it SHALL execute only that command and SHALL
NOT read `--file` or `stdin` batch input, preserving existing single-command
behavior unchanged.

#### Scenario: argv command ignores stdin

- **WHEN** a user runs `fabrique wafer Si 500nm 300nm <1,1,1>` with
  unrelated data piped into stdin
- **THEN** `fabrique` executes only the `wafer` command from argv
- **AND** the piped stdin content is not read as additional commands

### Requirement: Comments and blank lines in batch input

When reading commands from a file or `stdin`, `fabrique` SHALL ignore blank
lines and lines whose first non-whitespace character is `#`; such lines
SHALL NOT be treated as commands or produce an error.

#### Scenario: Blank line and comment line are skipped

- **WHEN** batch input contains a blank line and a line `# comment` between
  two `wafer` command lines
- **THEN** `fabrique` executes only the two `wafer` commands
- **AND** the blank line and comment line produce no output and no error

### Requirement: Per-command JSON output and error isolation

For each command executed from batch input, `fabrique` SHALL apply the
same JSON output and error-reporting behavior defined for single-command
execution (see the `json-output` capability): a successfully executed
command writes one JSON document to stdout, and a command that fails to
parse or execute is reported as an error without emitting a JSON document
for that command.

#### Scenario: One JSON document per successful command

- **WHEN** batch input contains two valid `wafer` commands
- **THEN** `fabrique` writes two JSON documents to stdout, one per command,
  in input order

#### Scenario: A malformed command in a batch is reported and processing stops

- **WHEN** batch input contains a valid `wafer` command followed by a
  command with malformed arguments
- **THEN** `fabrique` writes the JSON document for the valid command that
  executed before the error
- **AND** reports the malformed command's error to stderr
- **AND** exits with a non-zero status without executing any commands
  after the malformed one
