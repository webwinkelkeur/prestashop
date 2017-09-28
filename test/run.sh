#!/bin/bash

set -euo pipefail
cd "$(dirname "$0")"

containers=()
trap cleanup_containers EXIT

cleanup_containers() {
    if [[ ${#containers[@]} -gt 0 ]]
    then
        docker rm -f "${containers[@]}"
    fi
}

no_tests=false
ps_version=latest
has_ps_version=0
extra_args=()

while [[ $# -gt 0 ]]
do
    arg="$1"
    shift

    case $arg in
    --no-tests)
        no_tests=true
        ;;
    --*)
        extra_args+=( $arg )
        ;;
    *)
        if [[ $has_ps_version -eq 1 ]]
        then
            echo "Dangling argument: $arg" >&2
            exit 1
        fi

        if [[ $arg = "" ]]
        then
            echo "Version argument must not be empty" >&2
            exit 1
        fi

        ps_version=$arg
        has_ps_version=1
        ;;
    esac
done

echo "Starting test for $ps_version"

if [[ ! -f ../dist/prestashop-webwinkelkeur.zip ]]
then
    echo "Generating module package"
    ../bin/package
fi

echo "Starting DB container for $ps_version"
db_id=`docker run \
        -ti \
        -e MYSQL_ROOT_PASSWORD=admin \
        -d mariadb`
containers+=( $db_id )
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
        /bin/bash -c 'sed "s~http://www\.unicode\.org/repos/cldr-aux/json/26~http://i18n.prestashop.com/cldr/json-full~" \
        </var/www/html/vendor/icanboogie/cldr/lib/WebProvider.php >/var/www/html/vendor/icanboogie/cldr/lib/WebProvider.php; \
        bash -x /tmp/docker_run.sh'`

containers+=( $ps_id )

ps_ip=`docker inspect --format='{{.NetworkSettings.Networks.bridge.IPAddress}}' $ps_id`
echo "Prestashop ip for $ps_version is $ps_ip"

echo "Waiting for prestashop $ps_version to start..."
while :
do
    docker ps -q --no-trunc | grep -xFq $ps_id || {
        echo "Prestashop container stopped running" >&2
        docker logs -f $ps_id
        exit 1
    }

    curl -m5 $ps_ip >/dev/null 2>&1 && break
    sleep .5
done

if [ $no_tests = "true" ]
then
    read -n 1 -s -r -p "Press any key to end"
else
    echo "Starting tests for $ps_version"

    set +u  # empty arrays are unbound
    node single-run.js --root-url="http://$ps_ip" --db-server=$db_ip --version=$ps_version "${extra_args[@]}"
fi
