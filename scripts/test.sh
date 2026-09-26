#!/bin/sh
set -eu

docker compose up -d db app
docker compose run --rm test
docker compose down -v
