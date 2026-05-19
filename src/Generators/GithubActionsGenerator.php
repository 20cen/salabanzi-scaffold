<?php

namespace Salabanzi\LaravelScaffold\Generators;

class GithubActionsGenerator extends BaseGenerator
{
    public function generate(): array
    {
        $content = <<<YAML
name: CI

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main, develop]

jobs:
  tests:
    name: Tests PHP
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: secret
          MYSQL_DATABASE: laravel_test
        ports:
          - 3306:3306
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3

    steps:
      - name: Checkout
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, pdo, pdo_mysql, zip, gd, bcmath
          coverage: none

      - name: Cache Composer
        uses: actions/cache@v3
        with:
          path: vendor
          key: composer-\${{ hashFiles('composer.lock') }}

      - name: Install dependencies
        run: composer install --no-interaction --prefer-dist --optimize-autoloader

      - name: Copy .env
        run: cp .env.example .env

      - name: Generate key
        run: php artisan key:generate

      - name: Configure database
        run: |
          php artisan config:clear
          php artisan migrate --force

      - name: Run tests
        run: php artisan test --parallel

  lint:
    name: Code style
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - run: composer install --no-interaction --prefer-dist
      - run: ./vendor/bin/pint --test
YAML;

        return [$this->writeFile('.github/workflows/ci.yml', $content)];
    }
}