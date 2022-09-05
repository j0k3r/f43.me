.PHONY: build local prepare test

build: prepare test

local:
	rm -rf build
	mkdir -p build/coverage
	mkdir -p build/logs
	php bin/console doctrine:database:create --env=test --if-not-exists
	php bin/console doctrine:schema:create --env=test
	php bin/console doctrine:fixtures:load --env=test -n
	php bin/console cache:clear --env=test

prepare:
	rm -rf var/cache/*
	rm -rf build
	mkdir -p build/coverage
	mkdir -p build/logs
	composer install --no-interaction -o --prefer-dist
	php bin/console doctrine:database:create --env=test --if-not-exists
	php bin/console doctrine:schema:drop --force --env=test
	php bin/console doctrine:schema:create --env=test
	php bin/console doctrine:fixtures:load --env=test -n
	php bin/console cache:clear --env=test

test:
	php bin/simple-phpunit --coverage-html coverage

reset:
	php bin/console doctrine:schema:drop --force --env=test
	php bin/console doctrine:schema:create --env=test
	php bin/console doctrine:fixtures:load --env=test -n
