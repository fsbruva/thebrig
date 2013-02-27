#!/usr/local/bin/php-cgi -f
<?php
/*
*/
require_once ("/etc/inc/config.inc");
require_once ("{$config['thebrig']['rootfolder']}conf/ext/thebrig/functions.inc");

exec( "mkdir -p /usr/local/www/ext/thebrig/" );
exec( "cp {$config['thebrig']['rootfolder']}ext/thebrig/* /usr/local/www/ext/thebrig");
$php_list = glob( "/usr/local/www/ext/thebrig/*.php" ); 
foreach ( $php_list as $php_file ) {
	$php_file = str_replace( "/usr/local/www/ext/thebrig/" , "", $php_file);
	if ( is_link ( "/usr/local/www/ext/thebrig/" . $php_file ) ) {
		unlink (  "/usr/local/www/ext/thebrig/" . $php_file );
	}
	exec ( "ln -s /usr/local/www/{$php_file} /usr/local/www/ext/thebrig/{$php_file}");
}
if ( count ( $config['thebrig']['content'] ) > 0 ) {
	if ( !is_file ( "/etc/rc.conf.local" ) ) {
		// This means we are on embedded
		write_rcconflocal ();
		exec ( "/etc/rc.d/jail restart" ) ;
	}
}
?>