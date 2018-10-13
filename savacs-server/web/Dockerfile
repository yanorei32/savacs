FROM php:7-apache

RUN docker-php-ext-install pdo_mysql

RUN set ex; \
    \
    savedAptMark="$(apt-mark showmanual)"; \
    apt-get update; \
    apt-get install -y --no-install-recommends wget; \
    rm -rf /var/lib/apt/lists/*; \
    \
    wget -O ffmpeg.tar.xz 'http://johnvansickle.com/ffmpeg/releases/ffmpeg-release-64bit-static.tar.xz'; \
    tar Jxf ffmpeg.tar.xz; \
    rm -rf ffmpeg.tar.xz; \
    cp ./ffmpeg-*-64bit-static/ffprobe /usr/local/bin/; \
    rm -r ./ffmpeg-*-64bit-static; \
    \
    apt-mark auto '.*' > /dev/null; \
    [ -z "$savedAptMark" ] || apt-mark manual $savedAptMark; \
    apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false;

RUN apt-get update && apt-get install -y --no-install-recommends \
            imagemagick \
        && rm -rf /var/lib/apt/lists/*

RUN mkdir /var/www/contents
RUN mkdir /var/www/contents/selfy_images
RUN mkdir /var/www/contents/record_voices
RUN chown -R www-data:www-data /var/www/contents

COPY php.ini /usr/local/etc/php/
COPY 000-default.conf /etc/apache2/sites-enabled/

