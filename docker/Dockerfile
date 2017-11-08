FROM php:7.1.11-apache
RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libmcrypt-dev \
        libpng-dev \
    && docker-php-ext-install -j$(nproc) iconv mcrypt \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo pdo_mysql
COPY php-apache/attribute-grabber.conf /etc/apache2/sites-available/attribute-grabber.conf
RUN a2dissite 000-default.conf && rm /etc/apache2/sites-available/000-default.conf
RUN a2ensite attribute-grabber.conf && service apache2 restart