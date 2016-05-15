<?php

function usage( $err=null ) {
  echo 'Usage: '.$_SERVER['argv'][0]." <source file>\n";
  if( $err ) {
    echo 'Error: '.$err."\n";
  }
  exit();
}

if( $_SERVER['argc'] != 2 ) {
  usage();
}

$src = $_SERVER['argv'][1];
if( !is_file($src) ) {
  usage( 'cannot find source file !' );
}
$content = file_get_contents( $_SERVER['argv'][1] );

$t_attr = [ 'href', 'src' ];
$t_matches = [];

foreach( $t_attr as $a ) {
    $r = '#<.*'.$a.'="+([^">]*)#i';
    preg_match_all( $r, $content, $tmp );
    if( $tmp && is_array($tmp) && isset($tmp[1]) && is_array($tmp[1]) && count($tmp[1]) ) {
        $t_matches = array_merge( $tmp[1], $t_matches );
    }
}

//var_dump( $t_matches );

foreach( $t_matches as $m ) {
    echo $m."\n";
}

exit();

?>