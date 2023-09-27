FROM php:8.2.10-alpine

COPY --from=composer/composer:2-bin /composer /usr/bin/composer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
COPY ./entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh && \
    apk add --no-cache bash && \
    install-php-extensions pcntl sockets

WORKDIR /app
EXPOSE 25

ENTRYPOINT ["/entrypoint.sh"]
CMD ["/bin/bash"]
