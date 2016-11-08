#!/bin/bash


function usage() {
    echo "Usage: "$0" <url> [<port>] [<output directory>]"
    if [ -n "$1" ] ; then
		echo "Error: "$1"!"
    fi
    exit
}

if [ $# -lt 1 ] ; then
    usage
fi

url=$1

if [ $# -gt 1 ] ; then
    t_port=$2
else
    t_port=80
fi
port=$(echo $t_port | tr ',' ' ')

if [ $# -gt 2 ] ; then
    output=$3
else
    output="/tmp"
fi

ua="Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.59 Safari/537.36"
timeout=3000

for p in $port ; do
	u=$url":"$p
	o=$output"/"$p.png
	xvfb-run --server-args="-screen 0, 1920x1080x24" cutycapt --url=$u --out=$o --user-agent="$ua" --max-wait=$timeout
	sleep 1
done

exit;

