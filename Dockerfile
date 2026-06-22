FROM hyperf/hyperf:8.1-alpine-v3.18-swoole-v5

WORKDIR /var/www/html

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 9501

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php", "bin/hyperf.php", "start"]
