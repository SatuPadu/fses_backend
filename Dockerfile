# Base image
FROM php:8.1-fpm

# Set working directory
WORKDIR /var/www

# Install dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    mariadb-client \
    && docker-php-ext-install pdo_mysql zip

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy application
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www

# Expose port
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm"]