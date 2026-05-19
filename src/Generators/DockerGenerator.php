<?php

namespace Salabanzi\LaravelScaffold\Generators;

class DockerGenerator extends BaseGenerator
{
    public function generate(): array
    {
        $files = [];

        $files[] = $this->writeFile('Dockerfile', $this->buildDockerfile());
        $files[] = $this->writeFile('docker-compose.yml', $this->buildDockerCompose());
        $files[] = $this->writeFile('.dockerignore', $this->buildDockerIgnore());

        return $files;
    }

    private function buildDockerfile(): string
    {
        return <<<DOCKERFILE
FROM php:8.2-fpm-alpine

WORKDIR /var/www/html

# Extensions PHP
RUN apk add --no-cache \
    git curl zip unzip libpng-dev libjpeg-turbo-dev \
    libzip-dev oniguruma-dev && \
    docker-php-ext-install pdo pdo_mysql mbstring zip gd bcmath opcache

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Dépendances
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist --optimize-autoloader

# Code source
COPY . .

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN php artisan config:cache && php artisan route:cache && php artisan view:cache

EXPOSE 9000
CMD ["php-fpm"]
DOCKERFILE;
    }

    private function buildDockerCompose(): string
    {
        return <<<YAML
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: laravel_app
    restart: unless-stopped
    volumes:
      - .:/var/www/html
    depends_on:
      - db
      - redis
    environment:
      - APP_ENV=local
      - DB_HOST=db
      - REDIS_HOST=redis

  nginx:
    image: nginx:alpine
    container_name: laravel_nginx
    restart: unless-stopped
    ports:
      - "8000:80"
    volumes:
      - .:/var/www/html
      - ./docker/nginx.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - app

  db:
    image: mysql:8.0
    container_name: laravel_db
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: \${DB_DATABASE:-laravel}
      MYSQL_ROOT_PASSWORD: \${DB_PASSWORD:-secret}
      MYSQL_PASSWORD: \${DB_PASSWORD:-secret}
      MYSQL_USER: \${DB_USERNAME:-laravel}
    volumes:
      - db_data:/var/lib/mysql
    ports:
      - "3306:3306"

  redis:
    image: redis:alpine
    container_name: laravel_redis
    restart: unless-stopped
    ports:
      - "6379:6379"

volumes:
  db_data:
YAML;
    }

    private function buildDockerIgnore(): string
    {
        return <<<TXT
.git
.gitignore
node_modules
vendor
storage/logs/*
storage/framework/cache/*
storage/framework/sessions/*
storage/framework/views/*
.env
*.md
tests/
TXT;
    }
}