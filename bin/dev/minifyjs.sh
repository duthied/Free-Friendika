#!/bin/bash

command -v uglifyjs >/dev/null 2>&1 || { echo >&2 "I require UglifyJS but it's not installed.  Aborting."; exit 1; }

MINIFY_CMD=uglifyjs

JSFILES=(
	"view/js/ajaxupload.js"
	"view/js/country.js"
	"view/js/main.js"
	"vendor/asset/base64/base64.min.js"
	"view/theme/frost/js/jquery.divgrow-1.3.1.f1.js"
	"view/theme/frost/js/main.js"
	"view/theme/frost/js/theme.js"
	"view/theme/frost-mobile/js/jquery.divgrow-1.3.1.f1.js"
	"view/theme/frost-mobile/js/main.js"
	"view/theme/frost-mobile/js/theme.js"
	"view/theme/decaf-mobile/js/theme.js"
)
JSFILES2=(
	"library/colorbox/jquery.colorbox.js"
)

for i in ${JSFILES[@]}
do
	MINFILE=${i%%.js}.min.js
	echo "Minifying $i into $MINFILE"
	rm $MINFILE
	$MINIFY_CMD -o $MINFILE $i
done

for i in ${JSFILES2[@]}
do
	MINFILE=${i%%.js}-min.js
	echo "Minifying $i into $MINFILE"
	rm $MINFILE
	$MINIFY_CMD -o $MINFILE $i
done

for i in ${JSFILES3[@]}
do
	MINFILE=${i%%_src.js}.js
	echo "Minifying $i into $MINFILE"
	rm $MINFILE
	$MINIFY_CMD -o $MINFILE $i
done

