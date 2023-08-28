FROM php:7.4-apache

# Install dependencies
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpng-dev libjpeg-dev libxml2-dev libfreetype6-dev libzip-dev mariadb-client curl unzip \
    && pecl install xdebug-2.9.8 \
    && docker-php-ext-enable xdebug \
    && echo 'xdebug.remote_enable=1' >> /usr/local/etc/php/php.ini \
    && echo 'xdebug.remote_autostart=1' >> /usr/local/etc/php/php.ini \
    && rm -rf /var/lib/apt/lists/*

# Configure PHP Extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j "$(nproc)" gd opcache mysqli soap zip

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Download OpenCart
RUN curl -SL https://github.com/opencart/opencart/releases/download/3.0.3.6/opencart-3.0.3.6.zip -o opencart.zip \
    && unzip opencart.zip \
    && mv upload/* . \
    && mv config-dist.php config.php \
    && mv admin/config-dist.php admin/config.php \
    && rm -rf upload opencart.zip install.txt

# Create storage directory and set permissions
RUN mkdir -p /var/www/html/storage/logs \
    && chown -R www-data:www-data /var/www/html/storage \
    && chmod -R 755 /var/www/html/storage

# Set the ownership of all the files to the Apache user
RUN chown -R www-data:www-data /var/www/html

CMD ["apache2-foreground"]
