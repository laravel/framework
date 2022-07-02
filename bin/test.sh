#!/usr/bin/env bash

down=false
php="8.0"

while true; do
  case "$1" in
    --down ) down=true; shift ;;
    --php ) php=$2; shift 2;;
    -- ) shift; break ;;
    * ) break ;;
  esac
done

if $down; then
    docker-compose down -t 0

    exit 0
fi

echo "Ensuring docker is running"

if ! docker info > /dev/null 2>&1; then
  echo "Please start docker first."
  exit 1
fi

echo "Ensuring services are running"

docker-compose up -d

if docker run --add-host=host.docker.internal:host-gateway -it --rm "registry.gitlab.com/grahamcampbell/php:$php-base" -r "\$tries = 0; while (true) { try { \$tries++; if (\$tries > 30) { throw new RuntimeException('MySQL never became available'); } sleep(1); new PDO('mysql:host=host.docker.internal;dbname=forge', 'root', '', [PDO::ATTR_TIMEOUT => 3]); break; } catch (PDOException \$e) {} }"; then
    echo "Running tests"

    if docker run -it -w /data -v ${PWD}:/data:delegated -u $(id -u ${USER}):$(id -g ${USER}) \
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
       --rm "registry.gitlab.com/grahamcampbell/php:$php-base" "$@"; then
        exit 0
    else
        exit 1
    fi
else
    docker-compose logs
    exit 1
fi
