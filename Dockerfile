# Base Image
FROM php:8.1-fpm

# Set the working directory
WORKDIR /var/www

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
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
    gd \
    zip

# Install Redis PHP extension
RUN pecl install redis \
    && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application code
COPY . .

# Install application dependencies
RUN composer install --optimize-autoloader --no-dev

# Set ownership for the entire application directory
RUN chown -R www-data:www-data /var/www

# Set specific permissions for writable directories
RUN chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Expose the application port
EXPOSE 9000

# Run PHP-FPM server
CMD ["php-fpm"]