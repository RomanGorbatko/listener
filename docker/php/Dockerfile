ARG PHP_EXT_PATH=/usr/local/lib/php/extensions/no-debug-non-zts-20220829/
ARG PHP_DEFAULT_EXT="intl mbstring curl iconv gmp openssl gd zip sockets protobuf pcntl pgsql pdo_pgsql redis amqp dom xml xmlwriter amqp bcmath"
ARG PHP_MOD_DEPS="$PHPIZE_DEPS git icu-libs libpq libzip rabbitmq-c-utils shadow openssh-client zlib"

ARG PHP_ADDITIONAL_EXT

ARG WWW_DATA_UID=1000
ARG WWW_DATA_GID=1000

#============================== PHP-BUILDER =============================#
FROM php:8.3-cli-alpine3.17 as php-builder

ARG PHP_DEFAULT_EXT
ARG PHP_MOD_DEPS
ARG PHP_ADDITIONAL_EXT

RUN set -ex && apk add --no-cache --update ${PHP_MOD_DEPS}

RUN curl -sSLf \
         -o /usr/local/bin/install-php-extensions \
         https://github.com/mlocati/docker-php-extension-installer/releases/download/2.1.10/install-php-extensions && \
         chmod +x /usr/local/bin/install-php-extensions

RUN install-php-extensions ${PHP_DEFAULT_EXT} ${PHP_ADDITIONAL_EXT}

#================================== OSV ====================================#
FROM golang:alpine as osv-builder

RUN GOBIN=/usr/bin/ go install github.com/google/osv-scanner/cmd/osv-scanner@v1

#================================ PHP-CLI ==================================#
FROM php-builder as php-cli

ARG WWW_DATA_UID
ARG WWW_DATA_GID
ARG PHP_DEFAULT_EXT
ARG PHP_ADDITIONAL_EXT

COPY --from=php-builder ${PHP_EXT_PATH} ${PHP_EXT_PATH}

RUN docker-php-ext-enable ${PHP_DEFAULT_EXT} ${PHP_ADDITIONAL_EXT}; php -m

RUN usermod -u ${WWW_DATA_UID} www-data
RUN groupmod -g ${WWW_DATA_GID} www-data

#=================================== VENDOR =================================#
FROM composer:latest as vendor

WORKDIR /app
COPY ./composer.json composer.json
COPY ./composer.lock composer.lock

RUN composer install \
        --no-interaction \
        --no-plugins \
        --no-scripts \
        --no-dev \
        --ignore-platform-reqs \
        --prefer-dist
COPY . .
RUN composer dump-autoload

#=================================== RR =================================#
FROM php-cli as app-rr

COPY --from=spiralscout/roadrunner:latest /usr/bin/rr /usr/local/bin/rr
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

#================================== CLI =================================#
FROM php-cli as app-cli

COPY --from=osv-builder /usr/bin/osv-scanner /usr/bin/osv-scanner
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
COPY --from=node:18.16.0-alpine3.17 /usr/local/bin /usr/local/bin
COPY --from=node:18.16.0-alpine3.17 /usr/local/lib/node_modules /usr/local/lib/node_modules
