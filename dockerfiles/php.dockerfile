FROM debian:buster
MAINTAINER visualio

ARG UID
ARG GID

ENV UID=${UID}
ENV GID=${GID}

ENV DEBIAN_FRONTEND noninteractive
ENV NETTE_DEBUG 1

RUN apt-get update && \
  apt-get -yqq install apt-transport-https  ca-certificates \
  vim unzip \
  wget curl git ssh

RUN \
  wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg && \
  echo "deb https://packages.sury.org/php/ buster main" > /etc/apt/sources.list.d/php.list && \
apt-get -qq update && apt-get -qqy upgrade

RUN  apt-get -qq update && apt-get install -y \
   libapache2-mod-php8.2 php8.2-cgi \
   php8.2-cli        php8.2-gd      php8.2-common    php8.2-intl    \
   php8.2-mbstring     php8.2-mysql    php8.2-opcache      php8.2-readline       php8.2-xml    php8.2-xsl   \
   php8.2-zip   php8.2-bcmath   php8.2-sqlite3 php8.2-curl  \
   apache2


RUN apt-get update && \
  apt-get -yqq install msmtp

#COPY msmtprc /etc/msmtprc
COPY php.ini /etc/php/8.2/cli/php.ini
COPY php.ini /etc/php/8.2/apache2/php.ini

ADD site.conf /etc/apache2/sites-enabled/000-default.conf

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer


RUN mkdir -p /var/www/project && a2enmod vhost_alias ssl  rewrite headers

#aby apache log sel na stdout dockeru
RUN ln -sf /proc/self/fd/1 /var/log/apache2/access.log && \
    ln -sf /proc/self/fd/1 /var/log/apache2/error.log

RUN chmod 777 /var/lib/php/sessions
RUN addgroup --gid ${GID} --system nette
RUN adduser --ingroup nette --system --disabled-password --shell /bin/sh --uid ${UID} nette
RUN sed -i "s/APACHE_RUN_USER=www-data/APACHE_RUN_USER=nette/g" /etc/apache2/envvars
RUN sed -i "s/APACHE_RUN_GROUP=www-data/APACHE_RUN_GROUP=nette/g" /etc/apache2/envvars

EXPOSE 80 443

CMD  /usr/sbin/apache2ctl -D FOREGROUND