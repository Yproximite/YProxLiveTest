#!/usr/bin/sh
rm -Rf output/*
php bin/test.php test:response-time --write-errors="`pwd`/output/errors" --write-response-times="`pwd`/output/response" http://admin.plombierweb.fr/platformmap.xml
php bin/test.php test:screenshots http://admin.plombierweb.fr/platformmap.xml output/
