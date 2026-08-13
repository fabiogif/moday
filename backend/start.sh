#!/bin/bash
set -e

echo "Running database migrations..."
php artisan migrate --force

echo "Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Starting web server..."
exec heroku-php-apache2 public/
