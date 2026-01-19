---
applyTo: "**/Makefile"
---

# Makefile Review Instructions

## Target Structure
Standard targets in order of dependency:
1. `setup` - Validate configuration, create `.setup.stamp`
2. `build` - Run `build.sh`, depends on setup
3. `deploy` - Run ansible-playbook, depends on build
4. `clean` - Remove `.setup.stamp`

## Phony Targets
Declare all non-file targets as `.PHONY`:
```make
.PHONY: setup build deploy clean
```

## Environment Variables
- `ANSIBLE_INVENTORY` - Required, path to inventory file
- `{SITE}_BRANCH` - Optional, defaults to "local"

## Validation
- Check that required environment variables are set
- Validate inventory file exists before deployment
- Use stamp files (`.setup.stamp`) to track setup state
