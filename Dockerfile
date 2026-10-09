
# ---------------------------------------------------------------------------
# Stage 1: install PHP dependencies from composer.lock
# ---------------------------------------------------------------------------
FROM public.ecr.aws/docker/library/composer:2.10 AS vendor

WORKDIR /build/api
COPY api/composer.json api/composer.lock ./
# Platform requirements are checked against the runtime image below, not this one
RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --prefer-dist \
        --optimize-autoloader \
        --ignore-platform-reqs

# ---------------------------------------------------------------------------
# Stage 2: frontend libraries (package-lock.json) and minified Londerland CSS/JS
# ---------------------------------------------------------------------------
FROM public.ecr.aws/docker/library/node:26-alpine AS frontend

WORKDIR /build
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY scripts/build-frontend.mjs scripts/
COPY css css
COPY js js
RUN npm run build

# ---------------------------------------------------------------------------
# Stage 3: runtime image (Apache + PHP), everything baked in
# ---------------------------------------------------------------------------
FROM public.ecr.aws/docker/library/php:8.5-apache

ARG LONDERLAND_COMMIT=unknown

# Port Apache listens on inside the container. 80 works for any user on Docker 20.10+;
# set another (for example 8080) only if an unprivileged --user may not bind port 80 on your system.
ENV LONDERLAND_PORT=80
# Time zone for PHP and logs; override with -e TZ=Europe/Amsterdam
ENV TZ=UTC

# PHP extensions that are not in the base image (zip, ldap, mysqli).
# Build-only packages are removed afterwards.
RUN set -eux; \
    apt-get update; \
    savedAptMark="$(apt-mark showmanual)"; \
    apt-get install -y --no-install-recommends libzip-dev libldap2-dev; \
    docker-php-ext-configure ldap --with-libdir="lib/$(dpkg-architecture --query DEB_BUILD_MULTIARCH)"; \
    docker-php-ext-install -j"$(nproc)" zip ldap mysqli; \
    apt-mark auto '.*' > /dev/null; \
    apt-mark manual $savedAptMark; \
    find /usr/local -type f -executable -exec ldd '{}' ';' \
        | awk '/=>/ { so = $(NF-1); if (index(so, "/usr/local/") == 1) { next }; gsub("^/(usr/)?", "", so); printf "*%s\n", so }' \
        | sort -u | xargs -r dpkg-query --search | awk 'sub(":$", "", $1) { print $1 }' | sort -u | xargs -r apt-mark manual; \
    apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false; \
    rm -rf /var/lib/apt/lists/*; \
    php -m | grep -qi '^zip$'; php -m | grep -qi '^ldap$'; php -m | grep -qi '^pdo_sqlite$'

# Apache listens on ${LONDERLAND_PORT}; its run, lock and log folders are already writable for any user
RUN set -eux; \
    mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"; \
    a2enmod rewrite headers; \
    sed -i 's/^Listen 80$/Listen ${LONDERLAND_PORT}/' /etc/apache2/ports.conf; \
    sed -i 's/<VirtualHost \*:80>/<VirtualHost *:${LONDERLAND_PORT}>/' /etc/apache2/sites-available/000-default.conf; \
    grep -q 'Listen ${LONDERLAND_PORT}' /etc/apache2/ports.conf

COPY docker/php.ini "$PHP_INI_DIR/conf.d/zz-londerland.ini"
COPY docker/apache-londerland.conf /etc/apache2/conf-enabled/londerland.conf
COPY --chmod=755 docker/entrypoint.sh /usr/local/bin/londerland-entrypoint

WORKDIR /var/www/html
COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /build/api/vendor ./api/vendor
COPY --from=frontend --chown=www-data:www-data /build/assets/vendor ./assets/vendor
COPY --from=frontend --chown=www-data:www-data /build/css/*.min.css ./css/
COPY --from=frontend --chown=www-data:www-data /build/js/*.min.js ./js/

# Docker.txt marks this as a Docker install (shown under Settings > About);
# Github.txt holds the commit used for cache-busting static files.
# The program files only need to be readable; only the data folder is written to.
RUN set -eux; \
    rm -rf docker Dockerfile .dockerignore package.json package-lock.json scripts/build-frontend.mjs; \
    touch Docker.txt; \
    printf '%s' "$LONDERLAND_COMMIT" > Github.txt; \
    mkdir -p data; \
    chown -R www-data:www-data /var/www/html; \
    chmod -R a+rX /var/www/html

VOLUME ["/var/www/html/data"]
EXPOSE 80

HEALTHCHECK --interval=1m --timeout=10s --start-period=30s --retries=3 \
    CMD php -r 'exit(@file_get_contents("http://127.0.0.1:" . getenv("LONDERLAND_PORT") . "/api/v2/status") === false ? 1 : 0);'

ENTRYPOINT ["londerland-entrypoint"]
CMD ["apache2-foreground"]
