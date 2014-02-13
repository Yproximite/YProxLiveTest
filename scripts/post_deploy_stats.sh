#!/usr/bin

DATE=`date +Y%m%d`
ERROR_LOG="logs/prod-errors-"$DATE".log"
RESPONSE_LOG="logs/prod-times-"$DATE".log"
REPORT=output/post_deploy_stats_report-$DATE.txt

php bin/test.php test:response-time 
  --write-errors="prod-errors-`date +%Y%m%d`.log" \
  --write-response-times="prod-times-`date +%Y%m%d`.log" \
  http://admin.yproximite.fr/platformmap.xml

sh scripts/response-average.sh $RESPONSE_LOG $ERROR_LOG > $REPORT

cat $REPORT | mail dev@y-proximite.com -s "Deployment statistics "$DATE
