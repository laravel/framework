#!/usr/bin/env bash

config=${1:-"types"}

./vendor/bin/phpstan clear-result-cache
./vendor/bin/phpstan analyze -c ./phpstan.$config.neon.dist
