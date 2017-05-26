#!/bin/bash


function usage() {
    echo "Usage: "$0" <ip> <port start> <port end>"
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

if [ $end -lt $start ] ; then
    tmp=$start
    start=$end
    end=$tmp
fi

for port in $(seq $start $end); do
    nc -n -v -z -w 1 $ip $port 2>&1 | egrep "\) open|\] succeeded" &
done

exit
