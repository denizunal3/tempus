#!/bin/sh

cd ~/tempus;
docker build -f ./docker/prod/Dockerfile -t tempus .;
cd -;
cd ~/solidtime;
docker compose up -d;
cd -;
