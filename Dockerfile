FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev \
    && docker-php-ext-install mbstring pdo_mysql \
    && apt-get purge -y --auto-remove libonig-dev \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

COPY . /var/www/html/
COPY docker-entrypoint.sh /usr/local/bin/fleetcare-entrypoint
RUN chmod +x /usr/local/bin/fleetcare-entrypoint

ENTRYPOINT ["fleetcare-entrypoint"]
CMD ["apache2-foreground"]
