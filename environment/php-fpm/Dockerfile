FROM php:7.3-fpm-stretch

RUN apt-get update -q -y \
    && apt-get install -q -y --no-install-recommends \
        git curl zlib1g-dev libicu-dev g++ \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl pdo pdo_mysql

RUN usermod -u 1000 www-data

CMD php-fpm
