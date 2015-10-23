#!/bin/bash

if [ "$1" = "-h" ]; then
  cp $1 /etc/exports
  exportfs -ra
fi