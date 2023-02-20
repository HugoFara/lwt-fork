FROM php:8.1-apache-buster

# creating config file php.ini 
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" && \
    echo 'mysqli.allow_local_infile = On' >> "$PHP_INI_DIR/php.ini"

RUN docker-php-ext-install pdo pdo_mysql mysqli

COPY . /var/www/html/

# creating connect.inc.php
ARG DB_HOSTNAME=${DB_HOSTNAME}
ARG DB_USER=${DB_USER}
ARG DB_PASSWORD=${DB_PASSWORD}
ARG DB_DATABASE=${DB_DATABASE}
RUN printf '<?php\n$server = "%s";\n$userid = "%s";\n$passwd = "%s";\n$dbname = "%s";\n?>' "$DB_HOSTNAME" "$DB_USER" "$DB_PASSWORD" "$DB_DATABASE" > /var/www/html/connect.inc.php
