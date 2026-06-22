FROM hyperf/hyperf:8.1-alpine-v3.18-swoole

WORKDIR /var/www/html

COPY composer.json composer.lock* ./

RUN composer install --no-scripts --no-autoloader --prefer-dist

COPY . .

RUN composer dump-autoload --optimize

EXPOSE 9501

CMD ["php", "bin/hyperf.php", "start"]
