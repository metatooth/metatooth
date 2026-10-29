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

## TLS certificates

Run before the first deploy of a site (needs `site_domain` and `acme_email` in
the inventory), and again to force a renewal check:

```bash
ANSIBLE_INVENTORY=/path/to/inventory make certs
```

This issues a certificate for `site_domain` and `www.site_domain` on the
balancers with acme.sh; acme.sh's cron job handles later renewals. Check expiry
with `../deploy/scripts/check-certs.sh <domain>`.

## Architecture

Load balancer (nginx) receives HTTPS requests, relays to a docker container running on another machine. This client server depends on a MariaDB instance.

## Make a new website

1. Create a MariaDB instance
2. Create a new inventory file based on the fields in `ansible/inventory.sample`
3. Use the new inventory to deploy a WordPress website
