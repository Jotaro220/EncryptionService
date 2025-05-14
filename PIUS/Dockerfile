# Используем официальный образ PHP
FROM php:8.2-cli

# Устанавливаем зависимости
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install zip

# Устанавливаем Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Копируем исходный код
COPY . /var/www/html
WORKDIR /var/www/html

# Устанавливаем зависимости Laravel (без dev-зависимостей)
RUN composer install --optimize-autoloader --no-dev

# Запускаем встроенный сервер Laravel
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

# Открываем порт 8000
EXPOSE 8000
