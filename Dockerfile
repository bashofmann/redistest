FROM composer AS composer

COPY composer.* /app/
RUN composer install

RUN ls -la vendor

FROM php:7.3-cli

RUN mkdir /redis-test

WORKDIR /redis-test

COPY --from=composer /app/vendor /redis-test/vendor

RUN apt-get update && apt-get install -y libhiredis-dev

RUN curl -fsSL 'https://github.com/nrk/phpiredis/archive/v1.0.0.tar.gz' -o phpiredis.tar.gz \
	&& mkdir -p phpiredis \
	&& tar -xf phpiredis.tar.gz -C phpiredis --strip-components=1 \
	&& rm phpiredis.tar.gz \
	&& ( \
		cd phpiredis \
		&& phpize \
		&& ./configure --enable-phpiredis \
		&& make -j "$(nproc)" \
		&& make install \
	) \
	&& rm -r phpiredis \
	&& docker-php-ext-enable phpiredis

COPY redis-test.php /redis-test/redis-test.php

RUN php -m && php -l /redis-test/redis-test.php