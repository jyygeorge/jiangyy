#!/bin/bash

function usage() {
    echo "Usage: "$0" <ip> <start> <end>"
    if [ -n "$1" ] ; then
	echo "Error: "$1"!"
    fi
    exit
}

if [ ! $# -eq 3 ] ; then
    usage
fi

ip=$1
start=$2
end=$3

for port in $(seq $start $end); do
    nc -n -v -z -w 1 $ip $port 2>&1 | grep ") open" &
done

exit
