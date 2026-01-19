# GitHub Copilot Instructions

This repository is an infrastructure monorepo managing containerized WordPress deployments for two websites (growherbert.com, lynngrown.com) using Ansible and Docker Compose.

## Project Structure

- `growherbert/` and `lynngrown/` contain identical deployment structures
- Each site has: `docker-compose.yml`, `nginx.conf`, `{site}-com` (balancer config), and `deploy/` directory
- Ansible playbooks deploy to two host groups: `servers` (Docker stack) and `balancers` (nginx reverse proxy)

## Code Review Guidelines

### Ansible Playbooks
- Validate YAML syntax and proper indentation (2 spaces)
- Check for hardcoded secrets - use Ansible vault or environment variables
- Ensure handlers are defined when using `notify`
- Verify `become: true` is used appropriately for privileged operations
- Check that file paths use variables from inventory/group_vars

### Docker Compose
- Verify service names are unique across the monorepo
- Check volume mounts and network configurations
- Ensure container names follow the pattern: `{service}-{site}-1`
- Validate environment variable references match `.env` template

### Nginx Configuration
- Check upstream server references match Docker service ports
- Verify SSL certificate paths for balancer configs
- Ensure proper proxy headers (X-Real-IP, X-Forwarded-For, X-Forwarded-Proto)

### Shell Scripts (build.sh)
- Validate proper error handling with `set -e`
- Check git operations use correct branch variables
- Verify staging paths follow `/tmp/{domain}.com/` pattern

### Makefiles
- Ensure phony targets are declared
- Validate dependency chains (setup → build → deploy)
- Check environment variable defaults are appropriate

## Consistency Rules

When reviewing changes to one site (growherbert or lynngrown), check if the same change should be applied to the other site to maintain consistency.
