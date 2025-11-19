FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    sqlite3 \
    libsqlite3-dev \
    nodejs \
    npm

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_sqlite mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . /var/www

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Install npm dependencies and build assets
RUN npm ci --include=dev
RUN npm run build

# Create SQLite database directory and file
RUN mkdir -p /var/www/database && \
    touch /var/www/database/database.sqlite && \
    chmod -R 775 /var/www/database && \
    chmod -R 775 /var/www/storage

# Expose port
EXPOSE 8080

# Start application
CMD php artisan migrate --force && \ 
    php artisan config:clear && \
    php artisan cache:clear && \
    php artisan storage:link && \
    php artisan db:seed --class=RealFoodPricesSeeder --force && \
    php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
