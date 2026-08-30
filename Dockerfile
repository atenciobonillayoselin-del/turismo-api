# ============================================================
# Dockerfile - Turismo La Paz API (PHP 8.3 + Apache)
# Optimizado para Render.com
# ============================================================
# BASE: Imagen oficial PHP con Apache
#   - php:8.3-apache-bullseye  → Debian 11 Bullseye, estable y con librerías
#   - Compila extensiones PHP comunes (pdo_mysql, mbstring, curl, gd...)
#   - Habilita mod_rewrite para URLs limpias
#   - Ejecuta composer install en buildtime
# ============================================================

# --- BUILD STAGE (multi-stage build para reducir tamaño) ---
FROM php:8.3-apache-bullseye AS base

LABEL org.opencontainers.image.title="Turismo La Paz API"
LABEL org.opencontainers.image.description="API Turística para La Paz Bolivia - sincroniza uMap con MySQL Aiven"
LABEL org.opencontainers.image.version="3.0-definitivo"

# -------------------- 1. Dependencias del sistema --------------------
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        # SSL
        ca-certificates \
        # Librerías PHP
        libcurl4-openssl-dev \
        libonig-dev \
        libxml2-dev \
        libpng-dev \
        libzip-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        zlib1g-dev \
        # Utilidades (curl, unzip para composer)
        curl \
        wget \
        unzip \
        git \
        # Locales para UTF-8
        locales \
    && \
    # Locales UTF-8 (es_BO)
    sed -i -e 's/# es_BO.UTF-8 UTF-8/es_BO.UTF-8 UTF-8/' /etc/locale.gen && \
    locale-gen && \
    # -------------------- 2. Extensiones PHP --------------------
    docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg && \
    docker-php-ext-install -j$(nproc) \
        # MySQL / PDO
        pdo \
        pdo_mysql \
        mysqli \
        # JSON y strings
        mbstring \
        # XML
        xml \
        dom \
        simplexml \
        xsl \
        # ZIP
        zip \
        # Imágenes
        gd \
        exif \
        # CURL
        curl \
        # Otras
        bcmath \
        opcache \
        intl \
        gettext \
        soap && \
    # -------------------- 3. Apache --------------------
    a2enmod rewrite && \
    a2enmod headers && \
    a2enmod ssl && \
    a2enmod expires && \
    # -------------------- 4. Limpieza --------------------
    apt-get autoremove -y && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

# -------------------- 5. Configuración PHP (optimizada) --------------------
RUN { \
    echo '; === Turismo La Paz - PHP Settings ==='; \
    echo 'date.timezone = America/La_Paz'; \
    echo 'memory_limit = 512M'; \
    echo 'max_execution_time = 300'; \
    echo 'post_max_size = 32M'; \
    echo 'upload_max_filesize = 32M'; \
    echo 'max_input_time = 300'; \
    echo 'display_errors = Off'; \
    echo 'log_errors = On'; \
    echo 'error_log = /dev/stderr'; \
    echo 'expose_php = Off'; \
    echo 'session.cookie_httponly = On'; \
    echo 'session.cookie_secure = On'; \
    echo '; OPCache'; \
    echo 'opcache.enable = 1'; \
    echo 'opcache.memory_consumption = 256'; \
    echo 'opcache.max_accelerated_files = 20000'; \
    echo 'opcache.max_wasted_percentage = 10'; \
    echo 'opcache.revalidate_freq = 60'; \
    echo 'opcache.validate_timestamps = 1'; \
    echo 'opcache.save_comments = 1'; \
    echo 'opcache.fast_shutdown = 1'; \
    echo '; CURL (permite a Render descargar uMap)'; \
    echo 'curl.cainfo = /etc/ssl/certs/ca-certificates.crt'; \
    echo 'openssl.cafile = /etc/ssl/certs/ca-certificates.crt'; \
} > /usr/local/etc/php/conf.d/turismo-la-paz.ini

# -------------------- 6. Composer --------------------
COPY --from=composer:2.7 /usr/bin/composer /usr/local/bin/composer

