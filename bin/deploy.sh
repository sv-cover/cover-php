#!/bin/sh
ssh webcie@svcover.nl "cd www/www; git pull origin master; composer.phar install"

