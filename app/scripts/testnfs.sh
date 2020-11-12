#!/bin/bash

if [ -f $1 ]; then
  unfsd -T -e $1
fi
