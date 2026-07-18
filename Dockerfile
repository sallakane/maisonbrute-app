# syntax=docker/dockerfile:1

# ---------- Base : FrankenPHP (Caddy + PHP intégrés) ----------
FROM dunglas/frankenphp:1-php8.4 AS frankenphp_base

WORKDIR /app

VOLUME /app/var/

RUN apt-get update && apt-get install -y --no-install-recommends \
        acl file gettext git \
    && rm -rf /var/lib/apt/lists/*

# Extensions PHP : pdo_pgsql (Doctrine/PostgreSQL), intl (i18n/SEO), opcache, zip, apcu. + Composer.
RUN set -eux; \
    install-php-extensions \
        @composer \
        apcu \
        intl \
        opcache \
        zip \
        pdo_pgsql \
    ;

ENV COMPOSER_ALLOW_SUPERUSER=1

COPY --link frankenphp/conf.d/10-app.ini $PHP_INI_DIR/conf.d/
COPY --link frankenphp/Caddyfile /etc/caddy/Caddyfile
COPY --link --chmod=755 frankenphp/docker-entrypoint.sh /usr/local/bin/docker-entrypoint

ENTRYPOINT ["docker-entrypoint"]

HEALTHCHECK --start-period=60s CMD curl -f http://localhost:2019/metrics || exit 1
CMD [ "frankenphp", "run", "--config", "/etc/caddy/Caddyfile" ]

# ---------- Dev (optionnel — le dev courant se fait via `symfony server`) ----------
FROM frankenphp_base AS frankenphp_dev

ENV APP_ENV=dev XDEBUG_MODE=off
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
COPY --link frankenphp/conf.d/20-app.dev.ini $PHP_INI_DIR/conf.d/

# ---------- Prod ----------
FROM frankenphp_base AS frankenphp_prod

ENV APP_ENV=prod
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY --link frankenphp/conf.d/20-app.prod.ini $PHP_INI_DIR/conf.d/

# Dépendances d'abord (cache de couche), puis le code.
COPY --link composer.* symfony.lock ./
RUN set -eux; \
    composer install --no-cache --no-dev --no-autoloader --no-scripts --no-progress

COPY --link . ./
# On retire du contexte tout ce qui n'a rien à faire en prod.
RUN rm -Rf frankenphp/ design-ref/ docs/ tests/ *.md

RUN set -eux; \
    mkdir -p var/cache var/log; \
    composer dump-autoload --classmap-authoritative --no-dev; \
    composer dump-env prod; \
    composer run-script --no-dev post-install-cmd; \
    php bin/console tailwind:build --minify; \
    php bin/console asset-map:compile; \
    chmod +x bin/console; sync;
