#!/bin/bash

BRANCH=$1

if [ -z "${BRANCH}" ]; then
  echo "usage: ./build.sh BRANCH"
  exit 1
fi

HERE=$( cd $( dirname "${BASH_SOURCE[0]}" ) >/dev/null 2>&1 && pwd )
TOPDIR=$( dirname ${HERE} )

TMP_DIR=/tmp/metatooth.com
rm -rf $TMP_DIR
mkdir -p $TMP_DIR

BUILD_DIR=${TMP_DIR}/${BRANCH}
DEPLOYMENT_DIR=${TMP_DIR}/deployment

cleanup() {
  rm -rfv $BUILD_DIR
}

trap "cleanup" exit

if [ "${BRANCH}" = "local" ]; then
  ln -sf ${TOPDIR} ${BUILD_DIR}
else
  git clone https://github.com/metatooth/metatooth.com.git $BUILD_DIR
  cd $BUILD_DIR
  git checkout $BRANCH
fi

cd $BUILD_DIR

rm -rf $DEPLOYMENT_DIR
mkdir -p $DEPLOYMENT_DIR

cp -r $BUILD_DIR/docker-compose.yml $DEPLOYMENT_DIR/docker-compose.yml
cp -r $BUILD_DIR/nginx.conf $DEPLOYMENT_DIR/nginx.conf
cp -r $BUILD_DIR/metatooth-com $DEPLOYMENT_DIR/metatooth-com
