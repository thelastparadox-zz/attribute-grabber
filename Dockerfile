FROM ubuntu:17.10
ENV DEBIAN_FRONTEND noninteractive
ENV APACHE_RUN_USER www-data
ENV APACHE_RUN_GROUP www-data
ENV APACHE_LOG_DIR /var/log/apache2

RUN apt-get update && apt-get install -y supervisor \
    apache2 php php-mysql php-bcmath php-bz2 php-xml php-gd php-gettext php-mbstring git \
    && apt-get clean

# Copy the Apache config
ADD docker/php-apache/config/attribute-grabber.conf /etc/apache2/sites-enabled/000-default.conf
#COPY php-apache/php.ini /usr/local/etc/php/php.ini

# Copy the code into the HTML folder
COPY . /var/www/html/

ADD docker/php-apache/config/supervisord.conf /etc/supervisor/supervisord.conf

# Define working directory.
WORKDIR /etc/supervisor/conf.d

EXPOSE 80
EXPOSE 443

# Define default command.
CMD ["supervisord", "-c", "/etc/supervisor/supervisord.conf"]