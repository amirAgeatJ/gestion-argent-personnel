#!/bin/bash
set -e

./bin/console asset-map:compile
./bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration