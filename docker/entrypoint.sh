#!/bin/bash

[ ! -d "vendor" ] && mkdir vendor

composer install

docker-php-entrypoint "php-fpm"
