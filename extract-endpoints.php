<?php

function usage( $err=null ) {
	echo 'Usage: php '.$_SERVER['argv'][0]." -f/-d <javascript file/directory> [OPTIONS]\n\n";
	echo "Options:\n";
	echo "\t-b\tbeautify the js before parsing (doesn't work anymore, need to find an alternative solution)\n";
	echo "\t-d\tset javascript source directory (required)\n";
	echo "\t-f\tset javascript source file (required)\n";
	echo "\t-h\tforce host if none\n";
	echo "\t-i\textensions to ignore separated by a comma (example: gif,jpg,png)\n";
	echo "\t-k\tsearch for keywords instead of urls\n";
	echo "\t-s\tforce https if no scheme\n";
	echo "\t-t\ttest the urls found\n";
	echo "\n";
	if( $err ) {
		echo 'Error: '.$err."\n";
	}
	exit();
}


require_once( 'Utils.php' );

define( 'MODE_ENDPOINT', 1 );
define( 'MODE_KEYWORD', 2 );
define( 'DEFAULT_MODE', MODE_ENDPOINT );


$options = '';
$options .= 'b'; // beautify
$options .= 'd:'; // js source directory
$options .= 'f:'; // js source file
$options .= 'h:'; // set host if none
$options .= 'i:'; // ignore extensions
$options .= 'k'; // looking for keywords instead of enpoints
$options .= 's'; // force https
$options .= 't'; // test url
$t_options = getopt( $options );
//ar_dump($t_options);
if( !count($t_options) ) {
	usage();
}


if( isset($t_options['t']) ) {
	$_test = true;
} else {
	$_test = false;
}

if( isset($t_options['s']) ) {
	$_scheme = 'https';
} else {
	$_scheme = 'http';
}

if( isset($t_options['b']) ) {
	$_beautify = true;
} else {
	$_beautify = false;
}

if( isset($t_options['h']) ) {
	$_host = $t_options['h'];
} else {
	$_host = null;
}

if( isset($t_options['f']) ) {
	$f = $t_options['f'];
	if( !is_file($f) ) {
		usage( 'Source file not found!' );
	} else {
		$_t_source = [$f];
	}
} elseif( isset($t_options['d']) ) {
	$d = $t_options['d'];
	if( !is_dir($d) ) {
		usage( 'Source file not found!' );
	} else {
		$d = rtrim( $d, '/' );
		$_t_source = glob($d.'/*.js');
	}
} else {
	usage();
}

if( isset($t_options['i']) ) {
	$_ignore = explode( ',', $t_options['i'] );
} else {
	$_ignore = null;
}

if( isset($t_options['k']) ) {
	$_mode = MODE_KEYWORD;
} else {
	$_mode = MODE_ENDPOINT;
}

$_url_chars = '[a-zA-Z0-9\-\.\?\#&=_:/]';
$_regexp = [
	'|["]('.$_url_chars.'+/'.$_url_chars.'+)?["]|',
	'#[\'"\(].*(http[s]?://.*?)[\'"\)]#',
	'#[\'"\(](http[s]?://.*?).*[\'"\)]#',
	'#[\'"\(]([^\'"\(]*\.php[^\'"\(]*?)[\'"\)]#',
	'#[\'"\(]([^\'"\(]*\.asp[^\'"\(]*?)[\'"\)]#',
	'#[\'"\(]([^\'"\(]*\.aspx[^\'"\(]*?)[\'"\)]#',
	//'#[\'"\(]([^\'"\(]*\.json[^\'"\(]*?)[\'"\)]#',
	//'#[\'"\(]([^\'"\(]*\.xml[^\'"\(]*?)[\'"\)]#',
	//'#[\'"\(]([^\'"\(]*\.ini[^\'"\(]*?)[\'"\)]#',
	//'#[\'"\(]([^\'"\(]*\.conf[^\'"\(]*?)[\'"\)]#',
	//'#href\s*=\s*[\'"](.*?)[\'"]#',
	'#href\s*=\s*[\'](.*?)[\']#',
	'#href\s*=\s*["](.*?)["]#',
	'#src\s*=\s*[\'](.*?)[\']#',
	'#src\s*=\s*["](.*?)["]#',
	//'#src[\s]*=[\s]*[\'"](.*?)[>]#',
	'#url\s*[:=].*[\'](.*?)[\']#',
	'#url\s*[:=].*?["](.*?)["]#',
	'#urlRoot\s*:.*[\'](.*?)[\']#',
	'#urlRoot\s*:.*?["](.*?)["]#',
	'#endpoint[s]?\s*:.*[\'](.*?)[\']#',
	'#endpoint[s]?\s*:.*?["](.*?)["]#',
	'#[\'"]script[\'"]\s*:\s*[\'"](.*?)[\'"]#',
	//'#href|src\s*=\s*["](.*?)["]#',
	//'#href|src\s*=\s*[\'](.*?)[\']#',
	//'#endpoint[s]?|url|urlRoot|href\s*:.*["](.*?)["]#',
	//'#endpoint[s]?|url|urlRoot|src\s*:.*[\'](.*?)[\']#',
];
$_keywords_sensitive = [
	'[\'\"][a-f0-9]{32}[\'\"]', // md5 
	'[\'\"][A-Z0-9]{20}[\'\"]', // aws secret
	'[\'\"][a-zA-Z0-9/]{40}[\'\"]', // aws api key
	'[\'\"][a-f0-9]{40}[\'\"]', // sometimes...
];
$_keywords_insensitive = [
	//'auth',
	//'private',
	'mysql',
	//'dump',
	//'login',
	//'password',
	//'credential',
	//'oauth',
	//'token',
	'apikey',
	'api_key',
	'app_key',
	'secret_key',
	'fb_secret',
	//'secret',
	'gsecr',
	//'username',
	'amazonaws\.com',
	'id_rsa',
	'id_dsa',
	//'\.json',
	//'\.xml',
	//'\.yaml',
	//'\.saml',
	//'config',
	'\.pem',
	'\.ppk',
	'\.sql',
	//'\.conf',
	//'\.ini',
	//'\.php',
	//'\.asp',
];
$_keywords_sensitive_regexp = '('.implode( '|', $_keywords_sensitive ).')';
$_keywords_insensitive_regexp = '('.implode( '|', $_keywords_insensitive ).')';

