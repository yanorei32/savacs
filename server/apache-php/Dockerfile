FROM php:7-apache

RUN docker-php-ext-install pdo_mysql
RUN mkdir /var/www/contents
RUN mkdir /var/www/contents/selfy_images
RUN chown -R www-data:www-data /var/www/contents

RUN apt-get update && apt-get install -y --no-install-recommends \
            imagemagick \
        && rm -rf /var/lib/apt/lists/*

COPY php.ini /usr/local/etc/php/
COPY 000-default.conf /etc/apache2/sites-enabled/

