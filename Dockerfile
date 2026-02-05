FROM php:7.4-cli

# Install required extensions
RUN apt-get update && apt-get install -y \
    sqlite3 \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite sockets \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy application files
COPY . /app

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Create necessary directories
RUN mkdir -p database logs && \
    chmod 755 database logs

# Run migrations
RUN php database/migrate.php

# Expose port
EXPOSE 8080

# Start server
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
