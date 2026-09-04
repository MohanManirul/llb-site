# syntax=docker/dockerfile:1

# Deployment image for the StepUp CRM backend (Laravel + Inertia/React).
# Coolify builds this file directly (Dockerfile build pack), which is what lets
# a deploy roll instead of stopping the container first. See DEPLOY.md. This
# file is deployment-only. Local development builds docker/local/Dockerfile via
# docker-compose.local.yml (or runs `composer run dev` outside Docker).

FROM serversideup/php:8.4-fpm-nginx AS base

# PHP dependencies
FROM base AS vendor
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --no-progress --prefer-dist

# Frontend build (app.css @source's vendor/ views, so it needs composer output)
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js ./
COPY public ./public
COPY resources ./resources
COPY --from=vendor /var/www/html/vendor ./vendor
# app.css also @source's storage/framework/views; the dir just has to exist
RUN mkdir -p storage/framework/views && npm run build

# Runtime
FROM base
USER root
COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /var/www/html/vendor ./vendor
COPY --from=assets --chown=www-data:www-data /app/public ./public
# .dockerignore drops runtime cache contents; recreate the dirs Laravel expects
RUN mkdir -p storage/app/public storage/logs bootstrap/cache \
    storage/framework/cache/data storage/framework/sessions storage/framework/views \
    && composer dump-autoload --optimize --no-dev --no-interaction \
    && chown -R www-data:www-data storage bootstrap/cache

# This line is what makes a deploy rolling, so do not drop it.
#
# Coolify starts the new container, waits for it to report healthy, and only
# then removes the old one. It reads that state two ways: the healthcheck
# configured in the panel, or a HEALTHCHECK found in this file. With neither,
# its wait returns immediately and the old container is removed about 100 ms
# after the new one starts, before php-fpm is listening, which is a real outage
# on every deploy. Keeping it here means the panel toggle cannot take the
# protection away by accident.
#
# It also replaces the base image's own healthcheck, which probes /healthcheck.
# This app does not serve that path, so the container would sit unhealthy and
# Traefik would leave it out of the pool. /up is Laravel's own health route
# (bootstrap/app.php).
HEALTHCHECK --interval=5s --timeout=3s --start-period=20s --retries=12 \
    CMD curl -fsS http://localhost:8080/up || exit 1

USER www-data
