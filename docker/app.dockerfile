FROM php:7.0-fpm

RUN apt-get update && apt-get install -y libpng-dev libfreetype6-dev libjpeg62-turbo-dev zlib1g-dev libxrender1 libmcrypt-dev  git\
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/\
    && docker-php-ext-install zip pdo pdo_mysql bcmath mbstring mcrypt gd

RUN apt-get update \
  && apt-get install -y libmemcached11 libmemcachedutil2 build-essential libmemcached-dev libz-dev \
  && pecl install memcached \
  && echo extension=memcached.so >> /usr/local/etc/php/conf.d/memcached.ini \
  && apt-get remove -y build-essential libmemcached-dev libz-dev \
  && apt-get autoremove -y \
  && apt-get clean \
  && rm -rf /tmp/pear

RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/ \
    && ln -s /usr/local/bin/composer.phar /usr/local/bin/composer

RUN chmod 777 -R /tmp && chmod o+t -R /tmp