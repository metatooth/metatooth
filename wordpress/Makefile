ANSIBLE_INVENTORY ?= ansible/inventory

.PHONY: all deploy

all: deploy

deploy: ansible/deploy.yml
	ansible-playbook -i ${ANSIBLE_INVENTORY} ansible/deploy.yml
