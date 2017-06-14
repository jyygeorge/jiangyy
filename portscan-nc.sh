#!/bin/bash


function usage() {
    echo "Usage: "$0" <ip> <port start> <port end> [<udp>]"
    if [ -n "$1" ] ; then
	echo "Error: "$1"!"
    fi
    exit
}

if [ $# -lt 3 ] || [ $# -gt 4 ] ; then
    usage
fi

ip=$1
start=$2
end=$3
options="-n -v -z -w 1"

if [ $# -eq 4 ] ; then
    options=$options" -u"
fi

if [ $end -lt $start ] ; then
    tmp=$start
    start=$end
    end=$tmp
fi

for port in $(seq $start $end); do
    nc $options $ip $port 2>&1 | egrep "\) open|\] succeeded" &
done

exit
