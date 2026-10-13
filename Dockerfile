FROM php:8.2-apache

# Install SQLite development libraries for PDO SQLite support
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Configure Apache port for cloud environments that inject $PORT (Render / Cloud Run)
ENV PORT=80
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Copy project source code
WORKDIR /var/www/html
COPY . /var/www/html/

# Ensure web server has write access to SQLite database directory
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/config

EXPOSE 80

CMD ["apache2-foreground"]
