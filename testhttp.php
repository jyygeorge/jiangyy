#!/usr/bin/php
<?php

function usage( $error='' )
{
  echo "Usage: php testhttp.php <host|ip> <port list>\n";
  
  if( $error ) {
    echo "Error: ".$error."!\n";
  }
  
  exit();
}

function extractDomain( $host )
{
  $tmp = explode( '.', $host );
  $cnt = count( $tmp );
  
  $domain = $tmp[$cnt-1];
  
  for( $i=$cnt-2 ; $i>=0 ; $i-- ) {
    $domain = $tmp[$i].'.'.$domain;
    if( strlen($tmp[$i]) > 3 ) {
      break;
    }
  }
  
  return $domain;
}

function isIp( $str )
{
  return preg_match( '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\z/', trim($str) );
}


define( 'PORT_SEP', ',' );

if( $_SERVER['argc']<2 || $_SERVER['argc']>3 ) {
  usage();
}

$host = $_SERVER['argv'][1];

if( $_SERVER['argc'] == 3 ) {
  $port = $_SERVER['argv'][2];
} else {
  $port = 80;
}
$t_port = explode( PORT_SEP, $port );

foreach( $t_port as $port )
{
  $scheme = 'http';
  if( $port == 443 ) {
    $scheme .= 's';
  }
  $u = $scheme.'://'.$host.':'.$port;
  $port_is_http = true;
  
  echo 'Calling '.$u."\n";
  
  do
  {
    $loop = false;
    
    $c = curl_init();
    curl_setopt( $c, CURLOPT_URL, $u );
    curl_setopt( $c, CURLOPT_NOBODY, true );
    curl_setopt( $c, CURLOPT_CONNECTTIMEOUT, 2 );
    curl_setopt( $c, CURLOPT_SSL_VERIFYPEER, false );
    curl_exec( $c );
    $t_info = curl_getinfo( $c );
    //var_dump( $t_info );
    curl_close( $c );
    
    $r = $t_info['redirect_url'];
    //var_dump( $r );
    if( trim($r) != '' ) {
      if( isIp($r) ) {
	$h = $r;
      } else {
	$tmp = parse_url( $r );
	//var_dump( $tmp );
	$s = $tmp['scheme'];
	$h = $tmp['host'];
	if( !isset($tmp['port']) ) {
	  $p = ($s=='https') ? 443 : 80;
	}
      }
      if( $s == $scheme && $h == $host && $p == $port ) {
	$u = $r;
	$loop = true;
      } else {
	$port_is_http = false;
      }
      //var_dump($s);
      //var_dump($h);
      //var_dump($p);
      //var_dump( $loop );
    } elseif( $t_info['http_code'] == 0 ) {
      $port_is_http = false;
    }
    
    //exit();
    //var_dump( $t_info['content_type'] );
    //var_dump( $t_info['http_code'] );
    //var_dump( $t_info['header_size'] );
    //var_dump( $t_info['request_size'] );
    //var_dump( $t_info );
    //exit();
  }
  while( $loop );

  var_dump( $port_is_http );
  //if( $port_is_http ) {
    
  //}
}

exit();

?>
