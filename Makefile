.SILENT:

.PHONY: setup down-clear shell lint-show lint test

DC = docker-compose run --rm dev

all: setup

setup:
	$(DC) composer install

down-clear:
	docker-compose down --remove-orphans -v

shell:
	$(DC) bash

lint-show:
	$(DC) composer lint:show

lint:
	$(DC) composer lint:fix

test:
	$(DC) composer test