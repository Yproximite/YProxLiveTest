#!/usr/bin/sh
OUTPUT_DIR="`pwd`/output"
FILTER="&.*&"
if [ $1 ] 
then
    FILTER=$1
fi

rm -Rf output/*
php bin/test.php test:response-time --use-base-url=YPROX_BASE_URL --host-filter="$1" --write-errors="$OUTPUT_DIR/errors.log" --write-response-times="$OUTPUT_DIR/response.log" http://admin.plombierweb.fr/platformmap.xml
cat "$OUTPUT_DIR/response.log" | perl -pe "s/([0-9\.]+).*/\$1/g" | awk '{total = total + $1; lines++}END{ print total / lines}' > "$OUTPUT_DIR/average-response.txt"
