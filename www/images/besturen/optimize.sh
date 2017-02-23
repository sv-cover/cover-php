#!/bin/sh
for image in *.jpg; do convert "$image" -resize 1000 "$image"; done
