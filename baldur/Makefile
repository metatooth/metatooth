.PHONY: docker up down logs migrate

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
