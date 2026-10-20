FROM php:8.2-apache

RUN docker-php-ext-install mysqli

# Match XAMPP: buffer output so session_start()/header() still work after stray output.
RUN printf "output_buffering = 4096\ndate.timezone = Asia/Manila\n" > /usr/local/etc/php/conf.d/app.ini

COPY . /var/www/html/

# Railway can end up with several Apache MPMs enabled ("AH00534: More than one MPM loaded");
# keep only prefork (required by mod_php). Then make Apache listen on Railway's $PORT.
CMD rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.* \
    && a2enmod mpm_prefork >/dev/null \
    && sed -i "s/^Listen .*/Listen ${PORT:-80}/" /etc/apache2/ports.conf \
    && sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT:-80}>/" /etc/apache2/sites-available/000-default.conf \
    && apache2-foreground
