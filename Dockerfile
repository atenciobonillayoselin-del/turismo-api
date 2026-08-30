# ============================================================
# DOCKERFILE MINIMAL - TURISMO LA PAZ
# Solo extensiones ESTRICTAMENTE necesarias
# ============================================================

FROM php:8.2-apache-bookworm

LABEL org.opencontainers.image.title="Turismo La Paz API"

ENV DEBIAN_FRONTEND=noninteractive \
    TIMEZONE=America/La_Paz \
    PORT=80

# ============================================================
# 1. Instalar SOLO librerías base (para compilar extensiones)
# ============================================================
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        unzip \
        git \
        libcurl4-openssl-dev \
        libonig-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
    && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# ============================================================
# 2. Configurar y compilar SOLO extensiones esenciales
# ============================================================
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mysqli \
        curl \
        mbstring \
        gd \
        zip \
        opcache \
    && \
    docker-php-ext-enable pdo_mysql mysqli curl mbstring gd zip opcache

# ============================================================
# 3. Verificar extensiones instaladas
# ============================================================
RUN php -m && echo "✅ Extensiones OK"

# ============================================================
# 4. Configuración PHP
# ============================================================
RUN { \
    echo 'date.timezone = America/La_Paz'; \
    echo 'memory_limit = 512M'; \
    echo 'max_execution_time = 300'; \
    echo 'display_errors = Off'; \
    echo 'log_errors = On'; \
    echo 'error_log = /dev/stderr'; \
    echo 'opcache.enable = 1'; \
    echo 'opcache.memory_consumption = 256'; \
    echo 'curl.cainfo = /etc/ssl/certs/ca-certificates.crt'; \
} > /usr/local/etc/php/conf.d/turismo.ini

# ============================================================
# 5. Apache + VirtualHost con puerto dinámico
# ============================================================
RUN a2enmod rewrite headers

RUN { \
    echo '<VirtualHost *:${PORT:-80}>'; \
    echo '  DocumentRoot /var/www/html'; \
    echo '  <Directory /var/www/html>'; \
    echo '    Options -Indexes +FollowSymLinks'; \
    echo '    AllowOverride All'; \
    echo '    Require all granted'; \
    echo '  </Directory>'; \
    echo '  Header set Access-Control-Allow-Origin "*"'; \
    echo '</VirtualHost>'; \
} > /etc/apache2/sites-available/000-default.conf

# ============================================================
# 6. Copiar código
# ============================================================
WORKDIR /var/www/html
COPY . .

# ============================================================
# 7. Permisos
# ============================================================
RUN chown -R www-data:www-data /var/www/html && \
    mkdir -p /var/www/html/data/umap_cache && \
    chmod -R 775 /var/www/html/data

# ============================================================
# 8. Entrypoint (usa $PORT de Render)
# ============================================================
RUN echo '#!/bin/bash\n\
set -e\n\
PORT=${PORT:-80}\n\
echo "🚀 Puerto: $PORT"\n\
sed -i "s/\${PORT:-80}/$PORT/g" /etc/apache2/sites-available/000-default.conf\n\
echo "Listen $PORT" > /etc/apache2/ports.conf\n\
echo "✅ PHP $(php -v | head -1)"\n\
apache2-foreground' > /usr/local/bin/entrypoint.sh && \
chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]