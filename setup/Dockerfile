FROM php:8.3-apache

RUN docker-php-ext-install mysqli

WORKDIR /var/www/html
COPY . /var/www/html
COPY setup/docker-entrypoint.sh /usr/local/bin/immunicare-entrypoint

RUN chmod +x /usr/local/bin/immunicare-entrypoint \
    && a2enmod headers

ENTRYPOINT ["immunicare-entrypoint"]
