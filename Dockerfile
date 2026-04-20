FROM dunglas/frankenphp

WORKDIR /app

RUN install-php-extensions \
    pdo_mysql \
    opcache \
    redis \
    gd

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# copy project
COPY . .

# install dependency
RUN composer install --no-dev --optimize-autoloader

# permission
RUN chown -R www-data:www-data /app

EXPOSE 80

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
