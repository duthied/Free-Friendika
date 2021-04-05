#!/bin/bash

PHPUNIT="$(git rev-parse --show-toplevel)/bin/phpunit"

if ! [ -x "$PHPUNIT" ]; then
	echo "Install PHPUnit 8"
	cd /tmp/
	curl -s -O -L https://phar.phpunit.de/phpunit-8.phar
	chmod +x phpunit-8.phar
	mv phpunit-8.phar $PHPUNIT
fi

echo "Using $PHPUNIT $($PHPUNIT --version)"
