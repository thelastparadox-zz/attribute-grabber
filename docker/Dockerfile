FROM php:7.1.11-apache
MAINTAINER thelastparadox@gmail.com
COPY php-apache/attribute-grabber.conf /etc/apache2/sites-available/attribute-grabber.conf
RUN a2dissite 000-default.conf && rm /etc/apache2/sites-available/000-default.conf
RUN a2ensite attribute-grabber.conf && service apache2 restart