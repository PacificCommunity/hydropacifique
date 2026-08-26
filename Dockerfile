# PHP 8.0 is end-of-life (no security patches since Nov 2023). composer.json requires
# ">=8.0.30" and pins dependency resolution to 8.0.30, so a newer runtime is allowed.
# 8.2 is the sweet spot: still security-supported and inside phpspreadsheet 1.28's
# tested range. Bump with --build-arg PHP_VERSION=8.3 after a smoke test.
ARG PHP_VERSION=8.2
FROM php:${PHP_VERSION}-apache

# Build/runtime libs for gd, zip and intl. mbstring, curl, iconv, dom, simplexml,
# xmlwriter, fileinfo and zlib are already compiled into the official image.
#
# The -dev packages are deliberately NOT purged afterwards: `apt-get purge
# --auto-remove` would also take the runtime libraries they pulled in (libicu*,
# libzip*, libpng*), silently breaking intl/zip/gd. Costs ~100MB of image for a
# build that keeps working across Debian releases.
RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libicu-dev \
        curl \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install -j"$(nproc)" \
        calendar \
        gd \
        mysqli \
        pdo_mysql \
        zip \
        intl \
        opcache \
    && rm -rf /var/lib/apt/lists/*

# Production php.ini as the base, then our overrides on top.
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php.ini "$PHP_INI_DIR/conf.d/zz-hydropacifique.ini"

# Apache listens on 8080 so the container needs no root-bound port.
# The grep makes a silently-unmatched sed a build failure instead of an Apache
# that listens on 80 while the vhost is bound to 8080.
RUN set -eux \
    && a2enmod rewrite headers \
    && sed -ri 's/^Listen 80$/Listen 8080/' /etc/apache2/ports.conf \
    && grep -qx 'Listen 8080' /etc/apache2/ports.conf
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html

# Dependencies first so code changes don't invalidate the composer layer.
# --no-scripts is safe here: composer.json defines none, and --optimize-autoloader
# still generates vendor/autoload.php.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --no-progress --optimize-autoloader \
    && composer clear-cache

COPY . .

# Only the writable paths belong to www-data; application code stays read-only.
RUN mkdir -p data/txt \
    && chown -R root:root /var/www/html \
    && chown -R www-data:www-data data \
    && find /var/www/html -type d -exec chmod 755 {} + \
    && find /var/www/html -type f -exec chmod 644 {} +

COPY docker/entrypoint.sh /usr/local/bin/hp-entrypoint
RUN chmod 755 /usr/local/bin/hp-entrypoint

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -fsS http://127.0.0.1:8080/ -o /dev/null || exit 1

ENTRYPOINT ["hp-entrypoint"]
CMD ["apache2-foreground"]
