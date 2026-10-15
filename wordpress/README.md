# [growherbert.com](https://growherbert.com)

# [lynngrown.com](https://lynngrown.com)

# [metatooth.com](https://metatooth.com)

Deploys a WordPress-powered website

## Getting Started

After changing to this directory in a terminal, run the following make command.

```bash
ANSIBLE_INVENTORY=/path/to/inventory make deploy
```

Alternately, make a symlink to the Ansible inventory file:

```bash
ln -s /path/to/inventory ansible/inventory
make deploy
```

## Architecture

Load balancer (nginx) receives HTTPS requests, relays to a docker container running on another machine. This client server depends on a MariaDB instance.

## Make a new website

1. Create a MariaDB instance
2. Create a new inventory file based on the fields in `ansible/inventory.sample`
3. Use the new inventory to deploy a WordPress website
