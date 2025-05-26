-include .env

.SILENT:

.PHONY: setup up share down shell lint-show lint test

DC = docker-compose run --rm php
EXPOSE_DASHBOARD_PORT ?= 4040

all: setup

setup:
	$(DC) composer install

up:
	docker-compose up -d

build:
	docker-compose build

share:
	docker-compose --profile share up -d && \
		echo && \
		echo "Expose Dashboard: http://localhost:${EXPOSE_DASHBOARD_PORT}"

down:
	docker-compose down --remove-orphans -v

shell sh:
	$(DC) bash

lint-show:
	$(DC) composer lint:show

lint:
	$(DC) composer lint:fix

test:
	$(DC) composer test