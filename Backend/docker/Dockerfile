FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/*

# pdo_mysql, rewrite para el .htaccess y Xdebug
RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite \
    && apt-get update && apt-get install -y --no-install-recommends $PHPIZE_DEPS \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Las rutas del COPY son relativas al contexto (Backend/), no al Dockerfile
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/xdebug.ini /usr/local/etc/php/conf.d/zz-xdebug.ini

WORKDIR /var/www/html

COPY composer.json composer.lock* ./
RUN composer install --no-interaction --prefer-dist --no-autoloader --no-scripts

COPY . .
RUN composer dump-autoload --optimize \
    && chown -R www-data:www-data /var/www/html