#!/usr/bin/env bash
# Report the expiry of the certificate served for each domain.
#
# Usage: check-certs.sh [-w days] <domain>...
#
# Exits non-zero if any certificate cannot be fetched or expires within
# <days> days (default 21).
set -uo pipefail

warn_days=21
if [ "${1:-}" = "-w" ]; then
  warn_days=$2
  shift 2
fi

if [ "$#" -eq 0 ]; then
  echo "usage: $0 [-w days] <domain>..." >&2
  exit 64
fi

rc=0
for domain in "$@"; do
  end=$(echo | openssl s_client -servername "$domain" -connect "${domain}:443" 2>/dev/null \
    | openssl x509 -noout -enddate 2>/dev/null | cut -d= -f2)
  if [ -z "$end" ]; then
    printf '%-32s FAIL  could not fetch certificate\n' "$domain"
    rc=1
    continue
  fi
  expires=$(date -d "$end" +%s)
  days=$(( (expires - $(date +%s)) / 86400 ))
  if [ "$days" -lt "$warn_days" ]; then
    printf '%-32s WARN  %d days left (%s)\n' "$domain" "$days" "$end"
    rc=1
  else
    printf '%-32s ok    %d days left (%s)\n' "$domain" "$days" "$end"
  fi
done
exit "$rc"
