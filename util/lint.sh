#!/bin/sh

if [ ! -e "index.php" ]
then
	echo "$0: Please execute this script from root directory."
	exit 1
fi

echo "$0: Analysing PHP scripts for syntax errors (lint) ..."
LINT=`find -type f -name "*.php" -exec php -l -f {} 2>&1 \; | grep -v "No syntax errors detected in" | grep -v "FUSE_EDEADLK" | sort --unique`

if [ -n "${LINT}" ]
then
	echo "${LINT}"
else
	echo "$0: No syntax errors found."
fi
