FROM php:8.3-apache

# Refuse to build on a PHP release vulnerable to CVE-2026-109618 / CVE-2026-109619.
# If this fails, rebuild with:  docker compose build --pull backend
RUN php -r 'exit(version_compare(PHP_VERSION, "8.3.36", ">=") ? 0 : 1);' \
 || (echo "PHP $(php -r 'echo PHP_VERSION;') is vulnerable (CVE-2026-109618/109619). Need >= 8.3.36. Rebuild with --pull." >&2; exit 1)

RUN apt-get update && apt-get install -y --no-install-recommends git unzip \
 && docker-php-ext-install pdo_mysql \
 && a2enmod rewrite headers \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Production php.ini + our hardening overrides
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php-hardening.ini "$PHP_INI_DIR/conf.d/zz-hardening.ini"
COPY docker/apache-hardening.conf /etc/apache2/conf-available/hardening.conf
RUN a2enconf hardening

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
 && sed -ri 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf
WORKDIR /var/www/html
CMD ["sh", "-c", "composer install --no-interaction --quiet --no-dev --prefer-dist; apache2-foreground"]
