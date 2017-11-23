#!/bin/bash

ENV_CONSOLE_LOGO="[INIT SCRIPT]"

while getopts e:f: option
do
    case "${option}"
        in
        e) ENVIRONMENT=${OPTARG};;
        f) FORCE=$OPTARG;;
    esac
done

# If it's not specified then use the production version
if [ "$ENVIRONMENT" == "" ]; then
    ENVIRONMENT="production"
fi

echo "$ENV_CONSOLE_LOGO Setting up the $ENVIRONMENT environment."

# Install docker if not already installed
if ! [ -x "$(command -v docker)" ]; then
    echo "$ENV_CONSOLE_LOGO Docker is not installed, therefore installing."
    sudo apt-get -qq update && sudo apt-get install -y docker
else
    RUNNING=$(sudo docker ps -a --filter "name=php" --format "{{.ID}}: {{.Size}}")

    if [ "$RUNNING" == "" ]; then
        echo "$ENV_CONSOLE_LOGO PHP container is not running, therefore no need to stop & destroy existing."
    else
        sudo docker stop php && sudo docker rm php

        # Delete image
        sudo docker rmi php-apache-custom
        sudo docker stop mysql && sudo docker rm mysql
        sudo docker stop phpmyadmin && sudo docker rm phpmyadmin
    fi
fi

DIRECTORY="`dirname $0`"
FILELOCATION="$PWD/$DIRECTORY"

# Copy the correct docker compose file, depending on environment
cp $DIRECTORY/docker-compose.$ENVIRONMENT.yml $PWD/docker-compose.yml

# Compose dockers
echo "$ENV_CONSOLE_LOGO Starting the following file: $DIRECTORY/docker-compose.$ENVIRONMENT.yml"
sudo docker-compose up -d

# Delete the compose file afterwards
rm $PWD/docker-compose.yml