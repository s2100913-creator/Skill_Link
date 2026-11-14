FROM php:8.2-cli

# PostgreSQL driver (needed by lib/db.php)
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY . .

EXPOSE 10000

# router.php maps clean URLs to the PHP files
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-10000} -t . router.php"]