$n_regexp = count( $_regexp );
echo $n_regexp." regexp loaded.\n\n";


function run( $buffer )
{
	global $_regexp, $_ignore, $_url_chars;
	//var_dump( $_regexp );

	$t_all = [];
	
	foreach( $_regexp as $r ) {
		$m = preg_match_all( $r.'i', $buffer, $matches );
		if( $m ) {
			//var_dump( $matches );
			$t_all = array_merge( $t_all, $matches[1] );
		}
	}
	
	$t_exclude_extension = [ ];
	$t_exclude_domain = [ ];
	$t_exclude_scheme = [ 'javascript', 'mailto', 'data', 'about' ];
	$t_exclude_string = [ ];
	$t_exclude_possible = [ '+', '==', 'text/html', 'text/javascript', 'application/json' ];

	$t_possible = [];
	$t_all = array_unique( $t_all );
	//var_dump( $t_all );

	foreach( $t_all as $k=>&$url )
	{
		//var_dump($url);
		//$url = urldecode( $url );
		
		$test = preg_replace( '#[^0-9a-zA-Z]#', '', $url );
		if( $test == '' ) {
			unset( $t_all[$k] );
			continue;
		}
	 	
		$parse = parse_url( $url );
		//var_dump($parse);
		if( !$parse ) {
			unset( $t_all[$k] );
			$t_possible[] = $url;
			continue;
		}
		
		foreach( $t_exclude_string as $s ) {
			if( strstr($url,$s) ) {
				unset( $t_all[$k] );
				$t_possible[] = $url;
				continue;
			}
		}
		
		foreach( $t_exclude_possible as $s ) {
			if( strstr($url,$s) ) {
				unset( $t_all[$k] );
				$t_possible[] = $url;
				continue;
			}
		}
		
		if( isset($parse['scheme']) && in_array($parse['scheme'],$t_exclude_scheme) ) {
			unset( $t_all[$k] );
			$t_possible[] = $url;
			continue;
		}
		
		if( count($_ignore) ) {
			$p = strrpos( $parse['path'], '.' );
			if( $p !== false ) {
				$ext = substr( $parse['path'], $p+1 );
				if( in_array($ext,$_ignore) ) {
					unset( $t_all[$k] );
					continue;
				}
			}
		}

		if( $url[0] == '#' ) {
			unset( $t_all[$k] );
			$t_possible[] = $url;
			continue;
		}
		
		if( isset($parse['path']) )
		{
			if( strstr($parse['path'],' ') !== false ) {
				$tmp = explode( ' ', $parse['path'] );
				$parse['path'] = $tmp[0];
			}
			
			$kk = preg_replace('|'.$_url_chars.'|i','',$parse['path']);
			if( strlen($kk) != 0 ) {
				unset( $t_all[$k] );
				$t_possible[] = $url;
				continue;
			}
		}
	}
	
	//var_dump($t_all);
	return [$t_all,$t_possible];
}


