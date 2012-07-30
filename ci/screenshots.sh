#!/usr/bin/sh
PWD="`pwd`"
OUTPUT_DIR="`pwd`/output/screenshots/daily/`date +%Y%m%d`"
THUMB_SIZE_1="400x400"
THUMB_SIZE_2="24x24"
TODAY_DIR=`pwd`/output/screenshots/daily/today

if [ ! -e "$OUTPUT_DIR" ]
then
    mkdir -p "$OUTPUT_DIR"
fi

if [ ! -e "$OUTPUT_DIR"/$THUMB_SIZE_1 ]
then
    mkdir -p "$OUTPUT_DIR"/$THUMB_SIZE_1
fi

if [ ! -e "$OUTPUT_DIR"/$THUMB_SIZE_2 ]
then
    mkdir -p "$OUTPUT_DIR"/$THUMB_SIZE_2
fi

rm -f "$OUTPUT_DIR"/original/*


php bin/test.php test:screenshots --use-base-url=$YPROX_BASE_URL http://admin.yproximite.fr/platformmap.xml "$OUTPUT_DIR/original"
# php bin/test.php test:screenshots --use-base-url=http://yprox.localhost http://yprox.localhost/platformmap.xml $OUTPUT_DIR/original
mogrify -auto-orient -thumbnail 400x400 -unsharp 0x.5 -path "$OUTPUT_DIR/$THUMB_SIZE_1" "$OUTPUT_DIR/original/*.png"
mogrify -auto-orient -thumbnail $THUMB_SIZE_2 -unsharp 0x.5 -path "$OUTPUT_DIR/$THUMB_SIZE_2" "$OUTPUT_DIR/$THUMB_SIZE_1/*.png"

if [ -e "$TODAY_DIR" ]
then
    rm "$TODAY_DIR"
fi

cd "`pwd`/output/screenshots/daily"
ln -s "$OUTPUT_DIR" today
cd "$PWD"

xsltproc ci/screenshot.xsl "$OUTPUT_DIR/original/screenshots.xml" > "output/screenshots/index.html"

