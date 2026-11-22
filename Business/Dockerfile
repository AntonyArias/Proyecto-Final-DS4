FROM php:8.5-apache
WORKDIR /var/www/html

RUN a2enmod rewrite && a2enmod actions
RUN apt-get update && apt-get install -y --no-install-recommends unzip libonig-dev \
    && docker-php-ext-install pdo_mysql mysqli mbstring \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY ./Business/composer.* ./
RUN composer install --no-interaction --prefer-dist --no-progress
COPY ./Business/public ./public
COPY ./Business/src ./src
COPY ./Business/tests ./tests
RUN sed -i 's@DocumentRoot /var/www/html@DocumentRoot /var/www/html/public@' /etc/apache2/sites-available/000-default.conf \
    && printf '<Directory /var/www/html/public>\nAllowOverride All\nRequire all granted\n</Directory>\n' > /etc/apache2/conf-available/proyecto.conf \
    && a2enconf proyecto \
    && printf 'upload_max_filesize=5M\npost_max_size=6M\ndisplay_errors=Off\n' > /usr/local/etc/php/conf.d/proyecto.ini \
    && mkdir -p /var/www/storage && chown www-data:www-data /var/www/storage
