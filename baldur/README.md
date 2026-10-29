# Baldur

An image gallery using Sinatra.rb + ROM
Powered by puma

## Getting Started

```
$ git clone https://github.com/terryg/baldur.git
$ cd baldur
$ bundle install --path vendor/bundle
$ bundle exec foreman run puma
```

### Environment Variables

When using foreman, the following entries are needed in .env

AWS_ACCESS_KEY_ID
AWS_SECRET_ACCESS_KEY
DATABASE_URL
RACK_SECRET
S3_BUCKET_NAME

## Deployment inventory

Besides the usual connection and database settings, the inventory must define
`site_domain`, `backend_host` (the host running the container, used as the
nginx upstream) and `baldur_port` (the host port mapped to the container's
4567). `container_name` is optional and defaults to the reversed domain
(`com.laramirandagoodman`).

## TLS certificates

Run before the first deploy (set `site_domain` and `acme_email` in the
inventory):

```bash
cd deploy
ANSIBLE_INVENTORY=/path/to/inventory make certs
```

Certificates are issued on the balancers with acme.sh, which renews them from
cron. Check expiry with `../deploy/scripts/check-certs.sh <domain>`.
