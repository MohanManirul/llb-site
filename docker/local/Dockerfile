# syntax=docker/dockerfile:1

# Local development image for boneek-crm (StepUp CRM).
#
# PHP-FPM only — nginx runs in its own container and reaches this one over the
# compose network on port 9000. The project root is bind-mounted at runtime, so
# nothing is COPYied in here; a rebuild is only needed when this file changes.
#
# Node is installed on purpose: the `vite` service runs `npm run dev` from this
# same image.
#
# Staging/production build the root ./Dockerfile instead.

FROM php:8.4-fpm

# Match the host user so bind-mounted files keep host ownership.
ARG UID=1000
ARG GID=1000
ARG NODE_VERSION=22

ENV COMPOSER_ALLOW_SUPERUSER=0 \
    COMPOSER_HOME=/home/www/.composer \
    NPM_CONFIG_CACHE=/home/www/.npm

# postgresql-client gives `php artisan db` and pg_dump something to shell out to.
RUN apt-get update && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        git \
        gnupg \
        procps \
        unzip \
        postgresql-client \
        libpq-dev \
        libzip-dev \
        libonig-dev \
        libicu-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libwebp-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-configure gd --with-jpeg --with-freetype --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        pgsql \
        bcmath \
        mbstring \
        zip \
        intl \
        pcntl \
        opcache \
        gd \
    && yes '' | pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && rm -rf /var/lib/apt/lists/*

RUN curl -fsSL "https://deb.nodesource.com/setup_${NODE_VERSION}.x" | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# A `www` user owning the host UID/GID, and php-fpm's pool running as it.
# The rename branches are not paranoia: on macOS `id -u`/`id -g` is 501/20, and
# GID 20 already exists inside Debian as `dialout`.
RUN if getent group "${GID}" >/dev/null; then \
        groupmod -n www "$(getent group "${GID}" | cut -d: -f1)"; \
    else \
        groupadd -g "${GID}" www; \
    fi \
    && if getent passwd "${UID}" >/dev/null; then \
        usermod -l www -g www -d /home/www -m -s /bin/bash "$(getent passwd "${UID}" | cut -d: -f1)"; \
    else \
        useradd -u "${UID}" -g www -m -s /bin/bash www; \
    fi \
    && sed -i "s/user = www-data/user = www/g; s/group = www-data/group = www/g" /usr/local/etc/php-fpm.d/www.conf \
    && mkdir -p "${COMPOSER_HOME}" "${NPM_CONFIG_CACHE}" \
    && chown -R www:www /home/www

WORKDIR /var/www/html

USER www

EXPOSE 9000

CMD ["php-fpm"]