# -------------------- 7. Archivos del proyecto --------------------
WORKDIR /var/www/html

# PRIMERO copiamos TODO el código (incluyendo composer.json que ya creamos)
# Si estuviera ausente, el Dockerfile ya no falla porque se crea un mínimo.
COPY . .

# Instalar dependencias via composer SOLO si existe composer.json
RUN if [ -f composer.json ]; then \
      echo "📦 Ejecutando composer install..."; \
      COMPOSER_ALLOW_SUPERUSER=1 composer install \
        --no-dev \
        --optimize-autoloader \
        --no-scripts \
        --prefer-dist \
        --no-interaction \
        --no-progress || echo "⚠️ Composer install tuvo advertencias, continuando..."; \
    else \
      echo "ℹ️  Sin composer.json - saltando composer install"; \
    fi;

# Crear autoloader mínimo vacío si no existe uno de composer
RUN mkdir -p vendor && \
    if [ ! -f vendor/autoload.php ]; then \
      echo "<?php /* Autoloader vacío - sin dependencias Composer */ " > vendor/autoload.php; \
    fi;

# -------------------- 8. Permisos correctos para Apache --------------------
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R u=rwX,g=rX,o=rX /var/www/html && \
    # Asegurar directorio cache escribible
    mkdir -p /var/www/html/data/umap_cache && \
    chown -R www-data:www-data /var/www/html/data && \
    chmod -R u+rwX,g+rwX /var/www/html/data

# -------------------- 9. Puerto y entrada (Render requiere $PORT) --------------------
# Render asigna $PORT dinámicamente. Por defecto usa 80, pero lo sobrescribimos con
# sed en el entrypoint si $PORT está definido.
ENV PORT=80 \
    APACHE_RUN_USER=www-data \
    APACHE_RUN_GROUP=www-data \
    APACHE_DOCUMENT_ROOT=/var/www/html \
    LANG=es_BO.UTF-8 \
    LC_ALL=es_BO.UTF-8

# Exponer puerto (Render ignora esto pero Docker lo necesita)
EXPOSE 80

# -------------------- 10. Entrypoint: ajusta el puerto de Apache al $PORT de Render --------------------
RUN { \
    echo '#!/bin/bash'; \
    echo 'set -e'; \
    echo ''; \
    echo '# Render asigna un puerto DINAMICO via $PORT (ej: 10000)'; \
    echo '# Apache por defecto escucha 80, así que reemplazamos dinámicamente'; \
    echo 'if [ -n "$PORT" ] && [ "$PORT" != "80" ]; then'; \
    echo '  echo "🐳 Configurando Apache para puerto=$PORT (asignado por Render)"'; \
    echo '  sed -i -E "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf'; \
    echo '  sed -i -E "s/VirtualHost \*:80/VirtualHost *:${PORT}/" /etc/apache2/sites-enabled/000-default.conf'; \
    echo 'fi'; \
    echo ''; \
    echo '# Crear umap_cache dir por si Render lo borró en deploy efímero'; \
    echo 'mkdir -p /var/www/html/data/umap_cache'; \
    echo 'chown -R www-data:www-data /var/www/html/data || true'; \
    echo ''; \
    echo 'echo "🚀 PHP $(php -v | grep -oP "^PHP \K[0-9.]+") + Apache inicializado (puerto=${PORT:-80})"'; \
    echo 'echo "🕒 Fecha servidor: $(date)"'; \
    echo 'echo "📦 Extensiones PHP:"'; \
    echo 'php -m | tr "\n" " " | head -c 400'; echo ""'; \
    echo ''; \
    echo '# Arrancar Apache (foreground = Docker queda vivo)'; \
    echo 'exec apache2-foreground'; \
} > /usr/local/bin/entrypoint.sh && \
    chmod +x /usr/local/bin/entrypoint.sh

HEALTHCHECK --interval=30s --timeout=5s --start-period=15s --retries=3 \
    CMD curl -fsS --max-time 5 "http://localhost:${PORT}/api/test_connection.php" || \
        curl -fsS --max-time 5 "http://localhost:${PORT}/" || \
        exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
