#!/bin/bash

if [ -f $1 ]; then
  cp $1 /etc/exports
  exportfs -ra
fi
