DC := docker compose

.PHONY: init build up down logs bash composer artisan migrate npm-install vite dev workers

## Build PHP image
build:
	$(DC) build app

## First-time: create Laravel in project root (keeps compose.yaml & docker/)
init: build
	@if [ -f artisan ]; then echo "artisan already exists — init skipped."; exit 1; fi
	$(DC) run --rm --no-deps app composer create-project laravel/laravel _laravel_new "~12.0" --no-interaction
	$(DC) run --rm --no-deps app sh -c 'rm -rf _laravel_new/docker && rm -f _laravel_new/compose.yaml _laravel_new/docker-compose.yml _laravel_new/Dockerfile _laravel_new/.dockerignore 2>/dev/null; cp -a _laravel_new/. . && rm -rf _laravel_new'
	$(DC) run --rm --no-deps app sh /var/www/html/scripts/patch-env-docker.sh
	$(DC) run --rm --no-deps app php artisan key:generate --force
	@echo "Done. Run: make up && make migrate"

## Start app (nginx + php-fpm + mysql + queue + scheduler)
up:
	$(DC) up -d mysql app nginx queue scheduler

## Stop containers
down:
	$(DC) down

logs:
	$(DC) logs -f

bash:
	$(DC) exec app bash

# Приклад: make composer CMD='install'
composer:
	$(DC) exec app composer $(CMD)

# Приклад: make artisan CMD='migrate'
artisan:
	$(DC) exec app php artisan $(CMD)

migrate:
	$(DC) exec app php artisan migrate

npm-install:
	$(DC) run --rm --no-deps node sh -c "npm install"

## Vite dev server (profile dev)
vite:
	$(DC) --profile dev up vite

## nginx + php + mysql + queue + scheduler + vite
dev:
	$(DC) --profile dev up -d mysql app nginx queue scheduler vite
