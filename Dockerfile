FROM php:8.2-apache

# Enable Apache rewrite for .htaccess
RUN a2enmod rewrite

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Ensure Apache passes environment variables to PHP
RUN echo "PassEnv VERIDIAN_DB_HOST VERIDIAN_DB_PORT VERIDIAN_DB_NAME VERIDIAN_DB_USER VERIDIAN_DB_PASSWORD VERIDIAN_SETUP_KEY" > /etc/apache2/conf-available/veridian-env.conf \
    && a2enconf veridian-env

# Set working directory and copy files
WORKDIR /var/www/html
COPY . /var/www/html/

# Copy the example config so it uses environment variables in the cloud
RUN cp config/config.example.php config/config.php

# Set Apache permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
