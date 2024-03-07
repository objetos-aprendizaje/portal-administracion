FROM php:8.1.11-apache

RUN apt-get update && apt install --fix-missing -y \
		libpq-dev \
		libxml2-dev libbz2-dev zlib1g-dev \
		libsqlite3-dev libsqlite3-0 mariadb-client curl exif ftp \
		zip unzip git \
	&& curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
	&& apt install --no-install-recommends -y libpq-dev \
	&& docker-php-ext-install intl \
	&& docker-php-ext-install mysqli \
	&& docker-php-ext-enable mysqli \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-enable pdo_mysql

#	&& docker-php-ext-enable pdo \

COPY ./ /var/www/html/

ENV APACHE_DOCUMENT_ROOT /var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer self-update --2

# Permisos de la carpeta
WORKDIR /var/www
RUN chown -R www-data:www-data html

# Instalación de dependencias (composer)
WORKDIR /var/www/html

RUN composer update
RUN composer install

RUN npm install
RUN npm run build

RUN php artisan storage:link

RUN chown -R www-data:www-data /var/www/html

# Habilitar htaccess
RUN a2enmod rewrite
