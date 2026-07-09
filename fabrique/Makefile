VENV ?= _venv
PYTHON = $(VENV)/bin/python3
PIP = $(VENV)/bin/pip
CONAN = CONAN_HOME=$(CURDIR)/_conan2 $(VENV)/bin/conan
CMAKE = cmake

all: setup build

.PHONY: setup build test clean

$(VENV)/bin/activate:
	python3 -m venv $(VENV)
	$(PYTHON) -m pip install --upgrade pip

setup: $(VENV)/bin/activate
	$(PIP) install conan
	$(CONAN) profile detect --force
	$(CONAN) export ../multidraw
	$(CONAN) install . -s compiler=clang -s compiler.version=21 -s compiler.cppstd=20 --build=missing
	$(CMAKE) -B _build/Release -DCMAKE_TOOLCHAIN_FILE=_build/Release/generators/conan_toolchain.cmake -DCMAKE_BUILD_TYPE=Release

build: setup
	$(CMAKE) --build _build/Release

test: build

clean:
	rm -rf $(VENV)
	rm -rf _conan2
	rm -rf _build
