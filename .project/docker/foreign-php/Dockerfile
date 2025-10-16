FROM in2code/php-dev:8.3-fpm

ARG USER_ID=1000
ARG GROUP_ID=1000

USER root

RUN usermod -u $USER_ID app \
    && groupmod -g $GROUP_ID app \
    && mkdir -m 700 -p /home/app/.ssh/
COPY --chown=$USER_ID authorized_keys /home/app/.ssh/
RUN chown -R $USER_ID:$GROUP_ID /home/app/

RUN chmod 600 /home/app/.ssh/authorized_keys \
    && apt-get update \
	&& apt-get install -y --no-install-recommends openssh-server \
	&& docker-php-ext-install exif \
	&& mkdir -p /run/sshd/ \
	&& ssh-keygen -A \
	&& for key in $(ls /etc/ssh/ssh_host_* | grep -v pub); do echo "HostKey $key" >> /etc/ssh/sshd_config; done \
	&& apt-get autoremove -y \
	&& apt-get clean \
	&& echo "php_admin_value[memory_limit] = 256M" >> /usr/local/etc/php-fpm.d/www.conf \
	&& echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/zz_custom.ini

COPY docker-php-entrypoint /usr/local/bin/docker-php-entrypoint

EXPOSE 22
