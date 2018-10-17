#!/bin/sh

# Fix permissions inside of containers so that the other php containers can write into the var/ folder
chown www-data -Rf ./var
chmod 777 -Rf ./var

# Undo permission changes to .gitkeep files within var/
git checkout ./var

# Load up schema in mongo
php bin/console doctrine:mongodb:schema:create

echo "Schema created, container will now die. 👋"
