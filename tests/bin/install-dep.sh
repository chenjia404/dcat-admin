#!/usr/bin/env bash

cp -f ./tests/resources/stubs/artisan ./laravel-tests/
cp -f ./tests/resources/stubs/ComposerConfigCommand.php ./laravel-tests/app/
mkdir ./laravel-tests/dcat-admin
cp -rf ./config ./laravel-tests/dcat-admin
cp -rf ./database ./laravel-tests/dcat-admin
cp -rf ./resources ./laravel-tests/dcat-admin
cp -rf ./src ./laravel-tests/dcat-admin
cp -rf ./tests ./laravel-tests/dcat-admin
cp -rf ./composer.json ./laravel-tests/dcat-admin
rm -rf ./laravel-tests/tests
cp -rf ./tests ./laravel-tests/tests
cp -f ./phpunit.dusk.xml ./laravel-tests
cp -f ./.env.testing ./laravel-tests/.env
cd ./laravel-tests
php artisan admin:composer-config
composer require chenjia404/dcat-admin:*@dev

LARAVEL_MAJOR=$(php -r "require 'vendor/autoload.php'; echo (int) explode('.', Illuminate\\Foundation\\Application::VERSION)[0];")
if [ "$LARAVEL_MAJOR" -ge 13 ]; then
    # Laravel 13 默认锁定 Guzzle 8；Packagist 上稳定版 Dusk 仍仅 ^7.5，需 8.x-dev（已合并 Guzzle 8 支持）
    composer require "laravel/dusk:8.x-dev@dev" --dev --with-all-dependencies
else
    composer require "laravel/dusk:*" --dev
fi
