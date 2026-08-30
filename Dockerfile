# ============================================================
# Dockerfile - TURISMO LA PAZ  (VERSION NUCLEAR - SIN APT)
# ============================================================
# 🔑 MOTIVACIÓN: La imagen oficial PHP de Docker (php:8.2-apache-bookworm)
# COMPILA PHP desde el código fuente. Los paquetes "php8.2-*" de Debian NO
# existen como tales en los repos activos de la imagen → por eso apt fallaba
# con exit code 100.
#
# ✅ SOLUCIÓN: USAMOS LO QUE LA IMAGEN PHP OFICIAL TRAE + docker-php-ext-install
#   - docker-php-ext-install FUNCIONA (está diseñada para esta imagen)
#   - No usamos apt para nada relacionado con PHP
#   - Solo apt-get install MÍNIMO para curl/zip/ca-cert, con retry de red
# ============================================================

FROM php:8.2-apache-bookworm

LABEL org.opencontainers.image.title="Turismo La Paz API v3 - Nuclear"
LABEL org.opencontainers.image.description="Sincroniza uMap con MySQL Aiven"

ENV DEBIAN_FRONTEND=noninteractive \
    LANG=C.UTF-8 \
    LC_ALL=C.UTF-8 \
    TIMEZONE=America/La_Paz \
    PORT=80

