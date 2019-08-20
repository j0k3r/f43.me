.PHONY: build clean-local local clean prepare phpunit

build: prepare phpunit

clean-local:
	rm -rf build
	rm -rf var/cache

local: clean-local
	mkdir -p build/coverage
	mkdir -p build/logs
	mkdir -p var/cache
	php bin/console doctrine:mongodb:schema:create --env=test
	php bin/console doctrine:mongodb:fixtures:load --env=test -n
	php bin/console cache:clear --env=test

clean:
	rm -rf build/coverage
	rm -rf build/logs
	rm -rf var/cache

prepare: clean
	mkdir -p build/coverage
	mkdir -p build/logs
	mkdir -p var/cache
	composer install --no-interaction -o --prefer-dist
	php bin/console doctrine:mongodb:schema:create --env=test
	php bin/console doctrine:mongodb:fixtures:load --env=test -n
	php bin/console cache:clear --env=test

phpunit:
	php bin/simple-phpunit --coverage-html build/coverage
