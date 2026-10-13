FROM php:8.3-apache

RUN apt-get update && apt-get install -y libicu-dev libzip-dev unzip git \
 && docker-php-ext-install intl mysqli pdo_mysql zip \
 && a2enmod rewrite \
 && rm -rf /var/lib/apt/lists/*

# CI4 sert le dossier public/
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

RUN printf '<Directory /var/www/html/public>\n\tAllowOverride All\n\tRequire all granted\n</Directory>\n' \
    > /etc/apache2/conf-available/ci4-override.conf \
 && a2enconf ci4-override

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html