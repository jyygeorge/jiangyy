<?php

$gg_url = 'https://www.google.fr/search?num=20&q=';

$t_dorks = array(
	'site:__SITE__ inurl:redirect inurl:&',
	'site:__SITE__ intitle:"index of"',
	'site:__SITE__ inurl:wp-content',
	'site:__SITE__ "Fatal error:"',
	'site:__SITE__ "not found on this server"',
    'site:__SITE__ inurl:url=',
);

	
function usage( $err=null ) {
	echo 'Usage: '.$_SERVER['argv'][0]." <example.com>\n";
	if( $err ) {
		echo 'Error: '.$err."\n";
	}
	exit();
}

if( $_SERVER['argc'] < 2 ) {
	usage();
}


for( $i=1 ; $i<$_SERVER['argc'] ; $i++ )
{
	$site = $_SERVER['argv'][$i];
	
	foreach( $t_dorks as $d )
	{
		$gg = urlencode( $d );
		$gg = str_replace( '__SITE__', $site, $gg );
		echo $gg_url.$gg."\n";
	}
	
	echo "\n";
}


exit();

?>
