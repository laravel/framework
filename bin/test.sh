#!/usr/bin/env bash

docker-compose down -t 0 &> /dev/null
docker-compose up -d

echo "Waiting for services to boot   ..."

if docker run -it --rm registry.gitlab.com/grahamcampbell/php:7.4-base -r "\$tries = 0; while (true) { try { \$tries++; if (\$tries > 30) { throw new RuntimeException('MySQL never became available'); } sleep(1); new PDO('mysql:host=docker.for.mac.localhost;dbname=forge', 'root', '', [PDO::ATTR_TIMEOUT => 3]); break; } catch (PDOException \$e) {} }"; then
    if docker run -it -w /data -v ${PWD}:/data:delegated --entrypoint vendor/bin/phpunit \
       --env CI=1 --env DB_HOST=docker.for.mac.localhost --env DB_USERNAME=root \
       --env REDIS_HOST=docker.for.mac.localhost --env REDIS_PORT=6379 \
       --env MEMCACHED_HOST=docker.for.mac.localhost --env MEMCACHED_PORT=11211 \
       --rm registry.gitlab.com/grahamcampbell/php:7.4-base "$@"; then
        docker-compose down -t 0
    else
        docker-compose down -t 0
        exit 1
    fi
else
    docker-compose logs
    docker-compose down -t 0 &> /dev/null
    exit 1
fi
