# ==============================================================================
# VASPA - Dockerfile Multi-Etapa para PHP 8.2 Apache
# Validador y Administrador de Programas Analíticos (UARG-UNPA)
# ==============================================================================

# ------------------------------------------------------------------------------
# Etapa 1: Base de Runtime con PHP 8.2 y Extensiones Requeridas
# ------------------------------------------------------------------------------
FROM php:8.2-apache AS base

# Configuración de zona horaria y variables no interactivas
ENV DEBIAN_FRONTEND=noninteractive \
    TZ=America/Argentina/Rio_Gallegos

# Instalación de dependencias del sistema requeridas para extensiones
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    git \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        mysqli \
        pdo_mysql \
        mbstring \
        fileinfo \
        gd \
        zip \
        opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Habilitación de módulos Apache esenciales para VASPA
RUN a2enmod rewrite headers ssl

# Configuración de endurecimiento para Apache y PHP (Seguridad en Profundidad)
RUN { \
        echo 'ServerTokens Prod'; \
        echo 'ServerSignature Off'; \
        echo 'TraceEnable Off'; \
    } >> /etc/apache2/conf-available/security.conf \
    && a2enconf security

RUN { \
        echo 'expose_php = Off'; \
        echo 'display_errors = Off'; \
        echo 'display_startup_errors = Off'; \
        echo 'log_errors = On'; \
        echo 'error_log = /var/log/apache2/php_errors.log'; \
        echo 'upload_max_filesize = 15M'; \
        echo 'post_max_size = 16M'; \
        echo 'memory_limit = 256M'; \
        echo 'max_execution_time = 120'; \
        echo 'session.cookie_httponly = 1'; \
        echo 'session.use_only_cookies = 1'; \
        echo 'session.cookie_samesite = "Lax"'; \
        echo 'date.timezone = "America/Argentina/Rio_Gallegos"'; \
    } > /usr/local/etc/php/conf.d/vaspa-security.ini

# Configuración del VirtualHost para soporte completo de .htaccess
RUN { \
        echo '<VirtualHost *:80>'; \
        echo '    ServerAdmin webmaster@uarg.unpa.edu.ar'; \
        echo '    DocumentRoot /var/www/html'; \
        echo '    <Directory /var/www/html>'; \
        echo '        Options -Indexes +FollowSymLinks'; \
        echo '        AllowOverride All'; \
        echo '        Require all granted'; \
        echo '    </Directory>'; \
        echo '    ErrorLog ${APACHE_LOG_DIR}/vaspa_error.log'; \
        echo '    CustomLog ${APACHE_LOG_DIR}/vaspa_access.log combined'; \
        echo '</VirtualHost>'; \
    } > /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

# Copiar el código fuente de la aplicación
COPY . /var/www/html/

# Asegurar directorios de escritura y permisos adecuados
RUN mkdir -p /var/www/html/archivos/programas \
             /var/www/html/programas \
             /var/www/html/test-results \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/archivos \
    && chmod -R 775 /var/www/html/programas

# Puerto expuesto
EXPOSE 80

# Verificación de salud del contenedor
HEALTHCHECK --interval=30s --timeout=5s --start-period=15s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# Comando de inicio del servidor Apache
CMD ["apache2-foreground"]
