GROWHERBERT_BRANCH ?= local
ANSIBLE_INVENTORY ?= ansible/inventory
SETUP_STAMP = .setup.stamp

.PHONY: all build check_branch check_inventory clean deploy setup

all: deploy;

check_branch:
ifeq ($(GROWHERBERT_BRANCH),)
	@echo ""
	@echo "Must set GROWHERBERT_BRANCH. Use \"local\" to deploy current changes,"
	@echo "or use a tag version to deploy a release."
	@echo ""
	@exit 1
endif

check_inventory:
ifeq ($(ANSIBLE_INVENTORY),)
	@echo ""
	@echo "Must set ANSIBLE_INVENTORY. Use \"ansible/inventory\" to use invetory"
  @echo	"from current repository."
	@echo ""
	@exit 1
endif

$(SETUP_STAMP): check_branch check_inventory
	touch $(SETUP_STAMP)

setup: $(SETUP_STAMP)

build: setup
	./build.sh ${GROWHERBERT_BRANCH}

deploy: build ansible/deploy.yml
	ansible-playbook -i ${ANSIBLE_INVENTORY} ansible/deploy.yml

clean:
	rm $(SETUP_STAMP)
