#!/bin/bash
input=$(</dev/stdin)
socket=$1
shift

{ echo "$@" ; echo "$input" ; } | nc -w 1 -v -U "$socket"
