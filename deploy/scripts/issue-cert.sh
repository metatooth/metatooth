#!/usr/bin/env bash
# Issue (or renew) a TLS certificate with acme.sh using the nginx webroot
# challenge. Runs on a balancer host as root; invoked by certs.yml but safe to
# run by hand.
#
# Usage: issue-cert.sh <email> <ca> <webroot> <domain> [alias...]
#
# Certificates land in /root/.acme.sh/<domain>_ecc/, which is where the
# balancer nginx site configs expect them. acme.sh installs its own cron job
# for renewals and reloads nginx after each one.
set -euo pipefail

if [ "$#" -lt 4 ]; then
  echo "usage: $0 <email> <ca> <webroot> <domain> [alias...]" >&2
  exit 64
fi

email=$1
ca=$2
webroot=$3
domain=$4
shift 4

acme=/root/.acme.sh/acme.sh

if [ ! -x "$acme" ]; then
  curl -fsSL https://get.acme.sh | sh -s "email=${email}"
fi

mkdir -p "$webroot"

"$acme" --set-default-ca --server "$ca" >/dev/null
"$acme" --register-account -m "$email" --server "$ca" >/dev/null || true

domains=(-d "$domain")
for alias in "$@"; do
  domains+=(-d "$alias")
done

# acme.sh exits 2 when the certificate is not yet due for renewal.
status=0
"$acme" --issue "${domains[@]}" -w "$webroot" --keylength ec-256 || status=$?
if [ "$status" -ne 0 ] && [ "$status" -ne 2 ]; then
  exit "$status"
fi

"$acme" --install-cert -d "$domain" --ecc \
  --reloadcmd "nginx -t && systemctl reload nginx"
