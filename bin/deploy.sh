#!/bin/sh
ssh webcie@svcover.nl "cd www/www; git fetch; git reset --hard origin/master; composer.phar install --no-dev"

