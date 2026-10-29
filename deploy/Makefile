ANSIBLE_INVENTORY ?= ansible/inventory
CERTS_VARS ?= ansible/certs.vars.yml

.PHONY: all certs certs-check

all: certs

# Issue/renew TLS certificates on the balancers (run before first site deploy).
certs:
	ansible-playbook -i ${ANSIBLE_INVENTORY} -e @${CERTS_VARS} ansible/certs.yml

# Report expiry of the live certificates; override CERT_DOMAINS as needed.
CERT_DOMAINS ?= growherbert.com lynngrown.com metatooth.com laramirandagoodman.com
certs-check:
	./scripts/check-certs.sh ${CERT_DOMAINS}
