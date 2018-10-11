#!/usr/bin/env bash

set -e

OPT_CLEAN=0
OPT_RESET=0
OPT_BUILD=0
OPT_RUN=0
OPT_MIGRATE=0
OPT_MIGRATE_FRESH=0
OPT_LOGS=0
OPT_LOGS_FOLLOW=0
OPT_STOP=0

while [ "$1" != "" ]
do
    case "$1" in
        --clean )
            OPT_CLEAN=1
        ;;
        --force | --hard )
            OPT_RESET=1
        ;;
        --build )
            OPT_BUILD=1
        ;;
        --run )
            OPT_RUN=1
        ;;
        --migrate )
            OPT_MIGRATE=1
        ;;
        --migrate-fresh )
            OPT_MIGRATE=1
            OPT_MIGRATE_FRESH=1
        ;;
        --logs )
            OPT_LOGS=1
        ;;
        --logs-follow )
            OPT_LOGS=1
            OPT_LOGS_FOLLOW=1
        ;;
        --stop )
            OPT_STOP=1
        ;;
    esac
    shift
done

if [ ${OPT_STOP} -eq 1 ]
then
    OPT_CLEAN=0
    OPT_RESET=0
    OPT_BUILD=0
    OPT_RUN=0
    OPT_MIGRATE=0
    OPT_MIGRATE_FRESH=0
    OPT_LOGS=0
    OPT_LOGS_FOLLOW=0
fi

envFiles=(
    .env
    docker-compose.override.yaml
)

# Docker Compose

export SYSTEM_USER_ID=`id --user`
export SYSTEM_USER_NAME=`id --user --name`

export WEBSERVER_PORT=${WEBSERVER_PORT:-8101}
export WEBMAILER_PORT=${WEBMAILER_PORT:-8025}

# AdServer ====================================================

export MAIL_HOST=${MAIL_HOST:-mailer}
export MAIL_PORT=${MAIL_PORT:-1025}
export MAIL_USERNAME=${MAIL_USERNAME}
export MAIL_PASSWORD=${MAIL_PASSWORD}
export MAIL_ENCRYPTION=${MAIL_ENCRYPTION:-null}
export MAIL_FROM_ADDRESS=${MAIL_FROM_ADDRESS:-dev@adshares.net}
export MAIL_FROM_NAME=${MAIL_FROM_NAME:-'[dev] AdShares'}

export ADSERVER_URL=${ADSERVER_URL:-http://localhost:8101}

export ADPANEL_URL=${ADPANEL_URL:-http://localhost:8102}

export ADSHARES_ADDRESS=${ADSHARES_ADDRESS:-0001-00000001-8B4E}
export ADSHARES_NODE_HOST=${ADSHARES_NODE_HOST:-esc.dock}
export ADSHARES_NODE_PORT=${ADSHARES_NODE_PORT:-9081}
export ADSHARES_SECRET=${ADSHARES_SECRET:-secret}

export ADUSER_LOCAL_ENDPOINT=${ADUSER_LOCAL_ENDPOINT:-http://webserver}

export APP_ENV=${APP_ENV:-local}
export APP_DEBUG=${APP_DEBUG:-true}

# =============================================================

if [ ${OPT_STOP} -eq 1 ]
then
    echo " > Stop containers"
    docker-compose stop && echo " < DONE"
fi

if [ ${OPT_CLEAN} -eq 1 ]
then
    echo " > Destroy containers"
    docker-compose down && echo " < DONE"

    if [ ${OPT_RESET} -eq 1 ]
    then
        echo " > Remove 'vendor'"
        rm -rf vendor
    fi
fi

for envFile in "${envFiles[@]}"
do
    if [ ${OPT_RESET} -eq 1 ]
    then
        echo " > Remove $envFile"
        rm -f "$envFile"
    fi

    echo " > Creating '$envFile'..."
    if [ -f "$envFile" ]
    then
        envsubst < "$envFile.dist" | tee "$envFile" && echo " < DONE"
    else
        echo " < INFO: Already gone"
    fi
done

if [ ${OPT_BUILD} -eq 1 ]
then
    docker-compose run --rm worker composer install
    if [ ${OPT_RESET} -eq 1 ]
    then
        docker-compose run --rm worker php artisan key:generate
    fi

    docker-compose run --rm worker php artisan package:discover
    docker-compose run --rm worker php artisan browsercap:updater

    docker-compose run --rm worker yarn install
    docker-compose run --rm worker yarn run dev
fi

[ ${OPT_STOP} -eq 1 ] || chmod a+w -R storage || echo " < ERROR: Change permisisons to 'storage'" && exit 127

if [ ${OPT_RUN} -eq 1 ]
then
    docker-compose up --detach
fi

if [ ${OPT_MIGRATE} -eq 1 ]
then
    if [ ${OPT_MIGRATE_FRESH} -eq 1 ]
    then
        echo " > Recreate database"
        if [ ${OPT_RUN} -eq 1 ]
        then
            docker-compose exec -T worker ./artisan migrate:fresh
        else
            docker-compose run --rm worker ./artisan migrate:fresh
        fi
    else
        echo " > Update database"
        if [ ${OPT_RUN} -eq 1 ]
        then
            docker-compose exec -T worker ./artisan migrate
        else
            docker-compose run --rm worker ./artisan migrate:
        fi
    fi
fi

if [ ${OPT_LOGS} -eq 1 ]
then
    if [ ${OPT_LOGS_FOLLOW} -eq 1 ]
    then
        docker-compose logs -f
    else
        docker-compose logs
    fi
fi
