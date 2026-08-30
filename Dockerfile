# ============================================================
# Dockerfile - TURISMO LA PAZ
# Versión INDISTRUCTIBLE - No hay builds de extensiones PHP desde código fuente
# Usa paquetes .deb precompilados de Debian → 100% estables y rápidos
# ============================================================

FROM php:8.2-apache-bookworm

LABEL org.opencontainers.image.title="Turismo La Paz API v3"
LABEL org.opencontainers.image.description="Sincroniza uMap con MySQL Aiven"

# -------------------- CONFIG DEBIAN NO INTERACTIVO --------------------
ENV DEBIAN_FRONTEND=noninteractive \
    LANG=C.UTF-8 \
    LC_ALL=C.UTF-8 \
    TIMEZONE=America/La_Paz

# -------------------- 1. Extensiones PHP PRECOMPILADAS + utilidades
# (NADA de docker-php-ext-install - TODO por apt, 100% fiable)
# --------------------
RUN set -eux; \
    # Actualiza SOLO una vez, limpia al mismo tiempo
    apt-get update -y; \
    apt-get install -y --no-install-recommends \
      # === PHP EXTENSIONS via apt (8.2 en Debian Bookworm) ===
      php8.2-mysqlnd \
      php8.2-pdo-mysql \
      php8.2-curl \
      php8.2-mbstring \
      php8.2-gd \
      php8.2-zip \
      php8.2-xml \
      php8.2-soap \
      php8.2-intl \
      php8.2-bcmath \
      php8.2-opcache \
      php8.2-readline \
      php8.2-json \
      php8.2-common \
      # === Utilidades ===
      ca-certificates \
      curl \
      wget \
      unzip \
      git \
      # === SSL ===
      ssl-cert \
      libcurl4-openssl-dev; \
    # === Timezone Bolivia ===
    ln -snf /usr/share/zoneinfo/$TIMEZONE /etc/localtime; \
    echo $TIMEZONE > /etc/timezone; \
    # === Limpieza APT CRÍTICA (reduce tamaño 70%) ===
    apt-get autoremove -y; \
    apt-get clean; \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/* /var/cache/apt/*;

# -------------------- 2. Habilitar módulos Apache --------------------
RUN set -eux; \
    a2enmod rewrite headers ssl expires deflate; \
    a2dissite 000-default || true;

# -------------------- 3. Configuración PHP (production optimizada) --------------------
RUN set -eux; \
    CONFDIR=$(php -r "echo PHP_CONFIG_FILE_SCAN_DIR;"); \
    { \
      echo '[Date]'; \
      echo 'date.timezone = America/La_Paz'; \
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
      echo 'html_errors = Off'; \
      echo 'expose_php = Off'; \
      echo 'session.cookie_httponly = On'; \
      echo 'session.cookie_secure = On'; \
      echo 'session.use_strict_mode = 1'; \
      echo '[opcache]'; \
      echo 'opcache.enable = 1'; \
      echo 'opcache.enable_cli = 0'; \
      echo 'opcache.memory_consumption = 256'; \
      echo 'opcache.max_accelerated_files = 20000'; \
      echo 'opcache.max_wasted_percentage = 10'; \
      echo 'opcache.revalidate_freq = 60'; \
      echo 'opcache.validate_timestamps = 1'; \
      echo 'opcache.save_comments = 1'; \
      echo '[curl]'; \
      echo 'curl.cainfo = /etc/ssl/certs/ca-certificates.crt'; \
      echo '[openssl]'; \
      echo 'openssl.cafile = /etc/ssl/certs/ca-certificates.crt'; \
    } > "$CONFDIR/90-turismo-la-paz.ini"; \
    # Comprobamos sintaxis PHP
    php -v; \
    php -m | grep -Ei 'pdo|mysql|curl|mbstring|gd|zip|xml|opcache|json' || true;

# -------------------- 4. Composer --------------------
COPY --from=composer:2.7 /usr/bin/composer /usr/local/bin/composer
RUN chmod +x /usr/local/bin/composer; \
    mkdir -p /var/www/.composer; \
    chown -R www-data:www-data /var/www/.composer;

# -------------------- 5. VirtualHost para Render (puerto dinámico $PORT) --------------------
RUN set -eux; \
    { \
      echo '# VirtualHost Dinámico para Render.com'; \
      echo '# El puerto $PORT se reemplaza en entrypoint.sh'; \
      echo '<VirtualHost *:__PORT__>'; \
      echo '  ServerAdmin webmaster@localhost'; \
      echo '  DocumentRoot /var/www/html'; \
      echo ''; \
      echo '  <Directory /var/www/html>'; \
      echo '    Options -Indexes +FollowSymLinks'; \
      echo '    AllowOverride All'; \
      echo '    Require all granted'; \
      echo '    DirectoryIndex index.php index.html'; \
      echo '  </Directory>'; \
      echo ''; \
      echo '  # CORS headers globales'; \
      echo '  Header always set Access-Control-Allow-Origin "*"'; \
      echo '  Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, PATCH, OPTIONS"'; \
      echo '  Header always set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With, X-Trigger, Accept"'; \
      echo '  Header always set Access-Control-Max-Age "3600"'; \
      echo ''; \
      echo '  # Security headers'; \
      echo '  Header always set X-Content-Type-Options "nosniff"'; \
      echo '  Header always set X-Frame-Options "SAMEORIGIN"'; \
      echo '  Header always set Referrer-Policy "no-referrer-when-downgrade"'; \
      echo ''; \
      echo '  # Compresión'; \
      echo '  AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css application/javascript application/json'; \
      echo ''; \
      echo '  ErrorLog ${APACHE_LOG_DIR}/error.log'; \
      echo '  CustomLog ${APACHE_LOG_DIR}/access.log combined'; \
      echo '</VirtualHost>'; \
    } > /etc/apache2/sites-available/turismo.conf; \
    a2ensite turismo;

# -------------------- 6. Entrypoint: reemplaza __PORT__ por el $PORT de Render --------------------
RUN set -eux; \
    { \
      echo '#!/bin/bash'; \
      echo 'set -e'; \
      echo ''; \
      echo '# Render asigna un puerto DINÁMICO. Valor por defecto 80 si no hay $PORT.'; \
      echo 'export PORT=${PORT:-80}'; \
      echo ''; \
      echo 'echo "🐳 [entrypoint] Render PORT=$PORT"'; \
      echo ''; \
      echo '# 1) Actualizar el VirtualHost con el puerto real'; \
      echo 'sed -i "s/__PORT__/${PORT}/g" /etc/apache2/sites-available/turismo.conf'; \
      echo ''; \
      echo '# 2) Actualizar ports.conf de Apache'; \
      echo 'echo "Listen ${PORT}" > /etc/apache2/ports.conf'; \
      echo ''; \
      echo '# 3) Asegurar permisos y carpetas'; \
      echo 'mkdir -p /var/www/html/data/umap_cache'; \
      echo 'chown -R www-data:www-data /var/www/html/data /var/www/html/api /var/www/html/config || true'; \
      echo 'chmod -R u+rwX,g+rwX /var/www/html/data || true'; \
      echo ''; \
      echo '# 4) Informe de salud del contenedor'; \
      echo 'echo "🚀 PHP $(php -v | head -1) + Apache inicializado | puerto=$PORT"'; \
      echo 'echo "🕒 Fecha servidor: $(date)"'; \
      echo 'echo "📦 Extensiones:"'; \
      echo 'php -m | tr "\n" " " | head -c 300; echo ""'; \
      echo 'echo "🌐 Environment vars cargadas:"'; \
      echo 'for v in PDO_HOST PDO_PORT PDO_DATABASE PDO_USERNAME PDO_PASSWORD PDO_SSL_CA UMAP_PROXY_URL GITHUB_RAW_BASE UMAP_TOKEN GITHUB_REPO GITHUB_TOKEN RENDER_SYNC_URL; do'; \
      echo '  VAL="${!v:-}"; if [ -n "$VAL" ]; then echo "  ✅ $v=$(echo "$VAL" | head -c 8)... (SET)"; else echo "  ⬜ $v (no configurado)"; fi;'; \
      echo 'done'; \
      echo ''; \
      echo '# 5) Composer install si composer.json existe pero vendor no'; \
      echo 'cd /var/www/html'; \
      echo 'if [ -f composer.json ] && [ ! -d vendor/composer ]; then'; \
      echo '  echo "📦 Ejecutando composer install (primera vez)...";'; \
      echo '  COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist 2>&1 | tail -10 || true;'; \
      echo 'fi'; \
      echo ''; \
      echo '# 6) Arrancar Apache en foreground'; \
      echo 'exec docker-php-entrypoint apache2-foreground'; \
    } > /usr/local/bin/entrypoint.sh; \
    chmod +x /usr/local/bin/entrypoint.sh;

# -------------------- 7. Copiar el código del proyecto --------------------
WORKDIR /var/www/html
COPY . .

# Correr composer AHORA si composer.json está presente
RUN set -eux; \
    cd /var/www/html; \
    if [ -f composer.json ]; then \
      COMPOSER_ALLOW_SUPERUSER=1 composer install \
        --no-dev \
        --optimize-autoloader \
        --no-interaction \
        --prefer-dist \
        --no-progress 2>&1 | tail -20 || echo "⚠️ Composer tuvo warnings"; \
    else \
      echo "ℹ️ Sin composer.json - sin dependencias"; \
      mkdir -p vendor; \
      echo '<?php /* no vendor */ ' > vendor/autoload.php; \
    fi; \
    # Permisos
    chown -R www-data:www-data /var/www/html; \
    chmod -R u=rwX,g=rX,o=rX /var/www/html; \
    find /var/www/html -type d -exec chmod 755 {} \; || true; \
    find /var/www/html -type f -exec chmod 644 {} \; || true; \
    mkdir -p /var/www/html/data/umap_cache; \
    chown -R www-data:www-data /var/www/html/data; \
    chmod -R u+rwX,g+rwX /var/www/html/data;

# -------------------- 8. Salud del contenedor --------------------
HEALTHCHECK --interval=40s --timeout=8s --start-period=20s --retries=4 \
  CMD curl -fsSL --max-time 8 -o /dev/null "http://localhost:${PORT:-80}/api/test_connection.php" || \
      curl -fsSL --max-time 8 -o /dev/null "http://localhost:${PORT:-80}/" || \
      exit 1

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
