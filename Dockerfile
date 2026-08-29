# syntax=docker/dockerfile:1

# Imagen base con PHP 8.3 y Apache.
# PHP 8.4 no se utiliza hasta que todas las dependencias del proyecto sean compatibles.
FROM php:8.3-apache

# Instala las dependencias necesarias para ejecutar la aplicación y generar PNG.
# Inkscape se utiliza únicamente para la salida type=png; SVG y JSON funcionan igualmente.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        fonts-dejavu-core \
        git \
        inkscape \
        libicu-dev \
        unzip \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" intl \
    && apt-get purge -y --auto-remove libicu-dev git unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Obtiene Composer desde la imagen oficial, sin instalarlo manualmente.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Directorio de trabajo de la aplicación.
WORKDIR /var/www/html

# Instala primero las dependencias para aprovechar la caché de capas de Docker.
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader

# Copia únicamente los archivos necesarios en la imagen de producción.
COPY src/ ./src/

# Configura Apache para servir el endpoint PHP desde src/.
# TOKEN y WHITELIST se reciben al iniciar el contenedor mediante docker run.
RUN a2enmod headers rewrite \
    && printf '%s\n' \
        '<VirtualHost *:80>' \
        '    ServerName localhost' \
        '    ServerTokens Prod' \
        '    ServerSignature Off' \
        '    DocumentRoot /var/www/html/src' \
        '    PassEnv TOKEN' \
        '    PassEnv WHITELIST' \
        '    <Directory /var/www/html/src>' \
        '        Options -Indexes' \
        '        AllowOverride None' \
        '        Require all granted' \
        '        Header always set Access-Control-Allow-Origin "*"' \
        '        Header always set Content-Security-Policy "default-src '\''none'\''; style-src '\''unsafe-inline'\''; img-src data:;"' \
        '        Header always set Referrer-Policy "no-referrer-when-downgrade"' \
        '        Header always set X-Content-Type-Options "nosniff"' \
        '    </Directory>' \
        '    ErrorLog ${APACHE_LOG_DIR}/error.log' \
        '    CustomLog ${APACHE_LOG_DIR}/access.log combined' \
        '</VirtualHost>' \
        > /etc/apache2/sites-available/000-default.conf

# Directorio de caché escribible por Apache.
RUN mkdir -p /var/www/html/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod 775 /var/www/html/cache

# El contenedor expone Apache en el puerto 80.
EXPOSE 80

# Comprueba que Apache puede responder durante la ejecución del contenedor.
HEALTHCHECK --interval=30s --timeout=10s --start-period=10s --retries=3 \
    CMD curl --fail --silent http://localhost/ || exit 1

CMD ["apache2-foreground"]
