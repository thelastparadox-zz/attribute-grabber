FROM ubuntu:17.10

RUN apt-get update && apt-get install -y apache2 php php-mysql php-bcmath php-bz2 php-xml php-gd php-gettext php-mbstring

COPY config/attribute-grabber.conf /etc/apache2/sites-available/attribute-grabber.conf
#COPY php-apache/php.ini /usr/local/etc/php/php.ini
RUN a2dissite 000-default.conf && rm /etc/apache2/sites-available/000-default.conf
RUN a2ensite attribute-grabber.conf && service apache2 restart