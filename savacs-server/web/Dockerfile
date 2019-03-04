FROM php:apache

RUN set ex; \
    docker-php-ext-install pdo_mysql; \
    \
    apt-get update; \
    apt-get install -y --no-install-recommends imagemagick; \
    savedAptMark="$(apt-mark showmanual)"; \
    apt-get install -y --no-install-recommends wget; \
    \
    wget -O ffmpeg.tar.xz 'https://johnvansickle.com/ffmpeg/releases/ffmpeg-release-amd64-static.tar.xz'; \
    tar Jxf ffmpeg.tar.xz; \
    rm -rf ffmpeg.tar.xz; \
    cp ./ffmpeg-*-amd64-static/ffprobe /usr/local/bin/; \
    rm -r ./ffmpeg-*-amd64-static; \
    \
    apt-mark auto '.*' > /dev/null; \
    [ -z "$savedAptMark" ] || apt-mark manual $savedAptMark; \
    apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false; \
    \
    rm -rf /var/lib/apt/lists/*; \
    \
    mkdir /var/www/contents; \
    mkdir /var/www/contents/selfy_images; \
    mkdir /var/www/contents/record_voices; \
    mkdir /var/www/contents/motion_images; \
    chown -R www-data:www-data /var/www/contents

COPY php.ini /usr/local/etc/php/php.ini
COPY 000-default.conf /etc/apache2/sites-enabled/

