#!/bin/bash


source myutils.sh
source s3-buckets-func.sh


function usage() {
    echo "Usage: "$0" <bucket>"
    if [ -n "$1" ] ; then
	echo "Error: "$1"!"
    fi
    exit
}


if [ ! $# -eq 1 ] ; then
    usage
fi

bucket=$1

f="/tmp/s3bf-"$(date +%s)
`echo $(date) > $f`

test $bucket

rm $f
exit
