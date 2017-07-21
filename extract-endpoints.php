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

echo "Loading file '".$source."'\n";
$buffer = file_get_contents( $source );
//var_dump( $buffer );

$t_all = [];
$t_regexp = [
	//'#[\'"\(](http[s]?://.*?)[\'"\)]#',
	//'#[\'"](/.*?)[\'"]#',
	//'#[\'"\(]([^\'"\(]*\.php[^\'"\(]*?)[\'"\)]#',
	//'#href\s*=\s*[\'"](.*?)[\'"]#',
	//'#href\s*=\s*[\'](.*?)[\']#',
	//'#href\s*=\s*["](.*?)["]#',
	'#src[\s]*=[\s]*["](.*?)["]#',
	//'#src[\s]*=[\s]*[\'](.*?)[\']#',
];

echo "Looking for urls...\n";

foreach( $t_regexp as $r ) {
	$m = preg_match_all( $r, $buffer, $matches );
	if( $m ) {
		$t_all = array_merge( $t_all, $matches[1] );
	}
}

//var_dump( $t_all );

$t_exclude_extension = [ ];
$t_exclude_scheme = [ 'javascript', 'mailto' ];
$t_exclude_string = [ '==' ];
$t_exclude_possible = [ '+' ];

echo "Cleaning results...\n";

$t_possible = [];
$t_final = array_unique( $t_all );

foreach( $t_final as $k=>$url ) {
//var_dump($url);
	$keep = true;
	$test = preg_replace( '#[^0-9a-zA-Z]#', '', $url );
	if( $test == '' ) {
		$keep = false;
	} else {
		$parse = parse_url( $url );
//var_dump($parse);
		if( isset($parse['scheme']) && in_array($parse['scheme'],$t_exclude_scheme) ) {
			$keep = false;
		} else {
			foreach( $t_exclude_string as $s ) {
				if( strstr($url,$s) ) {
					$keep = false;
				} else {
					foreach( $t_exclude_possible as $s ) {
						if( strstr($url,$s) ) {
							$keep = false;
							$t_possible[] = $url;
						}
					}
				}
			}
		}
	}
	if( !$keep ) {
		unset( $t_final[$k] );
	}
}

$n_url = count($t_final);
if( $n_url ) { 
	echo "\n-> ".implode( "\n-> ", $t_final )."\n";
}
echo "\n".$n_url." urls found!\n";

$n_possible = count($t_possible);
if( $n_possible ) {
	$t_possible = array_unique( $t_possible );
	echo "\n-> ".implode( "\n-> ", $t_possible )."\n";
}
echo "\nand ".$n_possible." more possible urls...\n";


exit();

?>