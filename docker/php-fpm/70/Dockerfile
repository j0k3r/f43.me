FROM phpdockerio/php7-fpm:latest
WORKDIR "/application"

RUN apt-get update \
    && apt-get -y --no-install-recommends install git php7.0-xdebug \
    && curl -O http://ftp.us.debian.org/debian/pool/main/o/openssl/libssl1.1_1.1.0f-3+deb9u1_amd64.deb \
    && curl -O https://launchpadlibrarian.net/347757963/php-mongodb_1.3.3-1+ubuntu16.04.1+deb.sury.org+1_amd64.deb \
    && dpkg -i *.deb \
    && rm *.deb \
    && apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

# Set a fixed user ID for www-data (which runs PHP) so that we can match the same user id on all PHP containers
# to avoid messing sessions
RUN usermod -u 1000 www-data
