FROM php:8.2-apache

RUN docker-php-ext-install mysqli

COPY . /var/www/html/

# Railway provides $PORT; make Apache listen on it.
CMD sed -i "s/80/${PORT:-80}/g" /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf \
    && apache2-foreground
