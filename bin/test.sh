#!/usr/bin/env bash

php="8.0"

while true; do
  case "$1" in
    --php ) php=$2; shift 2;;
    -- ) shift; break ;;
    * ) break ;;
  esac
done

echo "Checking whether docker is running"

if ! docker info > /dev/null 2>&1; then
  echo "Please start docker first."
  exit 1
fi

echo "Ensuring services required by the tests are running"

docker-compose up -d --force-recreate --remove-orphans

echo "Waiting until database is available"

docker run -it \
    --add-host=host.docker.internal:host-gateway \
    --rm "registry.gitlab.com/grahamcampbell/php:$php-base" \
    -r "ini_set(\"default_socket_timeout\", 1); \$tries = 0; while (true) { \$tries++; try { new PDO('mysql:host=host.docker.internal;dbname=forge', 'root', '', [PDO::ATTR_TIMEOUT => 1]); echo \"Connected on {\$tries}. attempt\\r\\n\"; break; } catch (PDOException \$e) { echo \"Failed to connect on {\$tries}. attempt. {\$e->getMessage()}\\r\\n\"; } if (\$tries > 60) { throw new Exception(); } sleep(1); }"

if [ "$?" -ne 0 ]; then
    echo "MySQL never became available"
    echo "Shutting down services"
    docker-compose down -t 0
    exit 1
fi

echo "Running composer update"

docker run -it -w /data -v ${PWD}:/data:delegated -u $(id -u ${USER}):$(id -g ${USER}) \
    --entrypoint composer \
    --add-host=host.docker.internal:host-gateway \
    --rm "registry.gitlab.com/grahamcampbell/php:$php-base" \
    update --no-cache --no-interaction

if [ "$?" -ne 0 ]; then
    echo "Failed to install packages"
    echo "Shutting down services"
    docker-compose down -t 0
    exit 1
fi

echo "Running tests"

docker run -it -w /data -v ${PWD}:/data:delegated -u $(id -u ${USER}):$(id -g ${USER}) \
    --entrypoint vendor/bin/phpunit \
    --add-host=host.docker.internal:host-gateway \
    --env CI=1 \
    --env DB_HOST=host.docker.internal \
    --env DB_PORT=3306 \
    --env DB_USERNAME=root \
    --env DYNAMODB_ENDPOINT=host.docker.internal:8000 \
    --env DYNAMODB_CACHE_TABLE=cache \
    --env AWS_ACCESS_KEY_ID=dummy \
    --env AWS_SECRET_ACCESS_KEY=dummy \
    --env REDIS_HOST=host.docker.internal \
    --env REDIS_PORT=6379 \
    --env MEMCACHED_HOST=host.docker.internal \
    --env MEMCACHED_PORT=11211 \
    --rm "registry.gitlab.com/grahamcampbell/php:$php-base" "$@"

echo "Shutting down services"

docker-compose down -t 0
