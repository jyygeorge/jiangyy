#!/bin/bash

function test() {
    bucket=$1
    http=`curl -s http://$bucket.s3.amazonaws.com/`
    exist=`echo $http | grep -i 'does not exist' | wc -w`
    #ls=`aws s3 ls s3://$bucket 2>&1`
    #exist=`echo $ls |egrep -i "does not exist|not supported" | wc -w`
    #echo $exist

    if [ $exist -eq 0 ] ; then
	_print $bucket GREEN
	echo

	putacl=`aws s3api put-bucket-acl --grant-full-control 'uri="http://acs.amazonaws.com/groups/global/AllUsers"' --bucket $bucket 2>&1 |egrep -i "denied|disabled|not supported"`
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
	    
	    ls=`aws s3 ls s3://$bucket 2>&1`
	    read=`echo $ls |egrep -i "denied|disabled|not supported"`
	    if [ -n "$read" ] ; then
		_print "list failed" DARK_GRAY
	    else
		_print "list success" ORANGE
	    fi
	    _print ", "
	    
	    read=`echo $http |egrep -i "denied"`
	    if [ -n "$read" ] ; then
		_print "http list failed" DARK_GRAY
	    else
		_print "http list success" ORANGE
	    fi
	    _print ", "
	    
   	    write=`aws s3 cp $f s3://$bucket 2>&1 |egrep -i "denied|disabled|not supported"`
	    if [ -n "$write" ] ; then
		_print "write failed" DARK_GRAY
	    else
		_print "write success" RED
	    fi
	fi

	echo
    fi
}
