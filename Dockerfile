#################################
##### Development Config ########
#################################
### Note: Code is auto-updated ##
#################################

FROM ubuntu:17.10

# Set Environment Variables
ENV DEBIAN_FRONTEND noninteractive
ENV APACHE_RUN_USER www-data
ENV APACHE_RUN_GROUP www-data
ENV APACHE_LOG_DIR /var/log/apache2
ENV COMPOSER_ALLOW_SUPERUSER 1

RUN apt-get update && apt-get install -qq -y apt-utils && apt-get install -qq -y supervisor cron nano composer rsyslog \
    apache2 php php-mysql php-bcmath php-bz2 php-xml php-gd php-gettext php-zip php-mbstring git \
    && apt-get clean

# Copy the Apache config
ADD docker/php-apache/config/attribute-grabber.conf /etc/apache2/sites-enabled/000-default.conf
## Add in the crontab configuration to start scheduled tasks
#ADD docker/php-apache/config/attribute-grabber.crontab.development /etc/cron.d/attribute-grabber 
RUN (crontab -l 2>/dev/null; echo "* * * * * root php /var/www/html/artisan schedule:run >> /var/log/cron.log") | crontab - && \
    touch /var/log/cron.log && chmod 0777 /var/log/cron.log && \
    echo "cron.* /var/log/cron.log" >> /etc/rsyslog.conf && \
    service rsyslog start && \
    service cron start
# Add in the Supervisord Configuration
ADD docker/php-apache/config/supervisord.conf /etc/supervisor/supervisord.conf
# Add the Auto-Update script to the 
ADD docker/php-apache/config/auto-update-git.sh /usr/local/bin/auto-update-git
# Clone the git repository
RUN rm -R /var/www/html/* && git clone https://github.com/thelastparadox/attribute-grabber.git /var/www/html/
# Copy Environment file to the html directory
ADD docker/php-apache/config/laravel.development.env /var/www/html/.env
# Make the Storage Directories Writeable
RUN chown -R www-data:www-data /var/www/html && chmod 0777 -R /var/www/html/storage
# Modify the Apache Re-write and restart Apache2
RUN a2enmod rewrite && a2enmod ssl
# Create SSL Cert
RUN mkdir /etc/apache2/ssl && openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout /etc/apache2/ssl/apache.key -out /etc/apache2/ssl/apache.crt -subj "/C=GB/ST=London/L=London/O=Global Security/OU=IT Department/CN=example.com"
# Add the Auto-Install script to the /usr/local/bin
ADD docker/php-apache/config/install-project.sh /usr/local/bin/install-project
# Add the Auto-Update script to the /usr/local/bin
ADD docker/php-apache/config/update-project.sh /usr/local/bin/update-project
# Call all the Composer files
RUN bash /usr/local/bin/install-project
# Update the owners for all files
#RUN chown -R www-data:www-data /var/www/html
# Define working directory.
WORKDIR /etc/supervisor/conf.d

EXPOSE 80
EXPOSE 443

# Define default command.
CMD ["supervisord", "-c", "/etc/supervisor/supervisord.conf"]