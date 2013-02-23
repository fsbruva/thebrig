#!/usr/local/bin/php-cgi -f
<?php
/*
This is only fo reference. I will check it and remove this message,when OK.  It worked from webgui now, but I not checked from command line
jail_start.php
*/
require_once ("/etc/inc/config.inc");
require_once ("{$config['thebrig']['rootfolder']}/conf/ext/thebrig/functions.inc");
if ( count ( $config['thebrig']['content'] ) > 0 ) {
	if ( !is_file ( "/etc/rc.conf.local" ) ) {
		// This means we are on embedded
		write_rcconflocal ();
		exec ( "/etc/rc.d/jail restart" ) ;
	}
}
?>