function clean( &$t_urls )
{
	global $_scheme, $_host, $_ignore;
	
	$scheme = $host = '';
	
	foreach( $t_urls as &$u )
	{
		//var_dump( $u );
		$scheme = $host = '';
		$parse = parse_url( $u );
		//var_dump( $parse );
		
		if( isset($parse['host']) ) {
			$host = $parse['host'];
		} elseif( $_host ) {
			$host = $_host;
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
		
		if( strstr($u,' ') !== false ) {
			$tmp = explode( ' ', $u );
			$u = $tmp[0];
		}
	}
}


foreach( $_t_source as $s )
{
	Utils::_println( "Loading: ".$s, 'yellow' );
	
	if( $_beautify ) {
		ob_start();
		system( 'js-beautify '.$s );
		$buffer = ob_get_contents();
		ob_end_clean();
	} else {
		$buffer = file_get_contents( $s );
	}
	
	if( $_mode == MODE_KEYWORD )
	{
		$output = null;
		$cmd = 'egrep -n "'.$_keywords_sensitive_regexp.'" '.$s;
		//var_dump( $cmd );
		exec( $cmd, $output );
		$n_sensitive = printColoredGrep( $_keywords_sensitive_regexp, implode("\n",$output), 1 );
		
		$output = null;
		$cmd = 'egrep -i -n "'.$_keywords_insensitive_regexp.'" '.$s;
		//var_dump( $cmd );
		exec( $cmd, $output );
		$n_insensitive = printColoredGrep( $_keywords_insensitive_regexp, implode("\n",$output), 0 );
		
		$n_total = $n_sensitive + $n_insensitive;
		echo $n_total." keywords found!\n";
	}
	else
	{
		list($t_final,$t_possible) = run( $buffer );
		clean( $t_final );
		$n_final = count($t_final);
		$n_possible = count($t_possible);
		
		if( $n_final ) { 
			$t_final = array_unique( $t_final );
			$n_final = count( $t_final );
			foreach( $t_final as $u ) {
				echo $u;
				if( $_test && stripos('http',$u)==0 ) {
					$http_code = testUrl( $u );
					if( $http_code == 200 ) {
						$color = 'green';
					} else {
						$color = 'red';
					}
					$txt = ' ('.$http_code.')';
					Utils::_print( $txt, $color );
				}
				echo "\n";
			}
		}
		echo $n_final." urls found!\n";
		
		if( $n_possible ) {
			Utils::_println( str_repeat('-',100), 'light_grey' );
			$t_possible = array_unique( $t_possible );
			$n_possible = count($t_possible);
			Utils::_println( implode( "\n",$t_possible), 'light_grey' );
			Utils::_println( $n_possible." possible...", 'light_grey' );
		}
	}

	echo "\n";
}


function testUrl( $url )
{
	$c = curl_init();
	curl_setopt( $c, CURLOPT_URL, $url );
	//curl_setopt( $c, CURLOPT_HEADER, true );
	//curl_setopt( $c, CURLOPT_SSL_VERIFYPEER, false );
	//curl_setopt( $c, CURLOPT_NOBODY, true );
	curl_setopt( $c, CURLOPT_CONNECTTIMEOUT, 3 );
	curl_setopt( $c, CURLOPT_FOLLOWLOCATION, false );
	curl_setopt( $c, CURLOPT_RETURNTRANSFER, true );
	$r = curl_exec( $c );
	
	$t_info = curl_getinfo( $c );
	
	return $t_info['http_code'];
}


function printColoredGrep( $regexp, $str, $case_sensitive )
{
	//$p = 0;
	//$l = strlen( $str );
	//$m = preg_match_all( '#'.$regexp.'#i', $str, $matches, PREG_OFFSET_CAPTURE );
	//var_dump( $matches );
	
	if( $case_sensitive ) {
		$flag = '';
	} else {
		$flag = 'i';
	}
	
	$colored = preg_replace( '#'.$regexp.'#'.$flag, "\033[0;32m".'\\1'."\033[0m", $str, -1, $cnt );
	if( $cnt ) {
		echo $colored."\n";
	}
	//var_dump( $str );
	//Utils::_print( '('.($line>=0?$line:'-').') ', 'yellow' );
	/*
	if( $m ) {
		$n = count( $matches[0] );
		//var_dump($n);
		for( $i=0 ; $i<$n ; $i++ ) {
			$s1 = substr( $str, $p, ($matches[0][$i][1]-$p) );
			$s2 = substr( $str, $matches[0][$i][1], $l );
			$p = $matches[0][$i][1] + $l;
			//$p = $matches[$i][1] + $l;
			Utils::_print( $s1, 'white' );
			Utils::_print( $s2, 'light_green' );
			//break;
		}
	}
	
	$s3 = substr( $str, $p );
	Utils::_print( $s3, 'white' );*/
	return $cnt;
}

exit();

?>