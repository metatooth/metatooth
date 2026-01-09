---
applyTo: "**/ansible/**/*.yml"
---

# Ansible Playbook Review Instructions

## Structure Requirements
- Use 2-space indentation consistently
- Include `name` for all tasks
- Group related tasks with block/rescue/always when appropriate

## Security Checks
- Never hardcode passwords, API keys, or secrets
- Use `ansible_become_password` from vault, not inline
- File permissions should be explicit (mode: '0644' for files, '0755' for executables)

## Best Practices
- Use `ansible.builtin.*` fully qualified collection names
- Prefer `copy` with `content` over `template` for static content
- Use `synchronize` for directory transfers (already in use)
- Include `changed_when` and `failed_when` for shell/command tasks

## Host Groups
This project uses two host groups:
- `servers`: Application hosts running Docker Compose stacks
- `balancers`: Load balancer hosts running nginx

## Template Variables
Environment templates (`dotenv.j2`) expect these inventory variables:
- `wordpress_db_host`, `wordpress_db_user`, `wordpress_db_password`, `wordpress_db_name`
