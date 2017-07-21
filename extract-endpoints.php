<?php

function usage( $err=null ) {
	echo 'Usage: '.$_SERVER['argv'][0]." <javascript file> <https> <host>\n";
	if( $err ) {
		echo 'Error: '.$err."\n";
	}
	exit();
}

if( $_SERVER['argc'] < 2 || $_SERVER['argc'] > 4 ) {
	usage();
}

$_source = trim( $_SERVER['argv'][1] );
if( !is_file($_source) ) {
	usage( 'Source file not found!' );
}

if( $_SERVER['argc'] >= 3 && $_SERVER['argv'][2]=='https' ) {
	$_scheme = 'https';
} else {
	$_scheme = 'http';
}

if( $_SERVER['argc'] >= 4 ) {
	$_host = trim( $_SERVER['argv'][3] );
} else {
	$_host = null;
}

echo "Loading file '".$_source."'\n";
$buffer = file_get_contents( $_source );
//var_dump( $buffer );

$t_regexp = [
	'#[\'"\(](http[s]?://.*?)[\'"\)]#',
	'#[\'"](/.*?)[\'"]#',
	'#[\'"\(]([^\'"\(]*\.php[^\'"\(]*?)[\'"\)]#',
	'#href\s*=\s*[\'"](.*?)[\'"]#',
	'#href\s*=\s*[\'](.*?)[\']#',
	'#href\s*=\s*["](.*?)["]#',
	'#src[\s]*=[\s]*[\'](.*?)[\']#',
	'#src[\s]*=[\s]*["](.*?)["]#',
	//'#src[\s]*=[\s]*[\'"](.*?)[>]#',
];

$n_regexp = count( $t_regexp );
echo $n_regexp." regexp loaded.\n";
echo "Looking for urls...\n";

function run( $buffer )
{
	global $t_regexp;
	//var_dump( $t_regexp );

	$t_all = [];
	
	foreach( $t_regexp as $r ) {
		$m = preg_match_all( $r, $buffer, $matches );
		if( $m ) {
			//var_dump( $matches );
			$t_all = array_merge( $t_all, $matches[1] );
		}
	}
	
	//var_dump( $t_all );

	$t_exclude_extension = [ ];
	$t_exclude_domain = [ ];
	$t_exclude_scheme = [ 'javascript', 'mailto' ];
	$t_exclude_string = [ ];
	$t_exclude_possible = [ '+', '==' ];

	echo "Cleaning results...\n";

	$t_possible = [];
	$t_all = array_unique( $t_all );

	foreach( $t_all as $k=>&$url )
	{
	//var_dump($url);
		$test = preg_replace( '#[^0-9a-zA-Z]#', '', $url );
		if( $test == '' ) {
			unset( $t_all[$k] );
			continue;
		}
	 	
		$parse = parse_url( $url );
		//var_dump($parse);
		
		foreach( $t_exclude_string as $s ) {
			if( strstr($url,$s) ) {
				unset( $t_all[$k] );
			}
		}
		
		foreach( $t_exclude_possible as $s ) {
			if( strstr($url,$s) ) {
				unset( $t_all[$k] );
				$t_possible[] = $url;
			}
		}

		if( isset($parse['scheme']) && in_array($parse['scheme'],$t_exclude_scheme) ) {
			unset( $t_all[$k] );
			continue;
		}
	}
	
	//var_dump($t_all);
	return [$t_all,$t_possible];
}


function clean( &$t_urls )
{
	global $_scheme, $_host;
	
	$scheme = $host = '';
	
	foreach( $t_urls as &$u )
	{
		//var_dump( $u );
		$parse = parse_url( $u );
		//var_dump( $parse );
		
		if( isset($parse['host']) ) {
			$host = $parse['host'];
		} else {
			if( $_host ) {
				$host = $_host;
			}
			$u = ltrim( $u, '/' );
			$u = $host . '/' . $u;
		}
		
		if( isset($parse['scheme']) ) {
			$scheme = $parse['scheme'];
		} elseif( $host ) {
			$scheme = $_scheme;
			$u = ltrim( $u, '/' );
			$u = $scheme . '://' . $u;
		}
	}
}


list($t_final,$t_possible) = run( $buffer );
clean( $t_final );
$n_final = count($t_final);
$n_possible = count($t_possible);

if( $n_final ) { 
	$t_final = array_unique( $t_final );
	$n_final = count($t_final);
	echo "\n-> ".implode( "\n-> ", $t_final )."\n";
}
echo "\n".$n_final." urls found!\n";

if( $n_possible ) {
	$t_possible = array_unique( $t_possible );
	$n_possible = count($t_possible);
	echo "\n-> ".implode( "\n-> ", $t_possible )."\n";
}
echo "\nand ".$n_possible." more possible urls...\n";


exit();

?>