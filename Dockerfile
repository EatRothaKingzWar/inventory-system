FROM php:8.2-apache

# ដំឡើង PostgreSQL Driver
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# បើក Output Buffering ដើម្បីការពារកំហុស Headers already sent
RUN echo "output_buffering = 4096" > /usr/local/etc/php/conf.d/output-buffering.ini

# បើក mod_rewrite
RUN a2enmod rewrite
COPY . /var/www/html/

RUN mkdir -p /var/www/html/uploads/products \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 775 /var/www/html/uploads

EXPOSE 80