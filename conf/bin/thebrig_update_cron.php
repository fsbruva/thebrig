#!/usr/local/bin/php-cgi -f
<?php
/*
*/
require_once ("/etc/inc/config.inc");
$brig_root = $config['thebrig']['rootfolder'];
require_once ("/usr/local/www/ext/thebrig/functions.inc");

$brig_update_db = $brig_root . "conf/db/freebsd-update/";
array_sort_key($config['thebrig']['content'], "jailno");
$a_jail = &$config['thebrig']['content'];
sleep ( rand( 0 , 3600 ) );
$brig_update_ready = thebrig_update_prep();
$basedir_list = array();
$workdir_list = array();
$conffile_list = array();
$base_selected = false;
if ( $brig_update_ready == 0 ) {
	
	// We will be building arrays of base directories, working directories and config files
	// to be used for the fetch operations.
	foreach ( $a_jail as $my_jail ) {
		// now we need to prep for the actual updating
		// This if gets entered the jail is checked and is fullsized
		if  ( $my_jail['type'] == 'full' ) 
			$conffile_list[] = $brig_root . "conf/freebsd-update.conf";
		else {
			// The current jail is slim, so we have to use a special conf that excludes the shared basejail
			$conffile_list[] = $brig_root . "conf/freebsd-update_thin.conf";
			$base_selected = true;
			
		} // end of slim + basejail
		$basedir_list[]=$my_jail['jailpath'];
		$workdir_list[]=$my_jail['jailpath'] . "var/db/freebsd-update/";
	} // end of all jails foreach

	// We need to take care of the basejail!
	if ( $base_selected ){
		$basejail = $config['thebrig']['basejail'];
		$basedir_list[]=$basejail['folder'];
		$workdir_list[]=$brig_update_db;
		$conffile_list[] = $brig_root . "conf/freebsd-update.conf";
	} // end of basejail selected

	// We need to take care of the template jail!
	if ( is_dir ( $config['thebrig']['template'] . "usr/" )){
		$template_dir = $config['thebrig']['template'];
		$basedir_list[]=$template_dir;
		$workdir_list[]=$template_dir . "var/db/freebsd-update/";
		$conffile_list[] = $brig_root . "conf/freebsd-update.conf";
	} // end of template selected

	$response = thebrig_update($basedir_list, $workdir_list , $conffile_list, "Fetch");
} // end of brig_update_ready

?>