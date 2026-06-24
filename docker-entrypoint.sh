#!/bin/sh
set -e

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist
    composer dump-autoload --optimize
fi

if [ ! -f .env ]; then
    cp .env.example .env
fi

# limpa o cache de scan para garantir re-scan limpo de anotações (#[Consumer], #[Listener], etc.)
rm -rf runtime/container
mkdir -p runtime/container/proxy

exec "$@"
