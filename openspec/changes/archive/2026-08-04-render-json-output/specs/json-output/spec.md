## Purpose

Defines the behavior by which `fabrique` renders the result of a parsed
command as a JSON document conforming to the published `device.schema.json`
schema, so that command results can be consumed by other tools and scripts.

## ADDED Requirements

### Requirement: Device JSON output

After `fabrique` successfully parses a command that produces a device
definition (e.g. `wafer`), it SHALL write a single JSON document to stdout
that validates against `device.schema.json`, including the required
`width` and `height` properties, both expressed in nanometers, fabrique's
fixed system unit. The JSON output SHALL NOT include a `units` property.

#### Scenario: Wafer command emits valid device JSON

- **WHEN** a user runs `fabrique wafer Si 500nm 300nm <1,1,1>`
- **THEN** `fabrique` writes to stdout a single JSON object containing
  `width`, `height`, and a `materials` array with an entry for `Si`
- **AND** that JSON object validates against `device.schema.json`

#### Scenario: Materials array reflects parsed command

- **WHEN** a command specifies one or more materials
- **THEN** the `materials` array in the JSON output SHALL contain one
  entry per material, each with at least a `name` property matching the
  parsed value

#### Scenario: Wafer command specifies width and height explicitly

- **WHEN** a user runs `fabrique wafer Si 500nm 300nm <1,1,1>`
- **THEN** the JSON output's `width` SHALL be `500` and `height` SHALL be
  `300`, reflecting the two distinct arguments rather than one being
  derived from the other

#### Scenario: Unitless width/height tokens are assumed to be nanometers

- **WHEN** a user runs a command whose `width`/`height` tokens have no unit
  suffix (e.g. `fabrique wafer Si 500 300 <1,1,1>`)
- **THEN** `fabrique` SHALL treat `500` and `300` as already being in
  nanometers, with no conversion applied

#### Scenario: Non-nanometer units are converted at the command interface

- **WHEN** a user runs `fabrique wafer Si 0.5um 300nm <1,1,1>`
- **THEN** `fabrique` SHALL convert `0.5um` to nanometers before building
  the device
- **AND** the JSON output's `width` SHALL be `500`

#### Scenario: A material has more than one boundary polygon

- **WHEN** command execution results in a material occupying more than one
  non-intersecting region of the device
- **THEN** that material's entry in the `materials` array SHALL describe
  all of its boundary polygons
- **AND** those polygons SHALL NOT intersect one another

### Requirement: Parse error reporting

If a command's arguments cannot be parsed (e.g. malformed Miller indices),
`fabrique` SHALL NOT emit a JSON device document; it SHALL report the error
and exit with a non-zero status.

#### Scenario: Malformed Miller index argument

- **WHEN** a user runs `fabrique wafer Si 500nm <1,1>`
- **THEN** `fabrique` exits with a non-zero status
- **AND** no JSON device document is written to stdout

### Requirement: Schema availability

The `device.schema.json` schema SHALL be tracked in the repository and kept
in sync with the JSON structure `fabrique` emits, so consumers can validate
output independently.

#### Scenario: Schema matches emitted output

- **WHEN** `fabrique` emits a device JSON document for any supported command
- **THEN** every property present in the output is described by
  `device.schema.json`
- **AND** every property `device.schema.json` marks as `required` is present
  in the output
