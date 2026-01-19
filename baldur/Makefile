VENV ?= _venv
PYTHON = $(VENV)/bin/python3
PIP = $(VENV)/bin/pip
PRE_COMMIT = $(VENV)/bin/pre-commit

.PHONY: setup docker up down logs clean migrate

$(VENV)/bin/activate:
	python3 -m venv $(VENV)
	$(PYTHON) -m pip install --upgrade pip

setup: $(VENV)/bin/activate
	$(PIP) install pre-commit
	$(PRE_COMMIT) install
	git config core.hooksPath hooks

docker:
	docker compose build

up:
	docker compose up -d

down:
	docker compose down

logs:
	docker compose logs -f

migrate:
	DATABASE_URL="postgresql://postgres:baldur@localhost:5432/baldur?sslmode=disable" bundle exec ruby db/migrate.rb

clean:
	rm -rf $(VENV)
