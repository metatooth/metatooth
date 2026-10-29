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

## TLS certificates

Run before the first deploy (set `acme_email` in the inventory; `site_domain`
optionally overrides the default `laramirandagoodman.com`):

```bash
cd deploy
ANSIBLE_INVENTORY=/path/to/inventory make certs
```

Certificates are issued on the balancers with acme.sh, which renews them from
cron. Check expiry with `../deploy/scripts/check-certs.sh <domain>`.
