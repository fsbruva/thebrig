#!/usr/local/bin/php-cgi -f
<?php

/*
 * File name: 	change_ver.php
 * Author:      Matt Kempe, Alexey Kruglov
 * Modified:	Jan 2015
 * 
 * Purpose: 	This script is used to update the extension used by
 * 				Nas4Free's lighttpd webgui server.
 *  
 * Variables used:
 * 
 * thebrig_ext	a string containing the real storage location of
 * 				the ext/thebrig folder. It simplifies code.
 * gitlangfile		File descriptor for accessing the github version of 
 * 					TheBrig's lang.inc (online version)
 * git_ver			String (then floating point) version value within the
 * 					github version of TheBrig's lang.inc.
*/
header_remove('x-powered-by');
header_remove('content-type');
include ("config.inc");

// Initial value for brig version so that numeric comparisons don't fail
$thebrigversion=0;
// Creates an array from the contents of thebriginstaller, then trims it 
$workdir_f = file("/tmp/thebriginstaller");
$workdir = trim($workdir_f[0]);

// Read the contents of the downloaded lang file, then extract the version
$gitlangfile = file("{$workdir}/install_stage/conf/ext/thebrig/lang.inc");
$git_ver_s = preg_split ( "/VERSION_NBR, 'v/", $gitlangfile[1]);
$git_ver=substr($git_ver_s[1],0,3);

