FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    bash \
    curl \
    gettext \
    git \
    icu-dev \
    libpng-dev \
    libzip-dev \
    nginx \
    nodejs \
    npm \
    oniguruma-dev \
    postgresql-dev \
    supervisor \
    unzip \
    zip

RUN docker-php-ext-install \
    bcmath \
    gd \
    intl \
    pcntl \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

COPY package.json package-lock.json ./
RUN npm ci

COPY . .

RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache \
    && npm run build \
    && composer dump-autoload --optimize \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data /var/www/html

COPY render/nginx.conf /etc/nginx/templates/default.conf.template
COPY render/uploads.ini /usr/local/etc/php/conf.d/uploads.ini
COPY render/supervisord.conf /etc/supervisord.conf
COPY render/start-web.sh /usr/local/bin/start-web.sh
COPY render/run-scheduler.sh /usr/local/bin/run-scheduler.sh

RUN chmod +x /usr/local/bin/start-web.sh /usr/local/bin/run-scheduler.sh

EXPOSE 10000

CMD ["/usr/local/bin/start-web.sh"]
