#!/usr/bin/env bash

# GitHub Actions 上的 Composer 2.7+ 默认拦截含安全公告的包版本，
# 导致无法 create-project 安装 Laravel 5~9 等旧骨架。Dusk 矩阵需关闭该策略。
composer config --global policy.advisories.block false