// We enter this if gl_statfs key is set and its value is numeric - this is done
// to check if we are at a version <1.0. 
if ( is_array($config['thebrig']) && isset($config['thebrig']['gl_statfs']) && 
	is_numeric ($config['thebrig']['gl_statfs'])  ) {
	// We're using a "modern" version - post Feb 2015
	$thebrigversion = $config['thebrig']['version'];
	if ($thebrigversion >= $git_ver) {
		// In this case, nothing about the config should change, since we
		// are using the same version. Since we downloaded a new tarball,
		// we are going to clear out the old files, copy known good ones,
		// and re-create the symlinks
		$message = "Looks like we're re-installing! \n"; 
		file_put_contents("/tmp/thebrig_action", "2");
	}
	else {
		// We are older than github, so we have some work to do...
		$message = "You are using and outdating version, so we'll upgrade \n";
		file_put_contents("/tmp/thebrig_action", "1");
		// Future code necessary for major/minor upgrades goes here.
	}
	$config['thebrig']['version'] = $git_ver;
	write_config();
	echo $message;
}  // End of gl_statfs check
elseif ( is_array($config['thebrig'] ) ) { 
	// Config key for gl_statfs is not set, or is not numeric. However, 
	// the XML config has some data about TheBrig. This means 
	// it was the previous version (< 1.0) of thebrig
	
// stop all jails
	exec ( "/usr/sbin/jls name", $runningjails);
	if ( is_array ( $runningjails ) && !empty( $runningjails )) {
		foreach ( $runningjails as $jail ) { 
			exec( "/etc/rc.d/jail stop " . $jail );
		} // end for
	} // end of check to make sure there are jails running 
			
	// This is in case there are some unknown jail parameters in the config
	// file. We create the file, but also mark it for deletion.
	$handle=fopen("/tmp/upgrademessage.txt", "w");
	$removemessage = 1;
	$message = "Warning! Please define the following parameters for jails manually. \n " ;
	fwrite ( $handle, $message );

// Begin alcatraz
// backup config in first
/*
 * TODO: Need to add XML config backup
 * 
 */
/**	if (is_dir ($config['thebrig']['rootfolder'] )) { */
// Check tag entry, folder, is writable and make backup
		/*	if ( ! copy ("/conf/config.xml ", $config['thebrig']['rootfolder']."config.xml.backup"))  {
				exec ("logger Failed copy rc script. TheBrig root folder not writable"); 
				exit;
				} else {} */
				
	// Create storage bins for old and new config - initialize variables
	$oldthebrigconf = array();
	$newthebrigconf = array();
	// Read in the current brig configuration as both new and old
	$oldthebrigconf = $config['thebrig'];
	$newthebrigconf = $oldthebrigconf;
	// Remove data about the jails from the new config array
	unset ( $newthebrigconf['content'] );
	unset ( $config['thebrig'] ); // Delete thebrig's config data
	// WHY DO WE NEED? write_config(); // write changes (in case the upgrade fails)
	// Initialize variables
	$config['thebrig'] = array();
	$config['thebrig']['content'] = array();
// conversion - begin updating XML config to 1.0 schema
	
	// Delete un-used global config keys
	unset ($newthebrigconf['sethostname']);
	unset ($newthebrigconf['unixiproute']);
	unset ($newthebrigconf['systenv']);
	// Add new global config parameter
	$newthebrigconf['gl_statfs'] = 1;
	// This is stupid - but if they immediately try to install again, it
	// will fail.
	if (isset( $oldthebrigconf['content'] ) ) {
		// Go through each of the jail's data from the old config
		foreach ( $oldthebrigconf['content'] as $jail) {
			// IP address conversion
			$jail['allowedip'] = $jail['if'] ."|". $jail['ipaddr'] ."/". $jail['subnet'] ;
			$jail['statfs'] = 1;
			$jail['cmd'] = array();
			// Prestart conversion
			if (!empty ( $jail['exec_prestart'])) {
				$jail['cmd'][] = "prestart|0|" .  $jail['exec_prestart'];
			}
			// Post Start command conversion
			if (!empty ($jail['afterstart0'])) { 
				$jail['cmd'][] = "afterstart_for_main|0|" .  $jail['afterstart0'];
				if (!empty ($jail['afterstart1'])) {
					$jail['cmd'][] = "afterstart_for_main|1|" .  $jail['afterstart1'];
				} 
			}
			if (!empty ($jail['jail_parameters'])) { 
				$message = "Detected parameters \"" . $jail['jailname']."\" :" . $jail['jail_parameters']."\n" ;
				fwrite ($handle, $message );
				$removemessage = 0;
			}
			
			// Remove unused/outdated configuration keys
			unset ($jail['if']);
			unset ($jail['ipaddr']);
			unset ($jail['subnet']);
			unset ($jail['exec_prestart']);
			unset ($jail['afterstart1']);
			unset ($jail['afterstart0']);
			unset ($jail['extraoptions']);
			unset ($jail['jail_parameters']);
			unset ($jail['image']);
			unset ($jail['image_type']);
			unset ($jail['attach_params']);
			unset ($jail['zfs_datasets']);
			unset ($jail['fib']);
			// Save the updated jail as a new array under 'content'
			$newthebrigconf['content'][] = $jail;
			// diagnose
			file_put_contents("/tmp/jailcache.txt", serialize($jail), FILE_APPEND);
		} // end foreach jails
	}
	fclose ( $handle ); // Close the manual upgrade file
	// We had custom jail parameters, we need to alert the user
	if ($removemessage == 0) { 
		echo "There were config settings we couldn't understand \n";
		echo "Please view the details at /tmp/upgrademessage.txt \n";
	}
	else { unlink ("/tmp/upgrademessage.txt"); }
		
	// Get rid of the old files & directories
	$old_folders = array ( "bin", "ext", "sbin", "jails", "libexec", );
	foreach ( $old_folders as $folder ) {
		exec ("/bin/rm -rf ".$config['thebrig']['rootfolder']."conf/".$folder);
	}
	exec ("/bin/rm /etc/rc.conf.local");
	$config['thebrig'] = $newthebrigconf;
	$config['thebrig']['version'] = $currentversion;
	write_config();
	// Let thebrig_install.sh know that this is an upgrade
	file_put_contents("/tmp/thebrig_action", "1");
}
else {
	// we are here because this is an initial install 
	// That is, there was nothing in the XML config yet.
	echo "Hello, we will begin installing TheBrig now \n";
	file_put_contents("/tmp/thebrig_action", "0"); 
	}
?>
