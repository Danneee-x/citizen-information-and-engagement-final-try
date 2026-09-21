FROM php:8.2-apache

# 1. Install system dependencies & PHP extensions required for Civentral
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo pdo_mysql gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 2. Enable Apache rewrite & headers modules
RUN a2enmod rewrite headers

# 3. Adjust Apache configuration to allow .htaccess overrides
RUN sed -ri -e 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf

# 4. Configure PHP runtime limits (uploads, memory, execution time)
RUN echo "upload_max_filesize = 64M" > /usr/local/etc/php/conf.d/custom.ini \
    && echo "post_max_size = 64M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/custom.ini

# 5. Set working directory
WORKDIR /var/www/html

# 6. Copy application code into web root
COPY . /var/www/html

# 7. Ensure upload directories exist and assign write permissions to Apache www-data
RUN mkdir -p /var/www/html/assets/uploads/verifications \
    /var/www/html/assets/uploads/concerns \
    /var/www/html/assets/uploads/certificates \
    && chown -R www-data:www-data /var/www/html/assets/uploads \
    && chmod -R 775 /var/www/html/assets/uploads

# 8. Expose standard HTTP port 80
EXPOSE 80

CMD ["apache2-foreground"]
