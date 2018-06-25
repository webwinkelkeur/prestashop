#!/bin/sh

set -eu

cd /

if [ ! -d /var/lib/mysql ]; then
    cp -a /var/lib/mysql.fresh /var/lib/mysql
fi

mkdir -p /run/apache2
deluser apache >/dev/null 2>&1 || true
delgroup apache >/dev/null 2>&1 || true
addgroup -S apache
adduser -S -u `stat -c %u /var/www/localhost/htdocs` -H -G apache apache

mkdir -p /run/mysqld
chown mysql:mysql /run/mysqld

sudo -u mysql mysqld_safe &
httpd -D FOREGROUND
