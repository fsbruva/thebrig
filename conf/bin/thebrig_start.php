#!/usr/local/bin/php-cgi -f
<?php
/*
 * File name: 	thebrig_start.php
 * Author:      Matt Kempe, Alexey Kruglov
 * Modified:	Dec 2014
 * 
 * Purpose: 	This script is used to prepare the extension for use by
 * 				Nas4Free's lighttpd webgui server.
 * 
 * Variables used:
 * 
 * thebrig_ext	a string containing the real storage location of
 * 				the ext/thebrig folder. It simplifies code.
 * brig_ver		String (then floating point) version value stored in 
 * 				the installed copy of TheBrig's lang.inc
 * php_list		An array of all the php files in the ext/thebrig
 * php_file		Variable used to control a "for" loop
 * a_jail		An array of all the jail information from the config.xml
*/
require_once ("config.inc");
require_once ("{$config['thebrig']['rootfolder']}conf/ext/thebrig/functions.inc");
require_once ("{$config['thebrig']['rootfolder']}conf/ext/thebrig/lang.inc");
if ( ! copy ( $config['thebrig']['rootfolder']."conf/bin/jail.sh", "/etc/rc.d/thebrig"))  
	{ exec ("logger Failed copy rc script");} 
chmod("/etc/rc.d/thebrig", 0755);

/* Clean up operations
 * 
 * These steps serve two purposes:
 * 1. To clean up old versions of TheBrig's file schema
 * 2. To reset all symlinks, in case a new version was installed, and the
 * 	  file list has changed
 * 
 */
if (g['platform'] == full ) {
// Get rid of the erroneously created file (by early versions).
unlink_if_exists ( "/usr/local/www/\*.php" );

// Get a list of all the symlinks or files from TheBrig that are currently 
// in the webroot, and destroy them

foreach ("/usr/local/www/extensions_thebrig_*.php" as $link) {
array_map ( 'unlink' , $link );
}



// Get rid of old schema - which was a separate copy of entire ext folder
if ( is_dir( "/usr/local/www/ext/thebrig") ) {
	exec ( "rm -rf /usr/local/www/ext/thebrig");
	}
/*
 * End of clean-up operations
 */
} 
// This checks to make sure the XML config concurs with the 
// installed version of lang.inc
$brig_ver = preg_split ( "/v/", _THEBRIG_VERSION_NBR);
// Convert the string to a float so that it can be used in comparisons
$brig_ver = 0 + substr($brig_ver[1],0,3);
if ( ($config['thebrig']['version'] != $brig_ver )){
	// We need to update the XML config to reflect reality
	$config['thebrig']['version'] = $brig_ver;
	write_config();
} 

// This might be the first extension, so we need to create the folder for it
exec( "mkdir -p /usr/local/www/ext" );
// Make life a little easier
$thebrig_ext = "{$config['thebrig']['rootfolder']}conf/ext/thebrig";
// Link the entire folder into the extension location
exec( "ln -s {$thebrig_ext} /usr/local/www/ext/thebrig");
// Create a list of all the php files that need to be linked into the webroot
$php_list = glob( "{$thebrig_ext}/*.php" ); 
// We need to extract just the file name so the symbolic links make sense
foreach ( $php_list as $php_file ) {
	// Cut off the prefix to obtain the filename
	$php_file = str_replace( "{$thebrig_ext}/" , "", $php_file);
	// Link the real storage location to the webroot
	exec ( "ln -s {$thebrig_ext}/{$php_file} /usr/local/www/{$php_file}");
}
if ( count ( $config['thebrig']['content'] ) > 0 ) {
	write_jailconf ();
	write_defs_rules ();
}
if (isset ( $config['thebrig']['thebrig_enable']) ) {
		rc_update_service('thebrig');
		rc_start_service('thebrig');
}
?>
