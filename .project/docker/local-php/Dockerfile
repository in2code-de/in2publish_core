FROM in2code/php-dev:8.1-fpm AS base

ARG USER_ID=1000
ARG GROUP_ID=1000

USER root

RUN usermod -u $USER_ID app \
    && groupmod -g $GROUP_ID app \
    && mkdir -m 700 -p /home/app/.ssh/ \
    && curl -fsSL https://get.docker.com | sh \
    && usermod -aG docker app \
    && usermod -aG systemd-journal app
COPY id_ed25519 id_ed25519.pub /home/app/.ssh/
RUN chmod 400 /home/app/.ssh/id* \
    && chown -R $USER_ID:$GROUP_ID /home/app/

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

USER $USER_ID:$GROUP_ID

FROM base AS local

COPY xdebug.ini /usr/local/etc/php/conf.d/zz_xdebug.ini
