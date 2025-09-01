# Dockerfile multistage para PHP 8.4
FROM php:8.4-fpm-alpine AS base

# Instalar dependencias del sistema
RUN apk --no-cache add \
    postgresql-dev \
    icu-dev \
    libzip-dev \
    git \
    unzip \
    && docker-php-ext-configure intl \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        intl \
        zip \
        opcache \
        bcmath

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar usuario no root
ARG USER_ID=1000
ARG GROUP_ID=1000
RUN addgroup -g $GROUP_ID -S appgroup && \
    adduser -u $USER_ID -S appuser -G appgroup

# Target para desarrollo
FROM base AS dev

# Instalar Xdebug para desarrollo
RUN apk --no-cache add $PHPIZE_DEPS linux-headers \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug

# Configuración de Xdebug
RUN echo "xdebug.mode=debug,coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_port=9003" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

WORKDIR /var/www/html

USER appuser

EXPOSE 9000

# Target para producción
FROM base AS prod

# Configuraciones de producción
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

WORKDIR /var/www/html

# Copiar código fuente
COPY --chown=appuser:appgroup . .

# Instalar dependencias de producción
USER root
RUN composer install --no-dev --optimize-autoloader --no-scripts
USER appuser

EXPOSE 9000
