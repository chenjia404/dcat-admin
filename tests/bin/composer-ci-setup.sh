#!/usr/bin/env bash

# Packagist 安全策略会拦截带公告的 laravel/framework 版本，导致 create-project 无法完成。
# 仅用于 CI Dusk 矩阵安装测试应用，不影响本包发布到 Packagist 的依赖声明。
composer config --global policy.advisories.block false
