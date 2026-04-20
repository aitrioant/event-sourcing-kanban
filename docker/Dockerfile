FROM php:8.4-fpm

# Install Xdebug
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Install db drivers
RUN apt-get update &&  \
    apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Remove cache and apt lists only needed during installation to reduce image size
RUN  apt-get clean && rm -rf /var/lib/apt/lists/*