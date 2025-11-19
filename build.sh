#!/usr/bin/env bash

# Exit on error
set -o errexit

echo "Starting build process..."

# Install Composer dependencies (production only, optimized)
echo "Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Install Node dependencies and build assets
echo "Installing Node dependencies..."
npm ci --include=dev

echo "Building frontend assets..."
npm run build

# Clear all Laravel caches
echo "Clearing Laravel caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Create SQLite database file if it doesn't exist
echo "Setting up SQLite database..."
mkdir -p database
touch database/database.sqlite
chmod 664 database/database.sqlite

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force

# Seed the database with real food prices
echo "Seeding database with food prices..."
php artisan db:seed --class=RealFoodPricesSeeder --force

echo "Build complete!"
