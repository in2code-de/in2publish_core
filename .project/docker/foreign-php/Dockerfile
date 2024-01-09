FROM in2code/php-dev:8.1-fpm AS base

RUN mkdir -m 700 -p /home/app/.ssh/
COPY --chown=1000:1000 authorized_keys /home/app/.ssh/

USER root
RUN chmod 600 /home/app/.ssh/authorized_keys \
    && apt-get update \
	&& apt-get install -y --no-install-recommends openssh-server \
	&& docker-php-ext-install exif \
	&& mkdir /run/sshd/ \
	&& ssh-keygen -A \
	&& for key in $(ls /etc/ssh/ssh_host_* | grep -v pub); do echo "HostKey $key" >> /etc/ssh/sshd_config; done \
	&& apt-get autoremove -y \
	&& apt-get clean \
	&& echo "php_admin_value[memory_limit] = 256M" >> /usr/local/etc/php-fpm.d/www.conf \
	&& echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/zz_custom.ini \
    && echo "xdebug.mode=off" > /usr/local/etc/php/conf.d/zz_xdebug.ini

COPY docker-php-entrypoint /usr/local/bin/docker-php-entrypoint

EXPOSE 22

FROM base AS local

COPY xdebug.ini /usr/local/etc/php/conf.d/zz_xdebug.ini
