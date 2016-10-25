FROM php:7.1-rc-fpm

# Install nginx & ruby stuff (for sass)
RUN apt-get update && apt-get install -y -q wget \
    && apt-get install -y -q nginx \
    && apt-get install -y -q libssl-dev build-essential vim git curl ruby2.1-dev rubygems

RUN apt-get update \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && pecl install zip \
    && docker-php-ext-enable zip

ADD ./fastcgi-php.conf /etc/nginx/snippets/fastcgi-php.conf
ADD ./php7.1/upstream-php.conf /etc/nginx/conf.d/php7.0-fpm.conf
ADD ./php7.1/vhost.sh /tmp/vhost.sh

RUN curl -o- https://raw.githubusercontent.com/creationix/nvm/v0.32.1/install.sh | bash

ENV NVM_DIR=/root/.nvm
ENV NODE_VERSION=v0.12.2

RUN . $HOME/.nvm/nvm.sh \
    && nvm install $NODE_VERSION \
    && nvm alias default $NODE_VERSION \
    && nvm use default

RUN gem install sass compass --no-ri --no-rdoc

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php -r "if (hash_file('SHA384', 'composer-setup.php') === 'e115a8dc7871f15d853148a7fbac7da27d6c0030b848d9b3dc09e2a0388afed865e6a3d6b3c0fad45c48e2b5fc1196ae') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
    && php composer-setup.php \
    && php -r "unlink('composer-setup.php');" \
    && mv composer.phar /usr/sbin/composer

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
