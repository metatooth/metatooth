ANSIBLE_INVENTORY ?= ansible/inventory

.PHONY: all deploy restore-db

all: deploy

deploy: ansible/deploy.yml
	ansible-playbook -i ${ANSIBLE_INVENTORY} ansible/deploy.yml

restore-db: ansible/restore-db.yml
	ansible-playbook -i ${ANSIBLE_INVENTORY} ansible/restore-db.yml
