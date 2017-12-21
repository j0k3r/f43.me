FROM phpdockerio/php72-fpm:latest
WORKDIR "/application"

RUN apt-get update \
    && apt-get -y --no-install-recommends install git php-mongodb \
    && apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

# Set a fixed user ID for www-data (which runs PHP) so that we can match the same user id on all PHP containers
# to avoid messing sessions
RUN usermod -u 1000 www-data
