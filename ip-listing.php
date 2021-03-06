<?php

function isIp( $str )
{
	return preg_match( '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\z/', trim($str) );
}


function usage( $err=null ) {
  echo 'Usage: '.$_SERVER['argv'][0]." <start ip> <end ip> [<step>]\n";
  if( $err ) {
    echo 'Error: '.$err."!\n";
  }
  exit();
}

if( $_SERVER['argc'] != 3 && $_SERVER['argc'] != 4 ) {
  usage();
}

if( !isIp($_SERVER['argv'][1]) ) {
	usage($_SERVER['argv'][1].' is not a valid ip address');
}

if( !isIp($_SERVER['argv'][2]) ) {
	usage($_SERVER['argv'][2].' is not a valid ip address');
}

$reverse = false;
$start = ip2long( $_SERVER['argv'][1] );
$end = ip2long( $_SERVER['argv'][2] );
$step = isset($_SERVER['argv'][3]) ? (int)$_SERVER['argv'][3] : 1;

$t_ip = range( $start, $end, $step );
array_walk( $t_ip, create_function('&$v', '$v=long2ip($v);') );
echo implode( "\n", $t_ip )."\n";

exit();

?>