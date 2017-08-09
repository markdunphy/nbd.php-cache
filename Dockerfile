FROM bryanlatten/docker-php:7.1
MAINTAINER Bryan Latten <latten@adobe.com>

RUN phpenmod redis
COPY ./ /app

RUN cd /app && composer install
