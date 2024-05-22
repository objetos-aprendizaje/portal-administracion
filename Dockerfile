FROM php:8.2.7-apache

RUN apt-get update && apt install --fix-missing -y \
		libpq-dev nano \
		libxml2-dev libbz2-dev zlib1g-dev \
		libsqlite3-dev libsqlite3-0 mariadb-client curl exif ftp \
		zip unzip git npm \
		libldap2-dev \
	&& apt install --no-install-recommends -y libpq-dev \
	&& docker-php-ext-install intl \
	&& docker-php-ext-install mysqli \
	&& docker-php-ext-enable mysqli \
	&& docker-php-ext-install pdo_mysql \
    && docker-php-ext-enable pdo_mysql \
    && docker-php-ext-install fileinfo \
    && docker-php-ext-enable fileinfo

# Instalación de librdkafka
RUN apt-get update \
    && apt-get install -y build-essential libssl-dev \
    && git clone https://github.com/edenhill/librdkafka.git \
    && cd librdkafka \
    && ./configure --prefix=/usr \
    && make \
    && make install

RUN rm -rf /var/lib/apt/lists/*

# Añadir el php.ini de producción ofrecido por la imagen.
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

RUN echo "upload_max_filesize = 32M"  > /usr/local/etc/php/conf.d/custom.ini

ENV APACHE_DOCUMENT_ROOT /var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer self-update --2

# Copiamos y damos los permisos a la carpeta
WORKDIR /var/www/html
COPY --chown=www-data:www-data . .
# RUN chown -R www-data:www-data /var/www/html

# Instalación de dependencias (composer)
RUN composer install
RUN npm install
RUN npm run build

# Generar un certificado autofirmado
RUN openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout /etc/ssl/private/apache-selfsigned.key -out /etc/ssl/certs/apache-selfsigned.crt -subj "/CN=localhost"

RUN a2enmod ssl

# Generar un certificado SSL autofirmado para el desarrollo. En producción, debería usar su propio certificado.
RUN openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout /etc/ssl/private/apache-selfsigned.key -out /etc/ssl/certs/apache-selfsigned.crt -subj "/CN=localhost"

# Habilitar el módulo SSL de Apache y el módulo de reescritura
RUN a2enmod ssl
RUN a2enmod rewrite

# Copiar la configuración de Apache SSL
COPY docker_files/000-default-ssl.conf /etc/apache2/sites-available/000-default-ssl.conf

# Habilitar el sitio SSL
RUN a2ensite 000-default-ssl

# Habilitar htaccess
RUN a2enmod rewrite headers

RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 775 /var/www/html
