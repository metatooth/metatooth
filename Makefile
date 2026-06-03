SETUP_STAMP=.metatooth.setup.stamp

.PHONY: setup

all: setup

$(SETUP_STAMP):
	npm install
	npx openspec init --tools claude
	touch $(SETUP_STAMP)

setup: $(SETUP_STAMP)

clean:
	rm -f $(SETUP_STAMP)
	rm -rf node_modules/
