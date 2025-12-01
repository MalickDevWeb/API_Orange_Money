#!/bin/bash

# Wait for database to be ready
echo "Waiting for database..."
while ! pg_isready -h $DB_HOST -p $DB_PORT -U $DB_USERNAME; do
  echo "Database not ready, waiting..."
  sleep 2
done

echo "Database is ready!"

# Run migrations
php artisan migrate --force

# Run seeders
php artisan db:seed --force

# Clear and cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start Laravel server (Render provides PORT environment variable)
php artisan serve --host=0.0.0.0 --port=${PORT:-10000}
