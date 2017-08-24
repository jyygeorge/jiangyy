<?php

function usage( $err=null ) {
	echo 'Usage: php '.$_SERVER['argv'][0]." -f <wayback output file> [OPTIONS]\n\n";
	echo "Options:\n";
	echo "\t-e\textension you care, separated  by comma\n";
	echo "\t-f\tset json source file (required)\n";
	echo "\t-i\textension you ignore, separated  by comma\n";
	echo "\t-r\tprint only a resume\n";
	echo "\t-s\tforce https if scheme not found\n";
	echo "\t-t\ttest the urls found\n";
	echo "\n";
	if( $err ) {
		echo 'Error: '.$err."\n";
	}
	exit();
}


require_once( 'Utils.php' );

define( 'DEFAULT_COLOR', 'red' );

$t_colors = [
	0   => 'dark_grey',
	200 => 'light_green',
	301 => 'light_cyan',
	302 => 'light_cyan',
	307 => 'light_cyan',
];
	
$options = '';
$options .= 'e:'; // extension to display
$options .= 'f:'; // json source file
$options .= 'i:'; // extension to ignore
$options .= 'r'; // print resume
$options .= 's'; // test urls
$options .= 't'; // test urls
$t_options = getopt( $options );

if( !count($t_options) ) {
	usage();
}

if( isset($t_options['e']) ) {
	$_extension_wish = explode( ',', $t_options['e'] );
} else {
	$_extension_wish = null;
}

if( isset($t_options['f']) ) {
	$f = $t_options['f'];
	if( !is_file($f) ) {
		usage( 'Source file not found!' );
	} else {
		$_source = $f;
	}
} else {
	usage( 'Source file not found!' );
}

if( isset($t_options['i']) ) {
	$_extension_ignore = explode( ',', $t_options['i'] );
} else {
	$_extension_ignore = [];
}

if( isset($t_options['r']) ) {
	$_resume = true;
} else {
	$_resume = false;
}

if( isset($t_options['s']) ) {
	$_https = true;
} else {
	$_https = false;
}

if( isset($t_options['t']) ) {
	$_test = true;
} else {
	$_test = false;
}


function cleanContent( $str )
{
	$str = str_replace( '%22http', '"],["http', $str );
	$str = str_replace( '%22//', '"],["//', $str );
	$str = str_replace( '///', '/"],["//', $str );
	$str = str_replace( '/http', '/"],["http', $str );
	return $str;
}

function cleanUrl( $str )
{
	$str = preg_replace( '#/%22#', '/', $str );
	return $str;
}

function cleanPath( $str )
{
	$str = preg_replace( '#(\.[a-zA-Z0-9]{2,4})/#', '\\1', $str );
	return $str;
}

function testUrl( $url )
{
	$c = curl_init();
	curl_setopt( $c, CURLOPT_URL, $url );
	//curl_setopt( $c, CURLOPT_HEADER, true );
	curl_setopt( $c, CURLOPT_SSL_VERIFYPEER, false );
	//curl_setopt( $c, CURLOPT_NOBODY, true );
	curl_setopt( $c, CURLOPT_CONNECTTIMEOUT, 3 );
	curl_setopt( $c, CURLOPT_FOLLOWLOCATION, false );
	curl_setopt( $c, CURLOPT_RETURNTRANSFER, true );
	$r = curl_exec( $c );
	
	$t_info = curl_getinfo( $c );
	
	return $t_info;
}


$content = file_get_contents( $_source );
$content = cleanContent( $content );
$array = json_decode( $content );

$current = 0;
$limit = -1;

if( is_null($_extension_wish) ) {
	$t_result = [ 'unknown'=>[], 'domain'=>[] ];
} else {
	$t_result = [];
}

$t_result = [];
$t_domain = [];
$t_unknown = [];

foreach( $array as $v )
{
	if( $limit > 0 && $current>$limit ) {
		break;
	}
	
	$current++;
	$url = cleanUrl( $v[0] );
	$t_parse = parse_url( $url );
	if( !isset($t_parse['scheme']) ) {
		$t_parse['scheme'] = 'http'.($_https?'s':'');
		$url = $t_parse['scheme'].'://'.$url;
	}
	//var_dump( $t_parse );

	if( !isset($t_parse['path']) || $t_parse['path']=='/' )
	{
		$t_domain[] = $url;
	}
	else
	{
		$t_parse['path'] = cleanPath( $t_parse['path'] );
		$ext = substr( $t_parse['path'], strrpos($t_parse['path'],'.')+1 );
		//var_dump( $ext );
		
		if( $ext == '' || strlen($ext)<2 || strlen($ext)>4 || preg_match('#[^a-zA-Z0-9]#',$ext) ) {
			$t_unknown[] = $url;
		} else {
			if( (is_null($_extension_wish) || in_array($ext,$_extension_wish)) && !in_array($ext,$_extension_ignore) ) {
				$t_result[ $ext ][] = $url;
			}
		}
	}
}

$total = 0;
ksort( $t_result, SORT_STRING );

if( is_null($_extension_wish) ) {
	$t_result['domain'] = $t_domain;
	$t_result['unknown'] = $t_unknown;
}

foreach( $t_result as $ext=>$t_url )
{
	Utils::_print( 'Extension: '.$ext, 'yellow' );
	$cnt = count( $t_url );
	$total += $cnt;
	
	if( $_resume )
	{
		echo " ".$cnt." urls found.\n";
	}
	else
	{
		echo "\n";
		
		foreach( $t_url as $u )
		{
			echo $u;
			
			if( $_test && stripos('http',$u)==0 ) {
				$t_info = testUrl( $u );
				//var_dump($t_info);
				if( isset($t_colors[$t_info['http_code']]) ) {
					$color = $t_colors[ $t_info['http_code'] ];
				} else {
					$color = DEFAULT_COLOR;
				}
				$txt = ' (C='.$t_info['http_code'].', L='.$t_info['size_download'].', T='.$t_info['content_type'].')';
				Utils::_print( $txt, $color );
			}
			echo "\n";
		}
		
		echo $cnt." urls found!\n\n";
	}
}

echo "\nTotal: ".$total." urls found!\n";

exit();

