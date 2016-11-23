FROM bryanlatten/docker-php:latest
MAINTAINER Bryan Latten <latten@adobe.com>

RUN phpenmod redis
COPY ./ /app

RUN cd /app && composer install
