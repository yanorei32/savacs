FROM php:7-apache

RUN docker-php-ext-install pdo_mysql

COPY php.ini /usr/local/etc/php/
COPY 000-default.conf /etc/apache2/sites-enabled/


