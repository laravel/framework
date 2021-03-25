#!/usr/bin/env bash

docker-compose down -t 0 &> /dev/null
trap 'docker-compose down -t 0' EXIT

docker-compose -f docker-compose.yml -f docker-compose.tests.yml run tests