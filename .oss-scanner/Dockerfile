# Laravel, for Anthropic's OSS Scanner (https://red.anthropic.com/oss-scanner/)
#
# /src    the laravel/framework checkout, with Composer dev dependencies (PHPUnit, Orchestra Testbench) installed
#
# Network access is only available while this image builds. Everything the test suite needs is installed here.
# Run `laravel-services-start` to start Redis and Memcached. PHPUnit uses in-memory SQLite by default.
FROM php:8.5-cli-trixie

ENV DEBIAN_FRONTEND=noninteractive \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1 \
    COMPOSER_MEMORY_LIMIT=-1 \
    COMPOSER_ROOT_VERSION=13.x-dev

RUN apt-get update && apt-get install -y --no-install-recommends \
      ca-certificates git unzip curl procps redis-server memcached \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# The base image already provides ctype, curl, dom, filter, hash, mbstring, openssl, pdo_sqlite, session and tokenizer.
RUN install-php-extensions bcmath ftp gd gmp igbinary imagick intl memcached msgpack pcntl pdo_mysql redis zip

RUN { echo 'memory_limit=-1'; \
      echo 'error_reporting=E_ALL'; \
    } > /usr/local/etc/php/conf.d/oss-scanner.ini

# laravel-services-start starts Redis and Memcached. The container has no init system. You can run it again safely.
RUN printf '%s\n' '#!/bin/sh' \
      'pgrep -x redis-server >/dev/null || redis-server --daemonize yes --dir /tmp' \
      'pgrep -x memcached >/dev/null || memcached -d -u nobody' \
      > /usr/local/bin/laravel-services-start \
    && chmod +x /usr/local/bin/laravel-services-start

# scanner contract: the checkout lives inside the image, at /src
COPY . /src
WORKDIR /src

# Install dependencies the same way CI does. COMPOSER_ROOT_VERSION replaces CI's `composer config version`, so
# composer.json stays unchanged. composer.lock and vendor/ are already in .gitignore.
RUN composer update --prefer-stable --prefer-dist --no-progress

# Smoke-test the image. A failing test does not stop the build.
RUN laravel-services-start \
    && (vendor/bin/phpunit --no-coverage tests/Encryption tests/Cookie tests/Routing \
          || echo "WARNING: phpunit reported failures")
