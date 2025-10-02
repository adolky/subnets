# Subnet Calculator - Production Docker Image
FROM php:8.2-apache

# Install required PHP extensions
RUN apt-get update && apt-get install -y \
    sqlite3 \
    libsqlite3-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_sqlite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache rewrite module
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Create directory for persistent database with correct permissions
RUN mkdir -p /var/www/html/data && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Configure Apache to allow .htaccess
RUN echo '<Directory /var/www/html>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/subnet-calculator.conf && \
    a2enconf subnet-calculator

# Create .htaccess for better routing
RUN echo 'RewriteEngine On\n\
RewriteCond %{REQUEST_FILENAME} !-f\n\
RewriteCond %{REQUEST_FILENAME} !-d\n\
RewriteRule ^(.*)$ subnets.html [QSA,L]\n\
\n\
# Security headers\n\
<IfModule mod_headers.c>\n\
    Header always set X-Content-Type-Options nosniff\n\
    Header always set X-Frame-Options DENY\n\
    Header always set X-XSS-Protection "1; mode=block"\n\
</IfModule>' > /var/www/html/.htaccess

# Initialize database on container start
RUN php -r "require_once 'db_init.php'; echo 'Database initialized\\n';"

# Expose port 80
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/subnets.html || exit 1

# Create entrypoint script
RUN echo '#!/bin/bash\n\
set -e\n\
\n\
# Ensure database directory and file have correct permissions\n\
chown -R www-data:www-data /var/www/html\n\
chmod 664 /var/www/html/subnets.db 2>/dev/null || true\n\
\n\
# Initialize database if it does not exist or is empty\n\
if [ ! -s "/var/www/html/subnets.db" ]; then\n\
    echo "Initializing database..."\n\
    php -f db_init.php\n\
    chown www-data:www-data /var/www/html/subnets.db\n\
    chmod 664 /var/www/html/subnets.db\n\
fi\n\
\n\
# Start Apache\n\
exec apache2-foreground' > /usr/local/bin/docker-entrypoint.sh && \
    chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
