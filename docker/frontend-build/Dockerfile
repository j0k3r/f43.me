FROM node:8-alpine

# Install ruby-sass and ruby-compass
RUN apk add --update \
    ruby \
    ruby-dev \
    libffi-dev \
    build-base

RUN gem install listen sass compass --no-document

RUN apk del build-base libffi-dev ruby-dev

RUN rm -rf /var/cache/apk/*
