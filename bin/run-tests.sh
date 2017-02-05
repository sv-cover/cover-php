#!/bin/sh
if [ -z "$1" ]; then
	vendor/phpunit/phpunit/phpunit --bootstrap vendor/autoload.php --verbose tests
else
	vendor/phpunit/phpunit/phpunit --bootstrap vendor/autoload.php --verbose "tests/$1.php"
fi
