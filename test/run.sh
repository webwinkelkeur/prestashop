#!/bin/bash

db_container_name="ps-db"

ps_container_name="prestashop-testing"
ps_version='latest'


if [ ! -z "$(docker ps -a | grep $ps_container_name)" ]
    then
        echo "Remove old prestashop"
        docker stop $ps_container_name
        docker rm $ps_container_name
fi;

if [ ! -z "$(docker ps -a | grep $db_container_name)" ]
    then
        echo "Removing old database"
        docker stop $db_container_name
        docker rm $db_container_name
fi;

docker run \
    -ti --name $db_container_name \
    -e MYSQL_ROOT_PASSWORD=admin \
    -d mariadb

docker run \
    --name $ps_container_name \
    --link $db_container_name \
    -e DB_SERVER=$db_container_name \
    -e PS_DEV_MODE=1 \
    -e PS_INSTALL_AUTO=1 \
    -e PS_ERASE_DB=1 \
    -e PS_FOLDER_ADMIN=admin1 \
    -e PS_FOLDER_INSTALL=install1 \
    -e ADMIN_MAIL='autotester@kiboit.com' \
    -e ADMIN_PASSWD=tester \
    -p 8081:80 \
    prestashop/prestashop:$ps_version
