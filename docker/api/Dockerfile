# PHP-FPM runtime for the Symfony API.
FROM php:8.4-fpm

ARG APP_UID=1000
ARG APP_GID=1000

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libicu-dev \
        libpq-dev \
        libzip-dev \
        postgresql-client \
    && docker-php-ext-install -j"$(nproc)" intl pdo_pgsql zip opcache \
    && pecl install redis xdebug \
    && docker-php-ext-enable redis xdebug \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY php.ini /usr/local/etc/php/conf.d/zz-joblog.ini

# Align the built-in www-data user with the host user so bind-mounted files
# stay writable without permission juggling.
RUN groupmod -g "${APP_GID}" www-data \
    && usermod -u "${APP_UID}" -g "${APP_GID}" www-data \
    && mkdir -p /app /tmp/composer \
    && chown -R www-data:www-data /app /tmp/composer

WORKDIR /app

USER www-data

EXPOSE 9000

CMD ["php-fpm"]
