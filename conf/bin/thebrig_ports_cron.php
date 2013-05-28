#!/usr/local/bin/php-cgi -f
<?php
/*
*/
require_once ("/etc/inc/config.inc");
$brig_root = $config['thebrig']['rootfolder'];
require_once ("/usr/local/www/ext/thebrig/functions.inc");

$brig_port_db = $brig_root . "conf/db/ports/";

$brig_update_ready = thebrig_update_prep();
if ( $brig_update_ready == 0 ) {
	sleep ( rand( 0 , 3600 ) );
	$response = thebrig_portsnap($brig_root . "conf/ports", $brig_root . "conf/db/ports", $brig_root . "conf/portsnap.conf", "Fetch");
}

?>
