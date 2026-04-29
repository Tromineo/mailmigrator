FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    imapsync \
    nginx

COPY nginx.conf /etc/nginx/http.d/default.conf
COPY . /var/www/html/
COPY entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh

EXPOSE 80

CMD ["/entrypoint.sh"]
