#!/bin/bash

BRANCH=$1

if [ -z "${BRANCH}" ]; then
  echo "usage: ./build.sh BRANCH"
  exit 1
fi

HERE=$( cd $( dirname "${BASH_SOURCE[0]}" ) >/dev/null 2>&1 && pwd )
TOPDIR=$( dirname ${HERE} )

TMP_DIR=/tmp/herbert
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
  HERBERT_DIR=${BUILD_DIR}
else
  git clone git@github.com:metatooth/metatooth.git $BUILD_DIR
  cd $BUILD_DIR
  git checkout $BRANCH
  HERBERT_DIR=${BUILD_DIR}/herbert
fi

rm -rf $DEPLOYMENT_DIR
mkdir -p $DEPLOYMENT_DIR

cp -r $HERBERT_DIR/src $DEPLOYMENT_DIR/
cp $HERBERT_DIR/package.json $DEPLOYMENT_DIR/
cp $HERBERT_DIR/package-lock.json $DEPLOYMENT_DIR/
