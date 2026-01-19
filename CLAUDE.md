# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Metatooth is a monorepo managing containerized WordPress deployments for three websites:
- **growherbert** (growherbert.com) - exposed on port 3030
- **lynngrown** (lynngrown.com) - exposed on port 3300
- **metatooth/www** (metatooth.com) - exposed on port 3330

The project uses Ansible for infrastructure automation with Docker Compose for containerization.

## Build & Deploy Commands

All sites follow identical patterns. Replace `{site}` with `growherbert`, `lynngrown`, or `metatooth/www`:

```bash
# Deploy to servers
cd {site}/deploy
ANSIBLE_INVENTORY=/path/to/inventory make deploy

# Available Makefile targets
make setup    # Validate config (checks branch, inventory)
make build    # Build deployment package via build.sh
make deploy   # Run ansible-playbook deployment
make clean    # Remove setup stamp
```

### Environment Variables
- `GROWHERBERT_BRANCH` / `LYNNGROWN_BRANCH` / `METATOOTH_BRANCH` - Git branch/tag to deploy (default: "local")
- `ANSIBLE_INVENTORY` - Path to Ansible inventory file

### Maintenance Playbooks
```bash
# Update packages on all hosts
ansible-playbook -i /path/to/inventory deploy/ansible/apt-upgrade.yml

# Reboot hosts with wait
ansible-playbook -i /path/to/inventory deploy/ansible/reboot.yml
```

## Architecture

```
                        ┌─────────────────────┐
                        │   Nginx Balancer    │ (balancers host group)
                        │   SSL termination   │
                        └──────────┬──────────┘
                                   │
              ┌────────────────────┼────────────────────┐
              ↓                                         ↓
┌─────────────────────────┐              ┌─────────────────────────┐
│  growherbert (port 3030)│              │  lynngrown (port 3300)  │
│  ┌───────────────────┐  │              │  ┌───────────────────┐  │
│  │ Nginx (Alpine)    │  │              │  │ Nginx (Alpine)    │  │
│  │ Reverse Proxy     │  │              │  │ Reverse Proxy     │  │
│  └─────────┬─────────┘  │              │  └─────────┬─────────┘  │
│            ↓ :9000      │              │            ↓ :9000      │
│  ┌───────────────────┐  │              │  ┌───────────────────┐  │
│  │ WordPress FPM     │  │              │  │ WordPress FPM     │  │
│  │ PHP 8.3 Alpine    │  │              │  │ PHP 8.3 Alpine    │  │
│  └───────────────────┘  │              │  └───────────────────┘  │
│         ↓               │              │         ↓               │
│   [MariaDB External]    │              │   [MariaDB External]    │
└─────────────────────────┘              └─────────────────────────┘
        (servers host group)                    (servers host group)
```

### Ansible Host Groups
- **servers**: Docker Compose stack hosts (WordPress + local Nginx)
- **balancers**: Load balancer hosts (SSL nginx reverse proxy)

### Deployment Flow
1. `build.sh` stages files to `/tmp/{domain}.com/deployment/`
2. Ansible synchronizes to target servers
3. `.env` generated from `dotenv.j2` template
4. Docker Compose stack started via systemd service

## Key Files Per Site

| File | Purpose |
|------|---------|
| `docker-compose.yml` | WordPress FPM + Nginx containers |
| `nginx.conf` | Local container nginx config |
| `{site}-com` | Remote balancer nginx site config |
| `deploy/ansible/deploy.yml` | Main deployment playbook |
| `deploy/ansible/templates/dotenv.j2` | Environment variable template |
| `deploy/ansible/templates/systemd-service.j2` | Systemd service template |

## Pre-commit Hooks

The repo uses pre-commit with: trailing-whitespace, end-of-file-fixer, mixed-line-ending (lf), check-yaml, check-json, check-added-large-files.
