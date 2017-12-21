#!/bin/sh

# Fix permissions inside of containers so that the other php containers can write into the var/ folder
chown www-data -Rf ./var
chmod 777 -Rf ./var

# Undo permission changes to .gitkeep files within var/
git checkout ./var

# The doctrine/mongodb bundle in use here is only compatible with the old style mongo extension
# PHP 7+ needs a polyfill, but we don't want it in the regular composer.json
composer require "alcaeus/mongo-php-adapter=^1.0.0" --ignore-platform-reqs

# Load up schema in mongo
php bin/console doctrine:mongodb:schema:create || echo "Schema already created"
