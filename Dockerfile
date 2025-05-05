# Base Image
FROM php:8.1-fpm

# Set the working directory
WORKDIR /var/www

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    curl \
    unzip \
    git \
    redis-tools \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd

# Install Redis PHP extension
RUN pecl install redis \
    && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy composer files first for better caching
COPY composer.json composer.lock ./

# Install dependencies
RUN composer install --optimize-autoloader --no-dev --no-scripts

# Copy application code
COPY . .

# Cache Laravel configuration for better performance
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Set ownership for the entire application directory
RUN chown -R www-data:www-data /var/www

# Set specific permissions for writable directories
RUN chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Remove composer and php executable access to prevent users from running commands
RUN rm /usr/bin/composer \
    && mv /usr/local/bin/php /usr/local/bin/php-fpm-only \
    && ln -s /usr/local/bin/php-fpm-only /usr/local/bin/php-fpm \
    && echo '#!/bin/bash\necho "Direct PHP execution is disabled in this container."\nexit 1' > /usr/local/bin/php \
    && chmod +x /usr/local/bin/php

# Expose the application port
EXPOSE 9000

# Create entrypoint script
RUN echo '#!/bin/bash\necho "FSES Backend container started."\nexec php-fpm' > /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh

# Use entrypoint script
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]