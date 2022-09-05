# create vhost
bash /tmp/vhost.sh

echo "Launch PHP..."
php-fpm -D

echo "Cleanup build..."
cd /usr/share/nginx/html
chown -R www-data var
chown -R www-data public
chmod -R 777 var/

# re-source the bashrc, so nvm will be loaded
. /root/.bashrc

echo "Installing assets..."
yarn install
yarn dev

echo "Setup database..."
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:schema:drop --force
php bin/console doctrine:schema:create

echo "Ready 🚀 http://localhost:8100/index.php/"
exec nginx -g 'daemon off;'
