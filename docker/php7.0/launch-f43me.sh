# create vhost
bash /tmp/vhost.sh

# launch php
/etc/init.d/php7.0-fpm start

# cleanup build
cd /usr/share/nginx/html
cp app/config/parameters.yml.docker app/config/parameters.yml
chown www-data ./var/cache
chown www-data ./var/logs
chown www-data ./var/sessions
chmod -R 777 var/cache/ var/logs/ var/sessions/

# install mongdb adaptater for PHP7
composer require "alcaeus/mongo-php-adapter=^1.0.0" --ignore-platform-reqs

# install assets
npm install
./node_modules/gulp/bin/gulp.js

# setup database
php bin/console doctrine:mongodb:schema:create

exec nginx -g 'daemon off;'
