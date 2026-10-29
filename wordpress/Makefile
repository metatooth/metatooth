ANSIBLE_INVENTORY ?= ansible/inventory

.PHONY: all deploy certs

all: deploy

deploy: ansible/deploy.yml
	ansible-playbook -i ${ANSIBLE_INVENTORY} ansible/deploy.yml

# Issue/renew the TLS certificate on the balancers (run before first deploy).
certs: ansible/certs.yml
	ansible-playbook -i ${ANSIBLE_INVENTORY} ansible/certs.yml
