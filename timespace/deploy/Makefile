BALDUR_BRANCH ?= local
ANSIBLE_INVENTORY ?= ansible/inventory

.PHONY: all
all: deploy-all

.PHONY: check_branch
check_branch:
ifeq ($(BALDUR_BRANCH),)
	@echo ""
	@echo "Must set BALDUR_BRANCH. Use \"local\" to deploy current changes,"
	@echo "or use a tag version to deploy a release."
	@echo ""
	@exit 1
endif

.PHONY: check_inventory
check_inventory:
ifeq ($(ANSIBLE_INVENTORY),)
	@echo ""
	@echo "Must set ANSIBLE_INVENTORY. Use \"ansible/inventory\" to use invetory"
  @echo	"from current repository."
	@echo ""
	@exit 1
endif

.PHONY: deploy-all
deploy-all: deploy-baldur

.PHONY: build
build: check_branch check_inventory
	./build.sh ${BALDUR_BRANCH} ${ANSIBLE_INVENTORY}

.PHONY: deploy
deploy:
	ansible-playbook -i ${ANSIBLE_INVENTORY} ansible/deploy.yml

.PHONY: deploy-baldur
deploy-baldur:
	$(MAKE) build
	$(MAKE) deploy
