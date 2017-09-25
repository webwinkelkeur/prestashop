#!/bin/bash

if [ $# -gt 0 ]
then
    ps_version=$1
else
    ps_version='latest'
fi;
echo "Starting test for $ps_version"

set -u

function cleanup_container {
    echo "Stopping $1"
    docker stop $1
    echo "Removing $1"
    docker rm $1
}

echo "Generating module package"
curdir="$(dirname "$0")"
$curdir/../bin/package
if [ $? -ne 0 ]
then
    echo "Failed creating package"
    exit 1
fi;

echo "Starting DB container for $ps_version"
db_id=`docker run \
        -ti \
        -e MYSQL_ROOT_PASSWORD=admin \
        -d mariadb`
if [ $? != 0 ]
then
    echo "Container failed"
    cleanup_container $db_id
    exit 1
fi;
echo "DB container for $ps_version is $db_id"

db_ip=`docker inspect --format='{{.NetworkSettings.Networks.bridge.IPAddress}}' $db_id`
echo "DB ip for $ps_version is $db_ip"

echo "Starting prestashop container for $ps_version"
ps_id=`docker run \
        --link $db_id \
        -e DB_SERVER=$db_ip \
        -e PS_DEV_MODE=1 \
        -e PS_INSTALL_AUTO=1 \
        -e PS_ERASE_DB=1 \
        -e PS_FOLDER_ADMIN=admin1 \
        -e PS_FOLDER_INSTALL=install1 \
        -e ADMIN_MAIL='autotester@kiboit.com' \
        -e ADMIN_PASSWD=tester \
        -d prestashop/prestashop:$ps_version`
if [ $? != 0 ]
then
    echo "Container failed"
    cleanup_container $ps_version
    cleanup_container $db_id
    exit 1
fi;
echo "Prestashop container for  $ps_version is $ps_id"


ps_ip=`docker inspect --format='{{.NetworkSettings.Networks.bridge.IPAddress}}' $ps_id`
echo "Prestashop ip for $ps_version is $ps_ip"

started=-1
while [ $started != 0 ]
do
    echo "Waiting for prestashop $ps_version to start..."
    sleep 1
    curl $ps_ip > /dev/null 2>&1
    started=$?
done

echo "Starting tests for $ps_version"
node single-run.js --root-url="http://$ps_ip" --headless=false --version=$ps_version

cleanup_container $ps_id
cleanup_container $db_id

exit 0


