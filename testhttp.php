#!/usr/bin/php
<?php

// usage function
function usage( $error='' )
{
	echo "Usage: php testhttp.php <host|ip> <port list>\n";

	if( $error ) {
		echo "Error: ".$error."!\n";
	}

	exit();
}

// test if a string is an IP address
function isIp( $str )
{
	return preg_match( '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\z/', trim($str) );
}


// global vars
define( 'RESULT_SEP', ':' );
define( 'PORT_SEP', ',' );
define( 'HTTP_KO', 0 );
define( 'HTTP_OK', 1 );
define( 'HTTP_REDIR', 2 );
$t_result = [ 0=>'KO', 1=>'OK',  2=>'REDIR' ];


if( $_SERVER['argc']<2 || $_SERVER['argc']>3 ) {
	usage();
}

$host = $_SERVER['argv'][1];

if( $_SERVER['argc'] == 3 ) {
	$port = $_SERVER['argv'][2];
} else {
	// default port
	$port = '80,443';
}
$t_port = explode( PORT_SEP, $port );


// main loop
foreach( $t_port as $port )
{
	$t_details = [];
	$port_is_http = HTTP_OK;
	$scheme = 'http';
	if( $port == 443 ) {
		$scheme .= 's';
	}
	$u = $scheme.'://'.$host.':'.$port;

	//echo 'Testing '.$u."\n";

	do
	{
		$loop = false;

		$c = curl_init();
		curl_setopt( $c, CURLOPT_URL, $u );
		curl_setopt( $c, CURLOPT_NOBODY, true );
		curl_setopt( $c, CURLOPT_CONNECTTIMEOUT, 2 );
		curl_setopt( $c, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $c, CURLOPT_RETURNTRANSFER, true );
		curl_exec( $c );
		$t_info = curl_getinfo( $c );
		//var_dump( $t_info );
		curl_close( $c );

		if( $t_info['http_code'] == 0 )
		{
			// http service NOT found
			$port_is_http = HTTP_KO;
		}
		else
		{
			// http service found
			$r = $t_info['redirect_url'];
			//var_dump( $r );

			if( trim($r) != '' )
			{
				$tmp = parse_url( $r );
				//var_dump($tmp);
				$u = $r;
				$port_is_http = HTTP_REDIR;
				$t_details[] = $tmp['host'];
				$loop = true;
				/*
				// but it's a redirection!
				if( isIp($r) ) {
					$h = $r;
				} else {
					// extract scheme, host and port of the redirection
					$tmp = parse_url( $r );
					//var_dump( $tmp );
					$s = $tmp['scheme'];
					$h = $tmp['host'];
					if( !isset($tmp['port']) ) {
						$p = ($s=='https') ? 443 : 80;
					}
				}
				if( $s == $scheme && $h == $host && $p == $port ) {
					// the redirection point to the exact same scheme, host and port
					// so we keep looping
					$u = $r;
					//$port_is_http = HTTP_REDIR;
					//$t_details[] = $tmp['host'];
					$loop = true;
				} else {
					// the redirection DO NOT point to the exact same scheme, host and port
					// so we leave
					$u = $r;
					$port_is_http = HTTP_REDIR;
					$t_details[] = $tmp['host'];
					//$loop = true;
				}
				*/
			}
		}
	}
	while( $loop );

	$cnt = count($t_details);
/*	if( $port_is_http == HTTP_REDIR && $t_details[$cnt-1]==$host ) {
		$port_is_http = HTTP_OK;
		$t_details = [];
		$cnt = 0;
	}
*/
	echo $port.RESULT_SEP.$t_result[$port_is_http];
	if( $cnt ) {
		for( $i=0,$p=null ; $i<$cnt ; $p=$t_details[$i],$i++ ) {
			if( $t_details[$i] != $p ) {
				echo RESULT_SEP.$t_details[$i];
			}
		}
	}
	echo "\n";
}


// the end
exit();

?>