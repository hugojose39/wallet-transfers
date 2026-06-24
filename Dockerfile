FROM hyperf/hyperf:8.1-alpine-v3.18-swoole-v5

ARG timezone

ENV TIMEZONE=${timezone:-"America/Sao_Paulo"}

RUN set -ex \
    && apk add --no-cache \
       php81-pecl-xdebug \
    && cd /etc/php* \
    && echo "disable_functions=" > conf.d/00-enable-pcntl.ini \
    && { \
        echo "zend_extension=xdebug.so"; \
        echo "xdebug.mode=coverage"; \
        echo "xdebug.start_without_request=yes"; \
        echo "memory_limit=1G"; \
        echo "date.timezone=${TIMEZONE}"; \
    } | tee conf.d/50_xdebug.ini \
    && rm -rf /var/cache/apk/* /tmp/* \
    && echo -e "\033[42;37m Build Completed :).\033[0m\n"

WORKDIR /var/www/html

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 9501

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php", "bin/hyperf.php", "start"]
