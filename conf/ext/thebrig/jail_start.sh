#!/usr/local/bin/php-cgi -f
<?php
/*
This is only fo reference. I will check it and remove this message,when OK.  It worked from webgui now, but I not checked from command line
jail_start.php.
This function works from the command line, but the #! doesn't function appropriately. It says there is 
no file specified. However, calling it: php-cgi -f jail_start.sh workd just fine.
*/
require_once ("/etc/inc/config.inc");
require_once ("{$config['thebrig']['rootfolder']}/conf/ext/thebrig/functions.inc");
write_rcconflocal ();
?>
