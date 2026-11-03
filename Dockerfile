# Nummo — one container: Apache + PHP serves the frontend, the REST API (/api) and Swagger (/docs)
FROM php:8.3-apache

RUN apt-get update && apt-get install -y --no-install-recommends unzip \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo_mysql \
    && a2enmod rewrite

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Backend (outside the web root, so PHP source files can never be downloaded)
WORKDIR /var/www/backend
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --no-interaction --optimize-autoloader
COPY backend/ ./
RUN chown -R www-data:www-data logs

# Web root: the SPA, the Swagger page and a tiny entry file for the API
COPY frontend/ /var/www/html/
COPY backend/public/docs/ /var/www/html/docs/
COPY deploy/api.php /var/www/html/api.php
COPY deploy/apache.conf /etc/apache2/sites-available/000-default.conf
COPY deploy/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Settings come from environment variables (see README → Deployment)
CMD ["start.sh"]
