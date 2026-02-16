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
  git clone https://github.com/metatooth/metatooth.git $BUILD_DIR
  cd $BUILD_DIR
  git checkout $BRANCH
fi

cd $BUILD_DIR

rm -rf $DEPLOYMENT_DIR
mkdir -p $DEPLOYMENT_DIR

cp -r $BUILD_DIR/docker-compose.yml $DEPLOYMENT_DIR/docker-compose.yml
cp -r $BUILD_DIR/Gemfile $DEPLOYMENT_DIR/Gemfile
cp -r $BUILD_DIR/Gemfile.lock $DEPLOYMENT_DIR/Gemfile.lock
cp -r $BUILD_DIR/config.ru $DEPLOYMENT_DIR/config.ru
cp -r $BUILD_DIR/init.rb $DEPLOYMENT_DIR/init.rb
cp -r $BUILD_DIR/Procfile $DEPLOYMENT_DIR/Procfile
cp -r $BUILD_DIR/app $DEPLOYMENT_DIR/app
cp -r $BUILD_DIR/config $DEPLOYMENT_DIR/config
cp -r $BUILD_DIR/db $DEPLOYMENT_DIR/db
