FROM nginx:1.15-alpine

RUN addgroup -g 1000 -S www-data && \
    adduser -u 1000 -D -S -G www-data www-data
RUN mkdir -p /var/log/nginx/ && \
    chown -R www-data:www-data /var/log/nginx

ADD nginx.conf /etc/nginx/nginx.conf
ADD conf.d /etc/nginx/conf.d
ADD includes /etc/nginx/includes

CMD ["nginx"]
