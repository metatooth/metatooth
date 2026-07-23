VENV ?= _venv
PYTHON = $(VENV)/bin/python3
PIP = $(VENV)/bin/pip
PRECOMMIT = $(VENV)/bin/pre-commit

all: setup

.PHONY: all setup clean

$(VENV)/bin/activate:
	python3 -m venv $(VENV)
	$(PYTHON) -m pip install --upgrade pip

setup: $(VENV)/bin/activate
	$(PIP) install pre-commit
	$(PRECOMMIT) install

clean:
	rm -rf $(VENV)
