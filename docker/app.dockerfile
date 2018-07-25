FROM php:7.0-fpm

RUN apt-get update && apt-get install -y libpng-dev libfreetype6-dev libjpeg62-turbo-dev zlib1g-dev libxrender1 libmcrypt-dev  git\
    && docker-php-ext-install zip\
    && docker-php-ext-install pdo pdo_mysql\
    && docker-php-ext-install bcmath mbstring\
    && docker-php-ext-install mcrypt\
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/\
    && docker-php-ext-install gd

RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/ \
    && ln -s /usr/local/bin/composer.phar /usr/local/bin/composer

RUN chmod 777 -R /tmp && chmod o+t -R /tmp