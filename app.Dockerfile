FROM php:7.3-fpm
RUN apt-get update \
    && apt-get install -y wget curl libzip-dev zlib1g-dev libpng-dev libjpeg-dev libfreetype6-dev git mariadb-client libmagickwand-dev openssh-client sqlite3 --no-install-recommends
RUN docker-php-ext-install pdo_mysql zip \
    && pecl install imagick \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && docker-php-ext-install exif \
    && docker-php-ext-install intl \
    && docker-php-ext-enable imagick \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install gd \
    && echo "xdebug.remote_enable=on" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.profiler_enable_trigger=on" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && curl -sS https://getcomposer.org/installer \
                 | php -- --install-dir=/usr/local/bin --filename=composer
RUN wget https://github.com/wkhtmltopdf/packaging/releases/download/0.12.6-1/wkhtmltox_0.12.6-1.buster_amd64.deb -P /tmp
RUN DEBIAN_FRONTEND=noninteractive apt-get install -y xvfb libfontconfig fontconfig libpng16-16 libxrender1 xfonts-75dpi build-essential xorg
RUN dpkg -i /tmp/wkhtmltox_0.12.6-1.buster_amd64.deb