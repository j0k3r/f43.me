FROM debian:jessie

# Install nginx, php7 & ruby stuff (for sass)
RUN apt-get update && apt-get install -y -q wget \
    && echo "deb http://packages.dotdeb.org jessie all" >> /etc/apt/sources.list \
    && echo "deb-src http://packages.dotdeb.org jessie all" >> /etc/apt/sources.list \
    && wget https://www.dotdeb.org/dotdeb.gpg \
    && apt-key add dotdeb.gpg && apt-get update \
    && apt-get install -y -q nginx php7.0 php7.0-fpm php7.0-curl php7.0-cli php7.0-xml php7.0-intl php7.0-iconv php7.0-json php7.0-tidy php7.0-mongodb php7.0-mongo php7.0-xdebug php7.0-mbstring php7.0-apcu php7.0-zip \
    && apt-get install -y -q libssl-dev build-essential vim git curl ruby2.1-dev rubygems

ADD ./fastcgi-php.conf /etc/nginx/snippets/fastcgi-php.conf
ADD ./php7.0/upstream-php.conf /etc/nginx/conf.d/php7.0-fpm.conf
ADD ./php7.0/vhost.sh /tmp/vhost.sh

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
