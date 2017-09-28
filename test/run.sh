#!/usr/bin/env bash


if [ $# -gt 0 ]
then
    ps_version=$1
else
    ps_version='latest'
fi;

no_tests='false'
if [ $# -gt 1 ] && [ $2 = "--no-tests" ]
then
    no_tests='true'
fi;

echo "Starting test for $ps_version"
set -u

function cleanup_container {
    echo "Stopping $1"
    docker stop $1
    echo "Removing $1"
    docker rm $1
}

curdir="$(dirname "$0")"
if [ ! -f $curdir/../dist/prestashop-webwinkelkeur.zip ]
then
    echo "Generating module package"
    $curdir/../bin/package
    if [ $? -ne 0 ]
    then
        echo "Failed creating package"
        exit 1
    fi;
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
        -e PS_ERASE_DB=1 \
        -e PS_FOLDER_ADMIN=admin1 \
        -e PS_FOLDER_INSTALL=install1 \
        -e ADMIN_MAIL='autotester@kiboit.com' \
        -e ADMIN_PASSWD=tester \
        -d prestashop/prestashop:$ps_version \
        /bin/bash -c 'sed "s/http:\/\/www\.unicode\.org\/repos\/cldr-aux\/json\/26/http:\/\/i18n.prestashop.com\/cldr\/json-full/" \
        </var/www/html/vendor/icanboogie/cldr/lib/WebProvider.php >/var/www/html/vendor/icanboogie/cldr/lib/WebProvider.php && \
        /tmp/docker_run.sh'`


if [ $? != 0 ]
then
    echo "Container failed"
    cleanup_container $ps_id
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

if [ $no_tests = "true" ]
then
    read -n 1 -s -r -p "Press any key to end"
else
    echo "Starting tests for $ps_version"
    node single-run.js --root-url="http://$ps_ip" --version=$ps_version "$@"
fi;

cleanup_container $ps_id
cleanup_container $db_id

exit 0


