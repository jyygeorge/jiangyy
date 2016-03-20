#!/bin/bash

function usage() {
    echo "Usage: "$0" <domain_file>"
    if [ -n "$1" ] ; then
	echo "Error: "$1"!"
    fi
    exit
}

if [ ! $# -eq 1 ] ; then
    usage
fi

file=$1
n=0

if [ ! -f $file ] ; then
    usage "file not found"
fi

for d in $(cat $file) ; do
    axfr=`fierce --dns $d -wordlist /tmp/a | grep 'Whoah, it worked' &`
    #axfr=`dnsrecon -t axfr -d $d | grep 'Zone Transfer was successful' &`
    echo $d
    if [ -n "$axfr" ] ; then
	echo $d" successful!"
	n=$[$n+1]
    fi
done

echo
echo $n" zone transfer performed."
exit
