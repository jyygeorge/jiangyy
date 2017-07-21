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

$source = trim( $_SERVER['argv'][1] );
if( !is_file($source) ) {
	usage( 'Source file not found!' );
}

$buffer = file_get_contents( $source );

$t_all = [];
$t_regexp = [
	'#[\'"\(](http[s]?://.*?)[\'"\)]#',
	'#[\'"](/.*?)[\'"]#',
	'#[\'"\(]([^\'"\(]*\.php[^\'"\(]*?)[\'"\)]#',
];

foreach( $t_regexp as $r ) {
	$m = preg_match_all( $r, $buffer, $matches );
	if( $m ) {
		$t_all = array_merge( $t_all, $matches[1] );
	}
}

$t_final = array_unique( $t_all );
foreach( $t_final as $k=>$url ) {
	$url = preg_replace( '#[^0-9a-zA-Z]#', '', $url );
	if( $url == '' ) {
		unset( $t_final[$k] );
	}
}

echo "-> ".implode( "\n-> ", $t_final )."\n";
exit();

?>