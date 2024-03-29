FROM composer:2.5 as builder

WORKDIR /tmp

COPY src/ src/
COPY composer.json composer.json
COPY composer.lock composer.lock

RUN composer install
RUN composer dump-autoload -o

FROM php:8.1-apache

COPY entrypoint.sh /usr/bin
RUN chmod +x /usr/bin/entrypoint.sh
COPY src/example /var/www/html/example
COPY src/tests /var/www/html/tests
COPY src/EduSharing /var/www/html/src/EduSharing
COPY --from=builder /tmp/vendor/ /var/www/html/vendor/
RUN mkdir /var/www/html/example/data

ENTRYPOINT ["/usr/bin/entrypoint.sh"]