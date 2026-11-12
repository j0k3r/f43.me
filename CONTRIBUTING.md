# Contributing

Contributions are welcome, of course.

## Setting up an Environment

You locally need:

 - PHP >= 8.2 (with `pdo_mysql` or `pdo_pgsql`) with [Composer](https://getcomposer.org/) installed
 - Docker (to run the database)

Install deps:

```
composer i
```

Then you can use Docker to launch the database (used for test or dev):

```
docker run -d --name f43me-mysql -e MYSQL_ALLOW_EMPTY_PASSWORD=yes -p 3306:3306 mariadb:latest
```

## Running the app

Create the database:

```
php bin/console doctrine:database:create --env=dev --if-not-exists
php bin/console doctrine:schema:create --env=dev
```

Generate a hashed password and define it in `.env.local` for `ADMINPASS`:

```
php bin/console security:hash-password --empty-salt
```

Launch the server:

```
php -d variables_order=EGPCS -S localhost:8888 public/index.php
```

Browse: http://localhost:8888


## Running Tests

You can setup the database and the project using:

```
make prepare
```

Once it's ok, launch tests:

```
php bin/phpunit
```

## Linting

Linter is used only on PHP files:

```
php bin/php-cs-fixer fix
php bin/phpstan analyse
php bin/rector
```
