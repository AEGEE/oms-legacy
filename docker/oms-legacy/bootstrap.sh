#!/bin/bash

if [ -f "/var/shared/strapstate/oms-legacy" ]
then
	echo "[bootstrap] Bootstrap-file found, not executing bootstrap script"
else
	echo "[bootstrap] Bootstrapping..."

    if [ ! -f /usr/app/src/.env ]; then
      echo "[bootstrap] No .env file found, copying from .env.example"
      cp /usr/app/src/.env.example /usr/app/src/.env
    fi

	cd /usr/app/src/
    echo -e "[bootstrap] composer install"
    composer install --quiet       || { echo "[core bootstrap] Error at composer install"; exit 10; }
    echo -e "[bootstrap] artisan config:clear"
	php artisan config:clear -q    || { echo "[core bootstrap] Error at config:clear"; exit 10; }
    echo -e "[bootstrap] artisan clear-compiled"
	php artisan clear-compiled -q  || { echo "[core bootstrap] Error at clear-compiled"; exit 10; }
    echo -e "[bootstrap] artisan config:cache"
	php artisan config:cache -q    || { echo "[core bootstrap] Error at config:cache (1)"; exit 10; }
    echo -e "[bootstrap] artisan key:generate"
	php artisan key:generate -q    || { echo "[core bootstrap] Error at key:generate"; exit 10; }
    echo -e "[bootstrap] artisan config:cache"
	php artisan config:cache -q    || { echo "[core bootstrap] Error at config:cache (2)"; exit 10; }

	mkdir -p /var/shared/strapstate
	touch /var/shared/strapstate/oms-legacy

	echo "[bootstrap] Bootstrap finished"
fi
