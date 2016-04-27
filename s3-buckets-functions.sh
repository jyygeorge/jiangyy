#!/bin/bash


NC='\033[0m'
BLACK='0;30'
RED='0;31'
GREEN='0;32'
ORANGE='0;33'
BLUE='0;34'
PURPLE='0;35'
CYAN='0;36'
LIGHT_GRAY='0;37'
DARK_GRAY='1;30'
LIGHT_RED='1;31'
LIGHT_GREEN='1;32'
YELLOW='1;33'
LIGHT_BLUE='1;34'
LIGHT_PURPLE='1;35'
LIGHT_CYAN='1;36'
WHITE='1;37'


function _print() {
    if [ -n "$2" ] ; then
		c=$2
    else
		c='WHITE'
    fi

	color="\033[${!c}m"
    printf ${color}"$1"
    printf ${NC}
}


function test() {
    bucket=$1
    #echo $bucket
    ls=`aws s3 ls s3://$bucket 2>&1`
    exist=`echo $ls |egrep -i "does not exist|not supported" | wc -w`
    #echo $exist
    
    if [ $exist -eq 0 ] ; then
	_print $bucket GREEN
	echo

	putacl=`aws s3api put-bucket-acl --grant-full-control 'uri="http://acs.amazonaws.com/groups/global/AllUsers"' --bucket $bucket 2>&1 |egrep -i "denied|disabled|not supported"`
	#echo $putacl
	if ! [ -n "$putacl" ] ; then
	    _print "put ACL success" RED
	_print ", you got everything!"
	else
	    _print "put ACL failed" DARK_GRAY
	    _print ", "
	    
   	    getacl=`aws s3api get-bucket-acl --bucket $bucket 2>&1 |egrep -i "denied|disabled|not supported"`
	    #echo $getacl
	    if [ -n "$getacl" ] ; then
		_print "get ACL failed" DARK_GRAY
	    else
		_print "get ACL success" ORANGE
	    fi
	    _print ", "
	    
	    read=`echo $ls |egrep -i "denied|disabled|not supported"`
	    #echo $read
	    if [ -n "$read" ] ; then
		_print "list failed" DARK_GRAY
	    else
		_print "list success" ORANGE
	    fi
	    _print ", "
	    
   	    write=`aws s3 cp $f s3://$bucket 2>&1 |egrep -i "denied|disabled|not supported"`
	    #echo $write
	    if [ -n "$write" ] ; then
		_print "write failed" DARK_GRAY
	    else
		_print "write success" RED
	    fi
	fi

	echo
    fi
}
