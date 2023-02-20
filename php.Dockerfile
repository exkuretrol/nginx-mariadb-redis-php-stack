FROM php:8.1.16-fpm-alpine3.17

RUN apk update \
	&& apk add oniguruma-dev \
	&& apk add autoconf build-base \
	&& apk add --update linux-headers \
	&& pecl install xdebug \
	&& pecl install redis \
	&& docker-php-ext-install mbstring \
	&& docker-php-ext-enable redis xdebug
