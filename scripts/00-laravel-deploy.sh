#!/usr/bin/env bash
echo "Running Laravel deployment script..."

# Install composer dependencies
composer install --no-dev --working-dir=/var/www/html --optimize-autoloader --no-interaction

# Install npm dependencies and build assets
cd /var/www/html
npm ci --include=dev
npm run build

# Create SQLite database with proper permissions
mkdir -p /var/www/html/database
touch /var/www/html/database/database.sqlite

# Set proper permissions
# Directories: 755 (owner: rwx, group: r-x, others: r-x)
# Files: 644 (owner: rw-, group: r--, others: r--)
find /var/www/html/storage -type d -exec chmod 755 {} \;
find /var/www/html/storage -type f -exec chmod 644 {} \;
find /var/www/html/bootstrap/cache -type d -exec chmod 755 {} \;
find /var/www/html/bootstrap/cache -type f -exec chmod 644 {} \;
chmod 755 /var/www/html/database
chmod 664 /var/www/html/database/database.sqlite

# Clear Laravel caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Run migrations
php artisan migrate --force

# Seed the database
php artisan db:seed --class=RealFoodPricesSeeder --force

# Cache config and routes for production
php artisan config:cache
php artisan route:cache

echo "Laravel deployment complete!"
