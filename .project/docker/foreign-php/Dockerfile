FROM in2code/php-dev:8.3-fpm AS base

RUN mkdir -m 700 -p /home/app/.ssh/
COPY --chown=1000:1000 authorized_keys /home/app/.ssh/

USER root
RUN chmod 600 /home/app/.ssh/authorized_keys \
    && apt-get update \
	&& apt-get install -y --no-install-recommends openssh-server \
	&& docker-php-ext-install exif \
	&& mkdir -p /run/sshd/ \
	&& ssh-keygen -A \
	&& for key in $(ls /etc/ssh/ssh_host_* | grep -v pub); do echo "HostKey $key" >> /etc/ssh/sshd_config; done \
	&& apt-get autoremove -y \
	&& apt-get clean

COPY docker-php-entrypoint /usr/local/bin/docker-php-entrypoint

EXPOSE 22

FROM base AS local

COPY zz_custom.ini /tmp/zz_custom.ini
COPY www.conf /tmp/www.conf
COPY xdebug.ini /usr/local/etc/php/conf.d/zz_xdebug.ini

RUN { echo; cat /tmp/zz_custom.ini; } >> /usr/local/etc/php/conf.d/zz_custom.ini && rm /tmp/zz_custom.ini
RUN { echo; cat /tmp/www.conf; } >> /usr/local/etc/php-fpm.d/www.conf && rm /tmp/www.conf


