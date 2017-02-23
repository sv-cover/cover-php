FROM php:7.0-apache

# Install PDO pgsql drivers
RUN apt-get update \
    && apt-get install -y \
    	libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Install exif plugin (used for detecting embedded thumbnails amd data while adding photos)
RUN docker-php-ext-install exif

# Install gd (Todo: which is still used in the sticker map, should be replaced by imagemagick)
RUN apt-get update \
	&& apt-get install -y \
		libpng12-dev \
		libjpeg-dev \
	&& docker-php-ext-configure gd --with-jpeg-dir=/usr/lib \
	&& docker-php-ext-install gd

# Install Imagemagick plugin
RUN apt-get update \
	&& apt-get install -y \
		imagemagick \
		libmagickwand-dev \
		libfreetype6-dev \
	&& pecl install imagick

# Install bcmath (used by iban library stuff)
RUN docker-php-ext-install bcmath

# Install mbstring (user by http library stuff)
RUN docker-php-ext-install mbstring

# Install Composer
RUN apt-get update && apt-get install -y git zip
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Todo: install face recognition stuff

# Copy the application
COPY fonts locale /var/www/
COPY www/ /var/www/html/

# Create temporary folders :)
# Alternatively link these to a more persistent location
RUN mkdir -m 0777 -p /var/www/tmp/twig
RUN mkdir -m 0777 -p /var/www/tmp/stickers
RUN mkdir -m 0777 -p /var/www/tmp/profiles

# Install stuff in /var/www/vendor
WORKDIR /var/www/
COPY composer.* /var/www/
RUN composer install --no-plugins --no-scripts --no-dev

