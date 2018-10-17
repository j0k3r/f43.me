FROM php:7.2-fpm-alpine
WORKDIR "/application"

RUN apk --no-cache add pcre-dev ${PHPIZE_DEPS} \
  && pecl install mongodb xdebug \
  && docker-php-ext-enable xdebug \
  && docker-php-ext-enable mongodb \
  && apk del pcre-dev ${PHPIZE_DEPS}

# Set a fixed user ID for www-data (which runs PHP) so that we can match the same user id on all PHP containers
# to avoid messing sessions
RUN apk --no-cache add shadow && \
    usermod -u 1000 www-data

RUN apk --update add git openssh && \
    rm -rf /var/lib/apt/lists/* && \
    rm /var/cache/apk/*
