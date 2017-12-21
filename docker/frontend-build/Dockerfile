FROM node:9

# Install ruby-sass and ruby-compass
RUN apt-get update \
    && apt-get -y install --no-install-recommends ruby-sass ruby-compass \
    && apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*
