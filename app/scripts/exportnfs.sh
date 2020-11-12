#!/bin/bash

if [ -f $1 ]; then
  cp $1 /etc/exports
  killall -s SIGHUP unfsd
  echo "ok"
fi
