#!/usr/bin/env bash

docker compose up --renew-anon-volumes --exit-code-from test
docker compose down -v
