FROM alpine:latest

RUN apk add --no-cache \
        mysql \
        mysql-client \
        php5-apache2 \
        php5-bcmath \
        php5-bz2 \
        php5-calendar \
        php5-cli \
        php5-ctype \
        php5-curl \
        php5-dev \
        php5-dom \
        php5-enchant \
        php5-exif \
        php5-ftp \
        php5-gd \
        php5-gettext \
        php5-gmp \
        php5-iconv \
        php5-imap \
        php5-intl \
        php5-json \
        php5-mcrypt \
        php5-mysql \
        php5-mysqli \
        php5-openssl \
        php5-pcntl \
        php5-pdo \
        php5-pdo_mysql \
        php5-pear \
        php5-phar \
        php5-posix \
        php5-soap \
        php5-sockets \
        php5-sqlite3 \
        php5-xml \
        php5-xsl \
        php5-zip \
        php5-zlib \
        sudo

ADD my.cnf /etc/mysql/my.cnf
RUN sudo -u mysql mysql_install_db && mv /var/lib/mysql /var/lib/mysql.fresh

RUN rm -rf /var/www/localhost/htdocs && mkdir /var/www/localhost/htdocs

ADD entrypoint.sh /usr/local/bin/docker-entrypoint

ENTRYPOINT /usr/local/bin/docker-entrypoint
