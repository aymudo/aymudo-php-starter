FROM php:8.3-cli-alpine
RUN apk add --no-cache curl-dev git unzip && docker-php-ext-install curl
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY composer.json ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-scripts
COPY . .
RUN composer dump-autoload --no-dev --classmap-authoritative
EXPOSE 8080
CMD ["php","-S","0.0.0.0:8080","-t","public"]
