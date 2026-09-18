#!/usr/bin/env bash

cd ./laravel-tests
export DISPLAY=:99.0

sudo chmod -R 0755 ./vendor/laravel/dusk/bin/

# 等待 artisan 应用可启动
php artisan serve --host=127.0.0.1 --port=8300 > /dev/null 2>&1 &
for _ in $(seq 1 30); do
    if curl -sf http://127.0.0.1:8300 > /dev/null; then
        break
    fi
    sleep 1
done