# ====================================================================
# 1. Instalar dependencias SISTEMA (SOLO librerías de bajo nivel que
#    docker-php-ext-install NECESITA para compilar)
# ====================================================================
RUN --mount=type=cache,target=/var/cache/apt,sharing=locked \
    --mount=type=cache,target=/var/lib/apt,sharing=locked \
    set -eux; \
    # Retry: en Render a veces hay fallos de red al llegar al mirror Debian
    for i in 1 2 3 4 5; do \
      apt-get update -y && break; \
      echo "[retry] apt-get update falló (intento $i/5), esperando 5s..."; \
      sleep 5; \
    done; \
    for i in 1 2 3 4 5; do \
      apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        wget \
        unzip \
        git \
        # zip (ext)
        libzip-dev \
        zip \
        # gd
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        # intl
        libicu-dev \
        # soap + xml
        libxml2-dev \
        libxslt1-dev \
        # oniguruma (mbstring ya esta, pero por si acaso)
        libonig-dev \
        ssl-cert \
      && break; \
      echo "[retry] apt-get install falló (intento $i/5), esperando 5s..."; \
      sleep 5; \
    done; \
    # Timezone
    ln -snf /usr/share/zoneinfo/$TIMEZONE /etc/localtime || true; \
    echo $TIMEZONE > /etc/timezone 2>/dev/null || true; \
    # Cleanup MUY AGRESIVO (reduce imagen en ~200MB)
    apt-get autoremove -y; \
    apt-get clean; \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/* /var/cache/apt/*; \
    # Verificar binarios
    which curl; which unzip; which git;

# ====================================================================
# 2. Extensiones PHP (docker-php-ext-install)
#    Aquí NO falla. Si falla → repetimos hasta 3 veces (timing de red).
# ====================================================================
RUN set -eux; \
    # gd
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    # Instalar extensiones (con retry por si acaso)
    for i in 1 2 3; do \
      docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mysqli \
        curl \
        mbstring \
        xml \
        dom \
        simplexml \
        xsl \
        zip \
        gd \
        exif \
        bcmath \
        opcache \
        intl \
        soap \
      && break; \
      echo "[retry] docker-php-ext-install falló (intento $i/3), sleep 10s"; \
      sleep 10; \
    done; \
    # Comprobar que TODAS están OK
    php -m > /tmp/exts.txt; \
    echo "=== Extensiones PHP instaladas ==="; \
    cat /tmp/exts.txt; \
    for EXT in pdo_mysql curl mbstring gd zip xml opcache bcmath intl soap json; do \
      if ! grep -q -i "^$EXT$" /tmp/exts.txt; then \
        echo "❌ FALTA extensión: $EXT"; exit 1; \
      fi; \
      echo "✅ $EXT OK"; \
    done;

# ====================================================================
# 3. Módulos Apache + PHP config
# ====================================================================
RUN set -eux; \
    a2enmod rewrite headers ssl expires deflate; \
    # Desactivar sitio default
    a2dissite 000-default || true; \
    CONFDIR=$(php -r "echo PHP_CONFIG_FILE_SCAN_DIR;"); \
    echo "PHP ini scan dir: $CONFDIR"; \
    { \
      echo '[Date]'; echo 'date.timezone = America/La_Paz'; \
      echo '[PHP]'; \
      echo 'memory_limit = 512M'; \
      echo 'max_execution_time = 300'; \
      echo 'max_input_time = 300'; \
      echo 'post_max_size = 64M'; \
      echo 'upload_max_filesize = 32M'; \
      echo 'display_errors = Off'; \
      echo 'display_startup_errors = Off'; \
      echo 'log_errors = On'; \
      echo 'error_log = /dev/stderr'; \
      echo 'expose_php = Off'; \
      echo 'session.cookie_httponly = On'; \
      echo 'session.cookie_secure = On'; \
      echo '[opcache]'; \
      echo 'opcache.enable=1'; echo 'opcache.enable_cli=0'; \
      echo 'opcache.memory_consumption=256'; \
      echo 'opcache.max_accelerated_files=20000'; \
      echo 'opcache.revalidate_freq=60'; \
      echo 'opcache.validate_timestamps=1'; \
      echo 'opcache.save_comments=1'; \
      echo '[curl]'; echo 'curl.cainfo=/etc/ssl/certs/ca-certificates.crt'; \
      echo '[openssl]'; echo 'openssl.cafile=/etc/ssl/certs/ca-certificates.crt'; \
    } > "$CONFDIR/90-turismo-la-paz.ini"; \
    php --ini; \
    echo "== Versión PHP =="; php -v;

# ====================================================================
# 4. Composer
# ====================================================================
COPY --from=composer:2.7 /usr/bin/composer /usr/local/bin/composer
RUN chmod +x /usr/local/bin/composer; \
    mkdir -p /var/www/.composer; \
    chown -R www-data:www-data /var/www/.composer;

# ====================================================================
# 5. VirtualHost Apache (placeholder __PORT__)
# ====================================================================
RUN set -eux; \
    { \
      echo '<VirtualHost *:__PORT__>'; \
      echo '  ServerAdmin webmaster@localhost'; \
      echo '  DocumentRoot /var/www/html'; \
      echo '  <Directory /var/www/html>'; \
      echo '    Options -Indexes +FollowSymLinks'; \
      echo '    AllowOverride All'; \
      echo '    Require all granted'; \
      echo '    DirectoryIndex index.php index.html'; \
      echo '  </Directory>'; \
      echo '  Header always set Access-Control-Allow-Origin "*"'; \
      echo '  Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, PATCH, OPTIONS"'; \
      echo '  Header always set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With, X-Trigger, Accept"'; \
      echo '  Header always set Access-Control-Max-Age "3600"'; \
      echo '  Header always set X-Content-Type-Options "nosniff"'; \
      echo '  Header always set X-Frame-Options "SAMEORIGIN"'; \
      echo '  AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css application/javascript application/json'; \
      echo '  ErrorLog ${APACHE_LOG_DIR}/error.log'; \
      echo '  CustomLog ${APACHE_LOG_DIR}/access.log combined'; \
      echo '</VirtualHost>'; \
    } > /etc/apache2/sites-available/turismo.conf; \
    a2ensite turismo;

# ====================================================================
# 6. Entrypoint (cambia __PORT__ por $PORT de Render)
# ====================================================================
RUN set -eux; \
    { \
      echo '#!/bin/bash'; \
      echo 'set -e'; \
      echo 'export PORT=${PORT:-80}'; \
      echo 'echo "🐳 [entrypoint] Render PORT=$PORT"'; \
      echo 'sed -i "s/__PORT__/${PORT}/g" /etc/apache2/sites-available/turismo.conf'; \
      echo 'echo "Listen ${PORT}" > /etc/apache2/ports.conf'; \
      echo 'mkdir -p /var/www/html/data/umap_cache'; \
      echo 'chown -R www-data:www-data /var/www/html/data /var/www/html/api /var/www/html/config 2>/dev/null || true'; \
      echo 'chmod -R u+rwX,g+rwX /var/www/html/data 2>/dev/null || true'; \
      echo 'echo "🚀 PHP $(php -v | head -n 1) + Apache, puerto=$PORT"'; \
      echo 'echo "🕒 $(date)"'; \
      echo 'cd /var/www/html'; \
      echo 'if [ -f composer.json ] && [ ! -d vendor/composer ]; then'; \
      echo '  COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist 2>&1 | tail -10 || true'; \
      echo 'fi'; \
      echo 'echo "✅ Contenedor listo para recibir tráfico en :$PORT"'; \
      echo 'exec docker-php-entrypoint apache2-foreground'; \
    } > /usr/local/bin/entrypoint.sh; \
    chmod +x /usr/local/bin/entrypoint.sh;

# ====================================================================
# 7. Copiar código del proyecto y permisos
# ====================================================================
WORKDIR /var/www/html
COPY . .

RUN set -eux; \
    cd /var/www/html; \
    if [ -f composer.json ]; then \
      COMPOSER_ALLOW_SUPERUSER=1 composer install \
        --no-dev --optimize-autoloader --no-interaction \
        --prefer-dist --no-progress 2>&1 | tail -15 \
        || echo "⚠️ Composer tuvo warnings, continuando..."; \
    else \
      echo "ℹ️ Sin composer.json"; \
      mkdir -p vendor; \
      echo '<?php /* sin vendor */ ' > vendor/autoload.php; \
    fi; \
    chown -R www-data:www-data /var/www/html; \
    find /var/www/html -type d -exec chmod 755 {} \; 2>/dev/null || true; \
    find /var/www/html -type f -exec chmod 644 {} \; 2>/dev/null || true; \
    mkdir -p /var/www/html/data/umap_cache; \
    chown -R www-data:www-data /var/www/html/data; \
    chmod -R u+rwX,g+rwX /var/www/html/data;

# ====================================================================
# 8. Health check
# ====================================================================
HEALTHCHECK --interval=45s --timeout=10s --start-period=25s --retries=4 CMD \
  curl -fsSL --max-time 10 -o /dev/null "http://localhost:${PORT:-80}/api/test_connection.php" || \
  curl -fsSL --max-time 10 -o /dev/null "http://localhost:${PORT:-80}/" || exit 1

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
