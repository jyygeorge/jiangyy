#!/bin/bash


source myutils.sh


function usage() {
    echo "Usage: "$0" <ip start> <ip end> <wordlist> [<port>] [<ssl>]"
    if [ -n "$1" ] ; then
        echo "Error: "$1"!"
    fi
    exit
}

if [ $# -lt 3 ] || [ $# -gt 5 ] ; then
    usage
fi

wordlist=$3

if [ ! -f $wordlist ] ; then
        usage "File not found!"
fi

if [ $# -gt 4 ] ; then
    port=":$4"
else
    port=""
fi

if [ $# -eq 5 ] ; then
    ssl=1
else
    ssl=0
fi

start=$1
start_n=`ip2dec $start`
end=$2
end_n=`ip2dec $end`

if [ $end_n -lt $start_n ] ; then
    tmp=$start
    start=$end
    end=$tmp
    tmp=$start_n
    start_n=$end_n
    end_n=$tmp
fi

i=$start_n
coption="-s --connect-timeout 2"

while [ $i -le $end_n ] ; do
    ip=`dec2ip $i`
    if [ $ssl -eq 0 ] ; then
	proto="http"
    else
	proto="https"
	coption="$coption --insecure"
    fi

    url="$proto://$ip$port"
    output=`curl $coption $url`
    res=`echo $output | grep 'html'`
    echo "Connecting: $url"
    
    if [ ! -n "$res" ] ; then
	_print "Skipping..."
	echo
    else
        for w in $(cat $wordlist) ; do	    
	    url="http://$ip$port/$w"
            output=`curl $coption -I $url`
	    res=`echo $output | grep 'HTTP/1.1 200 OK'`
	    _print "Testing: $url"
	    
	    if [ -n "$res" ] ; then
		_print " FOUND!" GREEN
	    fi
	    
	    echo
	done
    fi
    
    i=$(( $i + 1 ))
    echo
done
  

exit

