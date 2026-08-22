FROM php:8.2-cli

# Instalar extensiones PDO y PDO MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos
COPY . .

# Exponer puerto
EXPOSE 8080

# Comando para iniciar el servidor
CMD ["php", "-S", "0.0.0.0:8080", "-t", "."]
