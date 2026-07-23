VENV ?= _venv
PYTHON = $(VENV)/bin/python3
PIP = $(VENV)/bin/pip
PRE_COMMIT = $(VENV)/bin/pre-commit

STAMP = .setup.stamp

all: setup

.PHONY: all setup clean

$(VENV)/bin/activate:
	python3 -m venv $(VENV)
	$(PYTHON) -m pip install --upgrade pip

setup: $(STAMP)

$(STAMP): $(VENV)/bin/activate
	$(PIP) install pre-commit
	$(PRE_COMMIT) install
	touch $(STAMP)

clean:
	rm -rf $(VENV) $(STAMP)
