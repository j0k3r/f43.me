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

# re-source the bashrc, so nvm will be loaded
. /root/.bashrc

echo "Installing assets..."
npm install
./node_modules/gulp/bin/gulp.js

echo "Setup database..."
php bin/console doctrine:mongodb:schema:create

echo "Ready 🚀 http://localhost:8100/app_dev.php/"
exec nginx -g 'daemon off;'
