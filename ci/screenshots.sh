#!/usr/bin/sh
OUTPUT_DIR="`pwd`/output/screenshots"
THUMB_SIZE="400x400"

if [ ! -e "$OUTPUT_DIR" ]
then
    mkdir -p "$OUTPUT_DIR"
fi

if [ ! -e "$OUTPUT_DIR"/$THUMB_SIZE ]
then
    mkdir -p "$OUTPUT_DIR"/$THUMB_SIZE
fi

rm $OUTPUT_DIR/original/*

php bin/test.php test:screenshots http://admin.plombierweb.fr/platformmap.xml "$OUTPUT_DIR/original"
#php bin/test.php test:screenshots --use-base-url=http://yprox.localhost http://yprox.localhost/platformmap.xml $OUTPUT_DIR/original
mogrify -auto-orient  -thumbnail 400x400 -unsharp 0x.5 -path "$OUTPUT_DIR/$THUMB_SIZE" "$OUTPUT_DIR/original/*.png"
xsltproc ci/screenshot.xsl output/screenshots/original/screenshots.xml > output/screenshots/index.html
