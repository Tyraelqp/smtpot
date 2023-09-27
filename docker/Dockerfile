FROM php:8.2.10-alpine

COPY --from=composer/composer:2-bin /composer /usr/bin/composer
COPY ./entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh && \
    apk add --no-cache bash

WORKDIR /app
EXPOSE 25

ENTRYPOINT ["/entrypoint.sh"]
CMD ["/bin/bash"]
