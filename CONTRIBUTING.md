# Contributing

Contributions are welcome, of course.

## Setting up an Environment

You locally need:

 - PHP >= 7.4 (with `pdo_mysql` or `pdo_pgsql`) with [Composer](https://getcomposer.org/) installed
 - Node.js 20 (use `nvm install`) with Yarn installed
 - Docker (to run the database)

Install deps:

```
yarn
composer i
```

Then you can use Docker to launch the database (used for test or dev):

```
docker run -d --name f43me-mysql -e MYSQL_ALLOW_EMPTY_PASSWORD=yes -p 3306:3306 mysql:latest
```

## Running Tests

You can setup the database and the project using:

```
make prepare
```

Once it's ok, launch tests:

```
php bin/simple-phpunit -v
```

## Linting

Linter is used only on PHP files:

```
php bin/php-cs-fixer fix
php bin/phpstan analyse
```
