#!/bin/zsh
# Script for analyzing the output of test:response-times
# 
FILE=$1
ERROR_FILE=$2
TOTAL=`cat $FILE | wc -l`
BCSCRIPT="scale=4; (`cat $FILE | grep -v div | grep -v li |perl -pe 's/^([0-9.]+).*\n/\1 + /g'` 0) / `cat $FILE | wc -l`";
AVERAGE=`echo "$BCSCRIPT" | bc`

echo "Total samples: "$TOTAL
echo "Average responsite time: "$AVERAGE

if [ -e $ERROR_FILE ]; then
    ERROR_TOTAL=`cat $ERROR_FILE | wc -l`
    PERCENTAGE=`echo "scale=4;\n "$ERROR_TOTAL" / "$TOTAL" * 100" | bc`
    echo "Number of errored requests: "$ERROR_TOTAL
    echo "Percentage Error: "$PERCENTAGE"%"
fi

FAILED_SITES=`cat $ERROR_FILE | egrep ".(fr|com)\?" | grep -v deleted | wc -l `

echo "Total failed sites: "$FAILED_SITES

cat $ERROR_FILE | egrep ".(fr|com)\?" | grep -v deleted
