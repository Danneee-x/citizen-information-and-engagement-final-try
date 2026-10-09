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

# 3. Adjust Apache configuration to allow .htaccess overrides and configure upload directory access
RUN sed -ri -e 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf \
    && echo '<Directory "/var/www/html/assets/uploads">' >> /etc/apache2/apache2.conf \
    && echo '    Options -Indexes +FollowSymLinks' >> /etc/apache2/apache2.conf \
    && echo '    AllowOverride All' >> /etc/apache2/apache2.conf \
    && echo '    Require all granted' >> /etc/apache2/apache2.conf \
    && echo '</Directory>' >> /etc/apache2/apache2.conf \
    && echo 'Alias /uploads /var/www/html/assets/uploads' >> /etc/apache2/apache2.conf

# 4. Configure PHP runtime limits (file uploads, memory, execution time, session persistence)
RUN echo "file_uploads = On" > /usr/local/etc/php/conf.d/custom.ini \
    && echo "upload_tmp_dir = /tmp" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "upload_max_filesize = 128M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "post_max_size = 128M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "memory_limit = 512M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "max_execution_time = 600" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "max_input_time = 600" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "max_file_uploads = 50" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "session.gc_maxlifetime = 604800" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "session.cookie_lifetime = 604800" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "session.cookie_path = /" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "session.cookie_samesite = Lax" >> /usr/local/etc/php/conf.d/custom.ini

# 5. Set working directory
WORKDIR /var/www/html

# 6. Copy application code into web root
COPY . /var/www/html

# 7. Ensure upload directories exist, symlink /uploads to assets/uploads, and assign write permissions
RUN mkdir -p /var/www/html/assets/uploads/verifications \
    /var/www/html/assets/uploads/concerns \
    /var/www/html/assets/uploads/certificates \
    /var/www/html/assets/uploads/ids \
    /var/www/html/assets/uploads/alerts \
    /var/www/html/assets/uploads/surveys \
    /var/www/html/assets/uploads/feedbacks \
    && ln -sfn /var/www/html/assets/uploads /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/assets/uploads \
    && chmod -R 777 /var/www/html/assets/uploads

VOLUME ["/var/www/html/assets/uploads"]

# 8. Expose standard HTTP port 80
EXPOSE 80

CMD ["apache2-foreground"]