#!/usr/bin/php
<?php

function usage( $err=null ) {
  echo 'Usage: '.$_SERVER['argv'][0]." <domain>\n";
  if( $err ) {
    echo 'Error: '.$err."\n";
  }
  exit();
}

if( $_SERVER['argc'] != 2 ) {
  usage();
}

$domain = $_SERVER['argv'][1];
$src = 'https://threatcrowd.org/searchApi/v2/domain/report/?domain='.$domain;
$json = file_get_contents( $src );
//var_dump( $json );

$t_json = json_decode( $json, true );
//var_dump( $t_json);

if( $t_json['response_code'] != 1 ) {
    echo 'Error: reponse_code='.$t_json['response_code']."\n";
    exit(-1);
}

foreach( $t_json['subdomains'] as $s ) {
    echo $s."\n";
}

exit(0);

?>