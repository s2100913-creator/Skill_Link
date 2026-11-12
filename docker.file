FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libssl-dev \
    pkg-config \
    && rm -rf /var/lib/apt/lists/*

# Install MongoDB PHP extension
RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . .

# Install PHP dependencies if composer.json exists
RUN if [ -f composer.json ]; then \
    composer install --no-dev --optimize-autoloader; \
    fi

# Render port
EXPOSE 10000

# Start PHP server
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-10000} -t ."]
