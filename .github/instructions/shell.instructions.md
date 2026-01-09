---
applyTo: "**/*.sh"
---

# Shell Script Review Instructions

## Error Handling
- Scripts should use `set -e` to exit on errors
- Use `set -u` to catch undefined variables
- Consider `set -o pipefail` for pipeline error handling

## Build Scripts (build.sh)
- Clone from GitHub when branch is not "local"
- Stage deployment files to `/tmp/{domain}.com/deployment/`
- Clean up temporary directories on completion

## Variables
- Use uppercase for environment variables (GROWHERBERT_BRANCH, LYNNGROWN_BRANCH)
- Quote all variable expansions: `"${VAR}"`
- Provide sensible defaults: `${VAR:-default}`

## Git Operations
- Use `--depth=1` for shallow clones when full history not needed
- Verify branch/tag exists before checkout
