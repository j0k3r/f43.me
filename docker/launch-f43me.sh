# create vhost
bash /tmp/vhost.sh

echo "Launch PHP..."
php-fpm -D

echo "Cleanup build..."
cd /usr/share/nginx/html
cp app/config/parameters.yml.docker app/config/parameters.yml
chown www-data ./var/cache
chown www-data ./var/logs
chown www-data ./var/sessions
chmod -R 777 var/cache/ var/logs/ var/sessions/

# install mongdb adaptater for PHP7
composer require "alcaeus/mongo-php-adapter=^1.0.0" --ignore-platform-reqs

# re-source the bashrc, so nvm will be loaded
. /root/.bashrc

echo "Installing assets..."
npm install
./node_modules/gulp/bin/gulp.js

echo "Setup database..."
php bin/console doctrine:mongodb:schema:create

echo "Ready 🚀"
exec nginx -g 'daemon off;'
