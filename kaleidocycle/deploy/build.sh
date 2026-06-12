#!/bin/bash

BRANCH=$1
INVENTORY=$2

if [ -z "${BRANCH}" ] || [ -z "${INVENTORY}" ]; then
  echo "usage: ./build.sh BRANCH INVENTORY"
  exit 1
fi

HERE=$( cd $( dirname "${BASH_SOURCE[0]}" ) >/dev/null 2>&1 && pwd )
TOPDIR=$( dirname ${HERE} )

TMP_DIR=/tmp/kaleidocycle
rm -rf $TMP_DIR
mkdir -p $TMP_DIR

BUILD_DIR=${TMP_DIR}/${BRANCH}
DEPLOYMENT_DIR=${TMP_DIR}/deployment

cleanup() {
  rm -rfv $BUILD_DIR
}

trap "cleanup" exit

if [ "${BRANCH}" = "local" ]; then
  DOCKER_CONTEXT=${TOPDIR}
else
  git clone https://github.com/metatooth/kaleidocycle.git $BUILD_DIR
  cd $BUILD_DIR
  git checkout $BRANCH
  DOCKER_CONTEXT=${BUILD_DIR}
fi

rm -rf $DEPLOYMENT_DIR
mkdir -p $DEPLOYMENT_DIR

cp -r $DOCKER_CONTEXT/config $DEPLOYMENT_DIR/config

docker build -t kaleidocycle_app:latest $DOCKER_CONTEXT

docker save kaleidocycle_app:latest | gzip > $DEPLOYMENT_DIR/kaleidocycle_app_latest.tar.gz
