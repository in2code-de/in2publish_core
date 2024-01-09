FROM in2code/php-dev:8.1-fpm AS base

RUN mkdir -m 700 -p /home/app/.ssh/
COPY --chown=1000:1000 id_ed25519 id_ed25519.pub /home/app/.ssh/
RUN chmod 400 /home/app/.ssh/id*

USER root
RUN apt-get update \
	&& apt-get install -y --no-install-recommends libssh2-1-dev \
	&& pecl install ssh2-1.3.1 \
	&& docker-php-ext-install exif \
	&& docker-php-ext-enable ssh2 \
	&& apt-get autoremove -y \
	&& apt-get clean \
	&& echo "php_admin_value[memory_limit] = 256M" >> /usr/local/etc/php-fpm.d/www.conf \
	&& echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/zz_custom.ini \
    && echo "xdebug.mode=off" > /usr/local/etc/php/conf.d/zz_xdebug.ini

USER 1000:1000

FROM base AS local

COPY xdebug.ini /usr/local/etc/php/conf.d/zz_xdebug.ini
