ANSIBLE_INVENTORY ?= ansible/inventory
MAGENTO_SRC ?= ~/metaspace/magento

.PHONY: all deploy

all: deploy

deploy: ansible/deploy.yml
	ansible-playbook -i ${ANSIBLE_INVENTORY} -e magento_src=$(MAGENTO_SRC) ansible/deploy.yml
