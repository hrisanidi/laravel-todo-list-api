# Setup Guide

## Requirements

- Docker
- PHP 8.1
- Laravel 8
- Composer

## Basic Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

## Run Automated Tests

```bash
php artisan test
```

## Generate Documentation
```bash
php artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"
php artisan l5-swagger:generate
```

## Start Database

Verify that it is started:

```bash
docker run -d \
  --name mysql \
  -e MYSQL_ROOT_PASSWORD=pass \
  -e MYSQL_DATABASE=todo_list_api \
  -p 3306:3306 \
  mysql
```

## Final Setup

```bash
php artisan queue:table
php artisan migrate
php artisan db:seed
php artisan passport:install
```

## Start Services

**Terminal 1:**
```bash
php artisan queue:work
```

**Terminal 2:**
```bash
php artisan serve
```
http://127.0.0.1:8080/api/documentation route won't render if there are 'deprecated' errors. Use PHP 8.1 to avoid that. 

## Access

- **api:** http://127.0.0.1:8000/api
- **docs:** http://127.0.0.1:8080/api/documentation
