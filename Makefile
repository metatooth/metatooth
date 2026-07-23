VENV ?= _venv
PYTHON = $(VENV)/bin/python3
PIP = $(VENV)/bin/pip

all: setup

.PHONY: setup clean

$(VENV)/bin/activate:
	python3 -m venv $(VENV)
	$(PYTHON) -m pip install --upgrade pip

setup: $(VENV)/bin/activate
	$(PIP) install pre-commit
	pre-commit install

clean:
	rm -rf $(VENV)
