VENV ?= _venv
PYTHON = $(VENV)/bin/python3
PIP = $(VENV)/bin/pip
CONAN = CONAN_HOME=$(CURDIR)/_conan2 $(VENV)/bin/conan
CMAKE = cmake

# Compiler used for both the Conan dependencies and the CMake build. Keeping
# them identical avoids ABI mismatches. Override to build with a different
# toolchain, e.g. `make CC=clang CXX=clang++`.
CC ?= cc
CXX ?= c++
BUILD_TYPE ?= Release

all: setup build

.PHONY: setup build test clean

$(VENV)/bin/activate:
	python3 -m venv $(VENV)
	$(PYTHON) -m pip install --upgrade pip

setup: $(VENV)/bin/activate
	$(PIP) install conan
	CC=$(CC) CXX=$(CXX) $(CONAN) profile detect --force
	CC=$(CC) CXX=$(CXX) $(CONAN) install . -s build_type=$(BUILD_TYPE) -s compiler.cppstd=20 --build=missing
	CC=$(CC) CXX=$(CXX) $(CMAKE) -B _build/$(BUILD_TYPE) -DCMAKE_TOOLCHAIN_FILE=_build/$(BUILD_TYPE)/generators/conan_toolchain.cmake -DCMAKE_BUILD_TYPE=$(BUILD_TYPE)

build: setup
	$(CMAKE) --build _build/$(BUILD_TYPE)

test: build

clean:
	rm -rf $(VENV)
	rm -rf _conan2
	rm -rf _build
