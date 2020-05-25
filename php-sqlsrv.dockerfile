FROM php:7.4.4-fpm-alpine as base

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/

RUN install-php-extensions sqlsrv pdo_sqlsrv

# Sql server necessary additions
RUN curl -O https://download.microsoft.com/download/e/4/e/e4e67866-dffd-428c-aac7-8d28ddafb39b/msodbcsql17_17.5.1.1-1_amd64.apk \
    && curl -O https://download.microsoft.com/download/e/4/e/e4e67866-dffd-428c-aac7-8d28ddafb39b/mssql-tools_17.5.1.2-1_amd64.apk \
    && apk add --allow-untrusted msodbcsql17_17.5.1.1-1_amd64.apk \
    && apk add --allow-untrusted mssql-tools_17.5.1.2-1_amd64.apk
# Fix for sql server
ENV LC_ALL=C

#install composer globally
#RUN curl -sSL https://getcomposer.org/installer | php \
#    && mv composer.phar /usr/local/bin/composer
#ENV COMPOSER_ALLOW_SUPERUSER 1
#RUN composer global require hirak/prestissimo --no-plugins --no-scripts

ARG TYPE_ENV=development

RUN mv "$PHP_INI_DIR/php.ini-${TYPE_ENV}" "$PHP_INI_DIR/php.ini" \
    && sed -e 's/max_execution_time = 30/max_execution_time = 600/' -i "$PHP_INI_DIR/php.ini" \
    && echo 'memory_limit = -1' > /usr/local/etc/php/conf.d/docker-php-memlimit.ini;

WORKDIR /var/www/html
