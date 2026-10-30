# =====================================================================
# Imagen del sistema J.A.N.E. para producción (Render u otro hosting con Docker)
#   PHP 8.4 + Apache · extensiones pdo_pgsql, zip y gd · dependencias con Composer
# Apache sirve SOLO la carpeta public/: app/, config/, vendor/ y storage/ quedan fuera de la web.
# =====================================================================
FROM php:8.4-apache

# ---- Extensiones de PHP ----
#   pdo_pgsql: PostgreSQL · zip: leer .xlsx (PhpSpreadsheet) · gd: imágenes (PhpSpreadsheet y Dompdf)
RUN apt-get update \
 && apt-get install -y --no-install-recommends libpq-dev libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev unzip \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" pdo_pgsql zip gd \
 && rm -rf /var/lib/apt/lists/*

# ---- PHP y Apache ----
COPY docker/php.ini       /usr/local/etc/php/conf.d/zz-jane.ini
COPY docker/apache.conf   /etc/apache2/sites-available/000-default.conf
COPY docker/iniciar.sh    /usr/local/bin/iniciar-jane
RUN a2enmod rewrite headers deflate \
 && echo 'ServerName localhost' > /etc/apache2/conf-available/servername.conf \
 && a2enconf servername \
 && chmod +x /usr/local/bin/iniciar-jane

WORKDIR /var/www/jane

# ---- Dependencias (se instalan antes de copiar el código para aprovechar la caché) ----
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --optimize-autoloader --no-scripts

# ---- Código ----
COPY . .
RUN composer dump-autoload --no-dev --optimize \
 && mkdir -p storage/logs \
 && chown -R www-data:www-data storage

# Render indica el puerto en la variable PORT (por defecto 10000)
ENV PORT=10000 \
    APP_ENV=production \
    APP_DEBUG=false \
    APP_LOG=stderr
EXPOSE 10000

CMD ["iniciar-jane"]
