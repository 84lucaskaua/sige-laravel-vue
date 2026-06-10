# ============================================================
# Dockerfile do Backend (PHP-FPM + Laravel)
# Usa PHP 8.2 com FPM (FastCGI Process Manager)
# ============================================================

FROM php:8.2-fpm

# Instala dependências do sistema que o PHP precisa
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    && apt-get clean

# Instala extensões do PHP usadas pelo Laravel
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd

# Instala o Composer (gerenciador de pacotes do PHP)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Define a pasta de trabalho dentro do container
WORKDIR /var/www

# Copia os arquivos do projeto para dentro do container
COPY . .

# Instala as dependências PHP do projeto
RUN composer install --no-interaction --no-dev --optimize-autoloader

# Dá permissão de escrita nas pastas que o Laravel precisa
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Expõe a porta 9000 (usada pelo PHP-FPM)
EXPOSE 9000

# Comando que inicia o PHP-FPM
CMD ["php-fpm"]
