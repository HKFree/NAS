#!/bin/bash

if [ -f $1 ]; then
  if [ -f $2 ]; then
    cp $1 /etc/rsyncd.conf
    cp $2 /etc/rsyncd.secrets
    chown root:root /etc/rsyncd.secrets
    chmod 640 /etc/rsyncd.secrets
    echo "ok"
  fi
fi
