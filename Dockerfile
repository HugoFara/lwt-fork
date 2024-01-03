FROM php:8.1-apache-bullseye

LABEL org.opencontainers.image.source = "https://github.com/HugoFara/lwt"


# creating config file php.ini 
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" && \
    echo 'mysqli.allow_local_infile = On' >> "$PHP_INI_DIR/php.ini"

RUN docker-php-ext-install pdo pdo_mysql mysqli

COPY . /var/www/html/lwt

# creating connect.inc.php
ARG DB_HOSTNAME=db
ARG DB_USER=root
ARG DB_PASSWORD=root
ARG DB_DATABASE=learning-with-texts

RUN printf '<?php\n$server = "%s";\n$userid = "%s";\n$passwd = "%s";\n$dbname = "%s";\n?>' "$DB_HOSTNAME" "$DB_USER" "$DB_PASSWORD" "$DB_DATABASE" > /var/www/html/lwt/connect.inc.php

