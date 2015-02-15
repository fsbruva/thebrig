<?php



function thebrig_unregister() {
 	global $config;
 	
 	$i = 0;
 	if ( is_array($config['rc']['postinit'] ) && is_array( $config['rc']['postinit']['cmd'] ) ) {
 		for ($i; $i < count($config['rc']['postinit']['cmd']); $i++) {
 		
 		///TODO - please refer into preg_match manual preg_match() returns 1 if the pattern matches given subject, 0 if it does not, or FALSE if an error occurred.
 		// Construction delete ALL cmd
 			if (1 === preg_match('/thebrig_start\.php/', $config['rc']['postinit']['cmd'][$i])) {
 				unset($config['rc']['postinit']['cmd'][$i]); }	// Disable the old startup
 		}	// end for
 	} // end if (is array)

 	// remove TheBrig related config and webgui settings - store folder location temporarily
 	$brigroot = $config['thebrig']['rootfolder'];
 	// clears the config variables
 	unset( $config['thebrig'] );
 	write_config();
 	mwexec("/bin/chmod -R 777 {$brigroot}");
 	// remove all the webgui stuff (particularly useful for "full" installs)
 	
 	// First, glob all the php files from the extensions folder into an array of fully pathed filenames
 	// and work through them one at a time as file. 
 	foreach ( glob( "{$brigroot}conf/ext/thebrig/*.php" ) as $file ) {
 			// Carry out the string replacement operation. For each file, search for the string 
 			//"{$brigroot}conf/ext/thebrig" and replace it with /usr/local/www. This will create a 
 			// string that has a fully pathed filename for the extension's webpages (which are 
 			// actually symbolic links we need to destroy.
 		$file1 = str_replace("{$brigroot}conf/ext/thebrig", "/usr/local/www", $file);
 			// only carry out the delete if it is a symbolic link (in case users have customised their own)
 		if ( is_link( $file1 ) ) {
 			unlink( $file1 ); 	// remove the symbolic link
 		} // end if
 	} // end for loop
 	

 	// Check to see if the folder is a folder, then remove it
 	if ( is_dir( "/usr/local/www/ext/thebrig/" ) ) {
 		rmdir( "/usr/local/www/ext/thebrig/" );
	}
 	elseif ( is_link("/usr/local/www/ext/thebrig") ){
		unlink ("/usr/local/www/ext/thebrig");
	}
 	# This counts the number of entries in the ext directory. If it is less than or equal to 2,
 	# the directory is empty, and can be removed (see http://www.php.net/manual/en/function.scandir.php)
 	if ( count( scandir( "/usr/local/www/ext/" ) ) <= 2 ){
 		rmdir( "/usr/local/www/ext/" );
 	}
	// Make the former root folder writeable by all, so that it can be easily deleted.
 	header("Location: /");
		exit;
} // end of thebrig_unregister function

// Function name: 	thebrig_populate
// Inputs: 			new_location 	Desired location of install
//					old_location	Former location of install
// Outputs: 		logical result of the creation of the proper directory structure in the install folder
// Description:		This function allows the installation (or movement of) the installation folder and
//					directory tree
function thebrig_populate ($new_location, $old_location) 
{
	global $config ;

	// Error checking prior to entry into this function guarantees $old_location will always be a directory

	// This indicates that the folders are actually different
	if ( strcmp ( realpath($old_location) , realpath($new_location) ) ){
		mwexec("/bin/mv {$old_location}conf {$new_location}");	// moves the conf
		mwexec("/bin/mv {$old_location}work {$new_location}");	// moves the work folder
		mwexec( "/bin/rm -r {$old_location}");						// get rid of the old folder
	}	// moves data from and removes the old directory 
	
	// Create the work directory if it doesn't already exist
	if ( ! is_dir ( "{$new_location}work/" ) ) {
		mwexec ( "mkdir -p {$new_location}work/") ;
	}
	
	// This section finds and unsets the startup command for the extension (code borrowed from slimserver install)
	$i = 0;
	if ( is_array($config['rc']['postinit'] ) && is_array( $config['rc']['postinit']['cmd'] ) ) {
		for ($i; $i < count($config['rc']['postinit']['cmd']); $i++) {
			if (1 == preg_match('/thebrig_start\.php/', $config['rc']['postinit']['cmd'][$i]))
				unset($config['rc']['postinit']['cmd'][$i]);	// Disable the old startup
		 	}	// end for		
	} // end if (is array)

	// This will search the startup commands to find the appropriate index to place thebrig's startup script command
	$i = 0;
	// Don't want to attempt array operations if there are no postinit commands.
	if ( is_array($config['rc']['postinit'] ) && is_array( $config['rc']['postinit']['cmd'] ) ) {
		for ($i; $i < count( $config['rc']['postinit']['cmd'] ); $i++) {
			// This loops through all the post init command script entries, and if it finds thebrig_start.sh (placed by hand), 
			// it will update the entry to reflect the new location by breaking out of the for loop at the correct index.
			if ( 1 == preg_match('/thebrig_start\.sh/', $config['rc']['postinit']['cmd'][$i]))
				break;
		} // end of for loop
	} // end of array if statment
	// update the value of the postinit command.
	$config['rc']['postinit']['cmd'][$i] = "/usr/local/bin/php-cgi {$new_location}conf/bin/thebrig_start.php";
	// update all the symbolic links and such by executing the shutdown script from the new location
	//mwexec("/bin/sh /mnt/data/thebrig/conf/bin/thebrig_stop.sh");
	$i = 0;
	if ( is_array($config['rc']['shutdown'] ) && is_array( $config['rc']['shutdown']['cmd'] ) ) {
		for ($i; $i < count($config['rc']['shutdown']['cmd']); $i++) {
			if (1 == preg_match('/thebrig_stop\.sh/', $config['rc']['shutdown']['cmd'][$i]))
				unset($config['rc']['shutdown']['cmd'][$i]);	// Disable the old startup
		 	}	// end for		
	} // end if (is array)
	// update the value of the shutdown command.
	$config['rc']['shutdown']['cmd'][$i] = "/bin/sh {$new_location}conf/bin/thebrig_stop.sh";
	
	if ( ! copy ( $config['thebrig']['rootfolder']."conf/bin/jail.sh", "/etc/rc.d/thebrig"))  
	{ exec ("logger Failed copy rc script");} 
	chmod("/etc/rc.d/thebrig", 0744);
	
	return 0;
}	// end of thebrig_populate()

// Function name: 	check_thebrig
// Inputs: 			none
// Outputs: 		logical result if thebrig has been installed
// Description:		This function evaluates whether or not thebrig has been properly installed
//					to the root folder yet
function check_thebrig() {
	global $config;		// import global config
		// The presence of /conf/jail_start is an analogue for the installation
	return file_exists($config['thebrig']['rootfolder'] . "conf/jail_start");
}

// Function name: 	thebrig_tarball_check
// Inputs: 			none
// Outputs: 		integer result based on the 
// Description:		This function evaluates whether or not the required tarballs are present
function thebrig_tarball_check () {
	global $config ;
	$result = 0 ;
	// The first thing we need to check for is the requisite official tarballs
	$myarch = exec("/usr/bin/uname -p");
	
	$base_search = thebrig_tarball_list("FreeBSD*base") ;
	// This indicates there are 0 base tarballs in the folder
	if ( count( $base_search ) == 0 ){
		$result += 1;
	}
	
	// There needs to be a lib32 tarball if Nas4free is 64 bit
	if ( $myarch == "amd64" ){
		$lib_search = thebrig_tarball_list("FreeBSD*lib32") ;
		// This indicates that there are no lib32 tarballs in work/
		if ( count( $lib_search ) == 0 ){
			$result += 32 ;
		} // End if count
	} // end if amd
	
	// It is also possible to build a jail with a custom tarball, so we search for all the tarballs that are not 
	// "official"
	$cust_search = thebrig_tarball_list( "*" , array( "FreeBSD"  ) ) ;
	if ( count ( $cust_search ) > 0 ) {
		$result = 0;
	}
	return $result ;
	
}

// Function name: 	thebrig_tarball_list
// Inputs: 			file_list			array of filenames with suffix to create list for
//					exclude				Optional array used to remove certain results
// Outputs: 		file_list			html formatted block with a radio next to each file
// Description:		This function creates an html code block with the files listed on the right
//					and radio buttons next to each on the left.
function thebrig_tarball_list ($contains , $exclude='') {
	global $config ;
	// This function creates a list of tarballs that match a certain filename pattern
	$installFiles = "";
	if ( is_dir( $config['thebrig']['rootfolder'] )) {
		$raw_list = glob("{$config['thebrig']['rootfolder']}work/{$contains}.{txz,tgz,tbz,tbz2,tb2,taz,tlz,zip,tar.xz}", GLOB_BRACE);
		$file_list = array_unique( $raw_list );
		if ( $exclude ) {
			foreach ( $exclude as $search_pattern ) {
				$file_list = preg_grep( "/{$search_pattern}/" , $file_list , PREG_GREP_INVERT );
			}
		sort ( $file_list , SORT_NUMERIC );
		}
	} // end of verifying rootfolder as valid location
	return $file_list ;
}

// Function name: 	thebrig_portsnap
// Inputs: 			dest_dir		array containing the location we want the updates to be applied to 
//					work_dir		array containing the location we want to use for temp downloads
//					conf_file		array containing the full path to the freebsd-update conf file
//					action			string to indicate the operation we should carry out. 
// Outputs: 		success			integer value, 0 indicating success, 1 indicating there was some sort of problem
//									with the preparations, 2 indicating there was some sort of problem cleaning up.
// Description:		This function takes the necessary steps to prepare the Nas4Free host to
function thebrig_update ( $dest_dir , $work_dir , $conf_file , $action ) {
	global $config;
	// First run the update tasks to make those binaries available to Nas4Free
	if ( thebrig_update_config ( true ) == true ){
		return 1; // If update_config returns anything other than a 0, there was a problem
	}
	else {
		$k = 0;
		for ( $k ; $k < count ($dest_dir ); $k++ ){

			$command_prefix = "/bin/sh " . $config['thebrig']['rootfolder'] . "conf/sbin/freebsd-update -d " . $work_dir[$k] . " -f " . $conf_file[$k] . " -b " . $dest_dir[$k];
			// The prep operation was successful

			if ( !is_dir($work_dir[$k]) )

				exec("mkdir -p " . $work_dir[$k] );

			switch ( $action ){
				case "Fetch":
					// Fetch only
					$command_fetch= $command_prefix . " fetch";
					exec($command_fetch);
					break;
				case "Install":
					// Update
					$command_update= $command_prefix . " install";
					exec($command_update);
					break;
				case "Fetch & Install":
					// Install --> fetch & extract
					$command_fetch= $command_prefix . " fetch install";
					mwexec($command_fetch );
					break;
				case "Cron":
					// Cron
					$command_cron= $command_prefix . " cron";
					mwexec($command_cron);
					break;

			} // end of switch

		} // end of for loop
		// Cleanup Nas4Free and return it to its previous state
		if ( thebrig_update_config ( false ) == true ){
			return 2; // if the cleanup returns anything other than 0, there was a problem.
		}
	}
	return 0;
}

// Function name: 	thebrig_portsnap
// Inputs: 			ports_dir		string containing the location we want the ports tree to be stored
//					work_dir		string containing the location we want portsnap to use for temp downloads
//					conf_file		string containing the full path to the portsnap conf file
//					action			integer to indicate the operation we should carry out. 0 = fetch , 1 = update  2 = install for the first time.				
// Outputs: 		success			integer value, 0 indicating success, 1 indicating there was some sort of problem
//									with the preparations, 2 indicating there was some sort of problem cleaning up.
// Description:		This function takes the necessary steps to prepare the Nas4Free host to
function thebrig_portsnap ( $ports_dir , $work_dir , $conf_file , $action ) {
	global $config;
	// First run the update tasks to make those binaries available to Nas4Free
	if ( thebrig_update_config ( true ) == true ){
		return 1; // If update_config returns anything other than a 0, there was a problem
	}
	else {
		$command_prefix = "/bin/sh " . $config['thebrig']['rootfolder'] . "conf/sbin/portsnap -d " . $work_dir . " -f " . $conf_file . " -p " . $ports_dir;
		// The prep operation was successful
		if (!is_dir($ports_dir))
			exec("mkdir -p " . $ports_dir );
		if ( !is_dir($work_dir))
			exec("mkdir -p " . $work_dir );

		switch ( $action ){
			case "Fetch":
				// Fetch only
				$command_fetch= $command_prefix . " fetch";
				exec($command_fetch);
				break;
			case "Update":
				// Update
				$command_update= $command_prefix . " update";
				exec($command_update);
				break;
			case "Fetch & Install":
				// Install --> fetch & extract
				$command_fetch= $command_prefix . " fetch extract";
				mwexec($command_fetch );
				break;
			case "Fetch & Update":
				// Update --> fetch & update
				$command_fetch= $command_prefix . " fetch update";
				mwexec($command_fetch);
				break;
			case "Cron":
				// Cron
				$command_cron= $command_prefix . " cron";
				mwexec($command_cron);
				break;
				
		}
		
		// Cleanup Nas4Free and return it to its previous state
		if ( thebrig_update_config ( false ) == true ){
			return 2; // if the cleanup returns anything other than 0, there was a problem.
		}
	}
	return 0;
}



// Function name: 	thebrig_update_config
// Inputs: 			setup			boolean value, true indicating we are setting up for update operations,
//									false indicating we are cleaning up from them
// Outputs: 		success			integer value, 0 indicating success, 1 indicating there was some sort of problem
//									with the preparations
// Description:		This function takes the necessary steps to prepare the Nas4Free host to 
function thebrig_update_config ( $setup ) {
	global $config;
	$brig_root = $config['thebrig']['rootfolder'];
	$bin_list = $config['thebrig']['update']['cmd'];
	$op_failure = false ;
	if ( $setup ) {
		// We want to prepare for portsnap and update operations by putting the files within the respective locations on the 
		// Nas4Free host. This is necessary because of the way portsnap and freebsd-update has certain paths fully qualified
		for ( $i = 0; $i <= 8; $i++ ){
			if ( file_exists ( "/usr/" . $bin_list[$i] ) ){
				// There is an existing file of the same name (should only be host, but who knows). We should make a backup
				exec ( "mv /usr/" . $bin_list[$i] . " " . "/usr/" . $bin_list[$i] . ".bak" , $blank, $result );
				$op_failure |= $result;
			}
			// Relocate the necessary files so Nas4Free can use them
			exec( "cp " . $brig_root . "conf/" . $bin_list[$i] . " " . "/usr/" . $bin_list[$i] , $blank, $result );
			$op_failure |= $result;
		} // End of for loop
	}
	else {
		// we want to cleanup after operations, so we need to get rid of the old copies of things, but only those things we messed with
		for ( $i = 0; $i <= 8; $i++ ){
			// Get rid of the the files we copied in
			exec( "rm /usr/" . $bin_list[$i] , $blank, $result );
			$op_failure |= $result;
			// If we made backups, we should restore them now
			if ( file_exists ( "/usr/" . $bin_list[$i] . ".bak") ){
				// There is an existing file of the same name (should only be host, but who knows). We should make a backup
				exec ( "mv /usr/" . $bin_list[$i] . ".bak " . "/usr/" . $bin_list[$i] , $blank, $result );
				$op_failure |= $result;
			}
		} // End of for loop
	}
	return $op_failure;
}// end of function


// Function name: 	thebrig_update_prep
// Inputs: 			none
// Outputs: 		success			integer value, 0 indicating success, 1 if there was a problem copying any of the
//									files, or 2 if there are no jails configured
// Description:		This function first checks if any of the files needed by takes the necessary steps to copy the files needed to perform some of the update tasks
// 					to the /conf location.
function thebrig_update_prep (  ) {
	global $config;
	// Store the list of files that portsnap and freebsd-update need
	if ( !is_array ( $config['thebrig']['update']['cmd'])){
		$config['thebrig']['update']['cmd'] = array ( "bin/host", "bin/comm" , "bin/join" , "bin/lam", "bin/look", "bin/bspatch", "bin/openssl" , "libexec/make_index" , "libexec/phttpget" );
		write_config();
	}
	$brig_root = $config['thebrig']['rootfolder'];
	$base_root = $config['thebrig']['basejail']['folder'];
	$copy_failure = false ; 
	// Run through the list of files.
	foreach ( $config['thebrig']['update']['cmd'] as $file ){
		// We want to first check to see if we even need to copy anything
		if ( is_file ( $brig_root . "conf/" . $file ) ) 
			continue; // Moves us out of the current "for" loop
		else {
			// We need to copy the file so Nas4Free has it. 
			// Before we look for them, we need to make sure we have the appropriate receptacles.
			if ( !is_dir ( $base_root . "conf/libexec" )) 
				exec( "mkdir " . $brig_root . "conf/libexec");
				
			// See if it's in the base jail
			if ( is_file ( $base_root . "usr/" . $file ) ) {
				exec ( "cp " . $base_root . "usr/" . $file . " " . $brig_root . "conf/" . $file , $blank , $result );
				$copy_failure |= $result;
				continue; // depart the for loop - we've found the file we needed
			}
			// it wasn't found in the basejail - we need to look elsewhere (all other jails)
			if ( isset ( $config['thebrig']['content'])){
				foreach ( $config['thebrig']['content'] as $jail ){
					if ( is_file ( $jail['jailpath'] . "usr/" . $file ) ) {
						exec ( "cp " . $jail['jailpath'] . "usr/" . $file . " " . $brig_root . "conf/" . $file , $blank , $result );
						$copy_failure |= $result;
						break; // depart the for loop - we've found the file we needed
					} // end if
				} // end for each jail
			}
			else {
				// There are no jails defined yet - stop
				return 2;
			}
			// Check if we were able to copy it to the destination location
			if ( !is_file ( $brig_root . "conf/" . $file ) ) {
				// We didn't have it to start, and we searched all jails - something's up. 	
				return 2;
			}
		} // end else (we needed to copy it
	}
	return (int)$copy_failure;
} // end of function



// Function name: 	thebrig_split_world
// Inputs: 			destination			string containing the jail's location
//					jail_type			boolean value, true indicating slim jail, false indicating full
//					source				Optional array containing the list of tarballs. Its absence indicates 
//										we are extracting from a buildworld.
// Outputs: 		none
// Description:		This function takes the necessary steps to create a jail, from a file moving/copying
//					standpoint.


function thebrig_split_world ( $destination , $jail_type ){
	global $config;
	
	if ( !$jail_type ){
		if ( func_num_args() === 3 ){
			$file_list = func_get_arg(2);
			// We are extracting from a list of traballs.
			foreach ( $file_list as $file ) {
				// Extract the desired tarballs to the destination jail.
				$commandextract = "tar xf " . $config['thebrig']['rootfolder'] . "work/" . $file . " -C " . $destination;
				mwexec( $commandextract );
			}
		}
		else{
			// We are extracting from a buildworld
			$commandextract = "tar -cf - -C " . $config['thebrig']['template'] . " . | (cd ". $destination . " && tar --unlink --keep-newer-files -xBpf -)";
			mwexec( $commandextract );
		}
		// Move the timezone info
		if ( is_dir ( $destination . "usr/share/zoneinfo/" )) {
			$commandtime = "cp ".$destination . "usr/share/zoneinfo/".$config['system']['timezone']." ".$destination."etc/localtime";
			mwexec ($commandtime);
		}
		// Move resolve.conf
		if ( ! is_file ( $destination . "etc/resolv.conf" )) {
			$commandresolve = "cp /var/etc/resolve.conf ".$destination."etc/resolve.conf";
			mwexec ($commandresolve);
		}
	}
	else {
		// We are attempting to make a thin jail
		if ( !is_dir( $config['thebrig']['basejail']['folder'] ) )
			exec( "mkdir -p " . $config['thebrig']['basejail']['folder'] );
		
		// these are needed for slim jails
		$dir_list = array ( "bin" , "boot" , "lib", "libexec", "sbin" , "usr/bin" , "usr/include" , "usr/lib" , "usr/libexec" , "usr/sbin", "usr/src" , "usr/share");
		if ( $myarch == "amd64")
			$dir_list[] = "usr/lib32";
		
		$base_switches = "";
		$slim_switches = "";
		foreach ( $dir_list as $dir ){
			$base_switches = $base_switches . "--include='./" . $dir . "/*' ";
			$slim_switches = $slim_switches . "--exclude='./" . $dir . "/*' ";
		}
		if ( func_num_args() === 3 ){
			$file_list = func_get_arg(2);
			foreach ( $file_list as $file ) {
				// Extract the desired tarball to the basejail.
				$commandextract = "tar " . $base_switches . " --unlink --keep-newer-files -xBpf " . $config['thebrig']['rootfolder'] . "work/" . $file . " -C " . $config['thebrig']['basejail']['folder'];
				mwexec( $commandextract );
				// Extract the tarball to thin jail
				$commandextract = "tar " . $slim_switches . " --unlink --keep-newer-files -xBpf " . $config['thebrig']['rootfolder'] . "work/" . $file . " -C " . $destination;
				mwexec( $commandextract );
			}
				
		}
		else{
			// We are extracting from a buildworld
			$commandextract = "tar " . $base_switches . " -cf - -C " . $config['thebrig']['template'] . " . | ( cd ". $config['thebrig']['basejail']['folder']. " && tar --unlink --keep-newer-files -xBpf -)";
			mwexec( $commandextract );
			$commandextract = "tar " . $slim_switches . " -cf - -C " . $config['thebrig']['template'] . " . | ( cd " . $destination . " && tar --unlink --keep-newer-files -xBpf -)";
			mwexec( $commandextract );
		}
		// Move the timezone info
		if ( is_dir ( $config['thebrig']['basejail']['folder'] . "usr/share/zoneinfo/" )) {
			$commandtime = "cp ".$config['thebrig']['basejail']['folder'] . "usr/share/zoneinfo/".$config['system']['timezone']." ".$destination."etc/localtime";
			mwexec ($commandtime);
		}
		// Move resolve.conf
		if ( ! is_file ( $destination . "etc/resolv.conf" )) {
			$commandresolve = "cp /var/etc/resolve.conf ".$destination."etc/resolve.conf";
			mwexec ($commandresolve);
		}
		// Create the necessary symlinks so the basejail's binaries can be accessed
		foreach ( $dir_list as $dir ) {
			exec ( "ln -s /basejail/" . $dir . " " . $destination . $dir );
		}
		// create the mountpoint for the basejail within the thin jail
		exec ("mkdir -p " . $destination . "basejail/") ;
		
		// Make backup of existing make file for later
		if ( file_exists($destination . "etc/periodic.conf"))
			exec("mv " . $destination . "etc/periodic.conf " . $destination. "etc/periodic.conf.bak");
		// Use the non-standard periodic file
		if ( is_dir( $destination. "etc/" ) )
		exec ( "cp " . $brig_root . "conf/periodic.conf " . $destination . "etc/");
		
		// ugly perl hack. Some applications require perl to be at /usr/bin/perl. So, we accomodate within the basejail.
		if ( ! is_dir( $config['thebrig']['basejail']['folder'] . "usr/bin/" )){
			exec ( "ln -s /usr/local/bin/perl " . $config['thebrig']['basejail']['folder'] . "usr/bin/perl" );
			exec ( "ln -s /usr/local/bin/perl5 " . $config['thebrig']['basejail']['folder'] . "usr/bin/perl5" );
		} // end of check for ugly perl hack
		
	} // end of else for all slim jails
	
	exec ( "echo 'sendmail_enable=\"NONE\"' >> " . $destination . "etc/rc.conf" );
	exec ( "echo 'syslogd_flags=\"-ss\"' >> " . $destination . "etc/rc.conf" );
	exec ( "echo 'rpc_bind=\"NO\"' >> " . $destination . "etc/rc.conf" );
	exec ( "echo 'network_interfaces=\"\"' >> " . $destination . "etc/rc.conf" );
	exec ( "echo 'cron_flags=\"\$cron_flags -J 15\"' >> " . $destination . "etc/rc.conf" );
	
} // end of function


// Function name: 	thebrig_radio_list
// Inputs: 			file_list			array of filenames with suffix to create list for
// Outputs: 		installFiles		html formatted block with a radio next to each file
// Description:		This function creates an html code block with the files listed on the right
//					and radio buttons next to each on the left. 
function thebrig_radio_list ($file_list) {
	global $config ;		// import the global config array 
	$installFiles = "";		// Initialize installFiles as an empty string so we can concatenate in the for loop
	if (is_dir($config['thebrig']['rootfolder'])) {		// check if the folder is a directory, so it doesn't choke
		foreach ( $file_list as $file) {
			$file = str_replace($config['thebrig']['rootfolder'] . "/", "", $file);
			$installFiles .= "<input type=\"radio\" name=\"installfile\" value=\"$file\"> "
			. str_replace("work/", "", $file)
			. "<br/>";
			} // end of completed folder, filename, suffix creation
	} // end of verifying rootfolder as valid location
	return $installFiles ;
}

// Function name: 	thebrig_checkbox_list
// Inputs: 			file_list			array of filenames with suffix to create list for
// Outputs: 		installFiles		html formatted block with a checkbox next to each file
// Description:		This function creates an html code block with the files listed on the right
//					and checkboxes next to each on the left.
function thebrig_checkbox_list ($file_list) {
	global $config ;
	$installFiles = "";
	if ( is_dir($config['thebrig']['rootfolder']) && count( $file_list ) > 0 ) {
		// Since we will be moving through the entirety of the $file_list array, we need a separate index counter, $i, 
		// which will start at 0. 
		$i = 0;
		// We will now figure out how many files we have. This number divided by 3 and rounded down
		// will tell us how many complete (two entry) rows in the table. Thus, we will run one loop to take care of each
		// complete row.
		$installFiles .= "<table border=\"0\" cellpadding=\"1\" width=\"100%\">";
		for ( $k = 1 ; $k <= floor( ( count( $file_list ) ) / 3 ) ; $k++ ) {
			$installFiles .= "<tr>";
				// This code removes the pesky path data from the globbed file list, then creates
				// an html checkbox entry for the file, and increments to the next index (i) 
				// This code is repeated for the other two columns.
				// Left Column
			$installFiles .= "<td width=\"33%\">" ;
			$file = str_replace( "{$config['thebrig']['rootfolder']}work/" , "", $file_list[$i]);
			$installFiles .= "<input type=\"checkbox\" name=\"formFiles[]\" value={$file}> "
			. " {$file} ";
			$i++;
			$installFiles .= "</td>";
			// Middle Column
			$installFiles .= "<td width=\"33%\">" ;
			$file = str_replace( "{$config['thebrig']['rootfolder']}work/" , "", $file_list[$i]);
			$installFiles .= "<input type=\"checkbox\" name=\"formFiles[]\" value={$file}> "
			.  " {$file} ";
			$i++;
			// Right Column - with a minor change - it is the end of a row, so we have a new line
			$installFiles .= "<td width=\"33%\">" ;
			$file = str_replace ("{$config['thebrig']['rootfolder']}work/" , "", $file_list[$i]);
			$installFiles .= "<input type=\"checkbox\" name=\"formFiles[]\" value={$file}> "
			.  " {$file}";
			$i++ ; 
			$installFiles .= "</td>";
			$installFiles .= "</tr>";
		} // end of row creation
		// This if statement checks if the remainder of the total list of files divided by 3 is at least 1, meaning
		// there are one or two entries in the bottom, incomplete row.
		if ( count( $file_list ) % 3 >= 1 ) {
			// This code removes the pesky path data from the globbed file list, then creates
			// an html checkbox entry for the file, and increments to the next index (i)
			$installFiles .= "<tr>";
			$installFiles .= "<td width=\"33%\">" ;
			$file = str_replace ("{$config['thebrig']['rootfolder']}work/" , "", $file_list[$i]);
			$installFiles .= "<input type=\"checkbox\" name=\"formFiles[]\" value={$file}> "
			.  " {$file} ";
			$i++ ;
			$installFiles .= "</td>";
			// This if statement checks if the remainder of the total list of files divided by 3 is 2, meaning
			// there are two entries in the bottom, incomplete row.
			if ( count( $file_list ) % 3 == 2 ) {
				// This code removes the pesky path data from the globbed file list, then creates
				// an html checkbox entry for the file, and increments to the next index (i)
				$installFiles .= "<td width=\"33%\">" ;
				$file = str_replace ("{$config['thebrig']['rootfolder']}work/" , "", $file_list[$i]);
				$installFiles .= "<input type=\"checkbox\" name=\"formFiles[]\" value={$file}> "
				.  " {$file}";
				$i++ ;
				$installFiles .= "</td>";
			} // end if filecount % 3 = 2
			// There is only one entry in the bottom row, so we need to finish the line
			else {
				$installFiles .= "</tr>";
			} // end else
		} // end if filecount % 3 >= 1
		// End the table
		$installFiles .= "</table>" ;
	} // end if root folder is a valid directory & there are at least some files
	return $installFiles ;
}


// Function name: 	thebrig_dl_list
// Inputs: 			file_list			array of filenames with suffix to create list for, with each filename
//										consisting of _partial_{filesize}.
// Outputs: 		partFiles			html formatted block with a status bar next to each file
// Description:		This function creates an html code block with the each of the files listed in file_list
//					and then a progress bar indicating how much it has downloaded. This is accomplished when
// 					the fetch operation first determines the file's size, and appends it as part of the file's name.
//					Thus, the data needed to determine download progress is already with the file.
function thebrig_dl_list ( $file_list ) {
global $config ;
	$partFiles = "";
	if ( is_dir($config['thebrig']['rootfolder']) && count( $file_list ) > 0 ) {
		// We will now figure out how many files we have. This number divided by 3 and rounded down
		// will tell us how many complete (two entry) rows in the table. Thus, we will run one loop to take care of each
		// complete row.
		$partFiles .= "";
		for ( $k = 0 ; $k < ( count( $file_list ) ) ; $k++ ) {
			// This code removes the pesky path data from the globbed file list, as well as removing the "partial_{filesize}"
			// It also converts all the underscores to spaces. This cleaned filename is then placed in the cell and the cell closed
			$cleanfile = str_replace( "{$config['thebrig']['rootfolder']}work/" , "", $file_list[$k]);
			// We find the index of the start of "_partial_". This is used to figure out where the size is.
			$partIndex = strripos($cleanfile , "_partial_");
			// We can now infer where the {filesize} starts, by adding 9 to the partIndex (length of _partial_)
			$sizeIndex = $partIndex + 9;
			// We also find the index of the start of the file suffix, to figure out where the end of the size lives.
			$suffixIndex = strripos($cleanfile , ".");
			// Now we can use where the number starts and the suffix begins to determine how many characters the filesize
			$sizeLength = $suffixIndex - $sizeIndex;
			// Now we extract the string starting at the start of the size, and going for as many characters are the filesize
			// string is. Then, we convert this string of numbers to an integer.
			$targetSize = intval ( substr( $cleanfile , $sizeIndex , $sizeLength ) ) ;
			// We also need to convert the filesizes to MiB for subsequent display
			$nicetargetSize = round( $targetSize / 1048576 , 2);
			// Obtain the current filesize from the OS
			$currentSize = filesize( $file_list[$k] );
			
			// Begin the first row, and first cell
			$partFiles .= "<tr><td width=\"25%\" class=\"vncellt\">";
			// Chop off everything after the first _ of _partial_ in the filename
			$cleanfile = substr( $cleanfile , 0 , $partIndex ) ;
			// Replace the hyphen with a space
			$cleanfile = str_replace( "-" , " ", $cleanfile ) ;
			// end the cell
			$partFiles .= "{$cleanfile}</td>";

			// Now we start building the right column, the one witht the progress bar
			$partFiles .= "<td width=\"75%\" class=\"listr\">" ;
			$pct_done = floor( $currentSize * 100 / $targetSize ) ;
			$pct_rem = 100 - $pct_done;
			
			$partFiles .= "<img src=\"bar_left.gif\" class=\"progbarl\" alt>";
			$partFiles .= "<img src=\"bar_blue.gif\" name=\"file{$k}u\" id=\"file{$k}u\" width=\"{$pct_done}px\" class=\"progbarcf\" alt>";
			$partFiles .= "<img src=\"bar_gray.gif\" name=\"file{$k}f\" id=\"file{$k}f\" width=\"{$pct_rem}px\" class=\"progbarc\" alt>";
			$partFiles .= "<img src=\"bar_right.gif\" class=\"progbarr\" alt>";
			$partFiles .= "<input style=\"padding: 0; border: 0;\" size=\"30\" name=\"file{$k}\" id=\"file{$k}\" value=\"{$pct_done}% of {$nicetargetSize}MiB\">";
			//$partFiles .= $pct_done ;
			
			$partFiles .= "</td>" ;
			$partFiles .= "</tr>";
		} // end of row creation
	} // end if root folder is a valid directory & there are at least some files
	return $partFiles ;
}

// Function name: 	thebrig_menu_list
// Inputs: 			list				array of filenames with suffix to create list for
// Outputs: 		menu				html formatted block of the menu
// Description:		This function creates an html code block with a dropdown box
function thebrig_menu_list ( $list , $list_name , $chosen ) {
	global $config ;
	// This function
	$menu = "<select name =\"{$list_name}\" id=\"{$list_name}_menu\">";
	// Build the select box one list item at a time
	foreach ( $list as $element) {
		// Check if the currently inspected element of the array 
		if ( strcmp($element, $chosen) == 0 ) {
			$menu .= "<option selected value = \"$element\"> $element </option> " ;
		}
		else {
			$menu .= "<option value = \"$element\"> $element </option> " ;
		}
	} // end of completed folder, filename, suffix creation
	$menu .= "</select>";
	return $menu ;
}
   

function get_nice_filesize ($file) {
	$dsize = filesize($file);
	if (strlen($dsize) <= 9 && strlen($dsize) >= 7) {
		$dsize = number_format($dsize / 1048576,1);
		return "$dsize MB";
	}
	elseif (strlen($dsize) >= 10) {
		$dsize = number_format($dsize / 1073741824,1);
		return "$dsize GB";
	}
	else {
		$dsize = number_format($dsize / 1024,1);
		return "$dsize KB";
	}
}
function write_rcconflocal () {
	global $config, $g;
	$testarray = array();
	$startonboot ="";
	// copy part of config in temporary array
	$testarray = $config['thebrig']['content'];
	$file = "/etc/rc.conf.local";
	$handle=fopen($file, "w");
	If (isset($config['thebrig'])) {
		fwrite ($handle, "jail_enable=\"YES\"\n");
	} else {fwrite($handle, "jail_enable=\"NO\"\n");
	}
	If (isset($config['thebrig']['parastart'])) {
		fwrite ($handle, "jail_parallel_start=\"YES\"\n");
	} else {fwrite($handle, "jail_parallel_start=\"NO\"\n");
	}
	If (isset($config['thebrig']['sethostname'])) {
		fwrite ($handle, "jail_set_hostname_allow=\"YES\"\n");
	} else {fwrite($handle, "jail_set_hostname_allow=\"NO\"\n");
	}
	If (isset($config['thebrig']['unixiproute'])) {
		fwrite ($handle, "jail_socket_unixiproute_only=\"YES\"\n"); exec ("/sbin/sysctl security.jail.socket_unixiproute_only=1");
	} else {fwrite($handle, "jail_socket_unixiproute_only=\"NO\"\n"); exec ("/sbin/sysctl security.jail.socket_unixiproute_only=0");
	}
	If (isset($config['thebrig']['systenv'])) {
		fwrite ($handle, "jail_sysvipc_allow=\"YES\"\n"); exec ("/sbin/sysctl security.jail.sysvipc_allowed=1");
	} else {fwrite($handle, "jail_sysvipc_allow=\"NO\"\n"); exec ("/sbin/sysctl security.jail.sysvipc_allowed=0");
	}
	// I explode multi array to small arrays and replace tag [cell(n)] to [number] number is 1,2,3,4...
	// with this trick I can make simple loop for write config
	array_sort_key($testarray, "jailno");
	foreach ($testarray as $out_jail ) {
		If (isset($out_jail['enable'])) {
			$startonboot = "{$startonboot} {$out_jail['jailname']}";
		} else {$startonboot = $startonboot;
		}
		fwrite ($handle, "##{$k}###########{$out_jail['jailname']}####{$out_jail['desc']}#####\n");
		$out_jail_comb = substr($out_jail['jailpath'], 0, strlen($out_jail['jailpath'])-1);
		fwrite ($handle, "jail_{$out_jail['jailname']}_rootdir=\"{$out_jail_comb}\"\n");
		fwrite ($handle, "jail_{$out_jail['jailname']}_hostname=\"{$out_jail['jailname']}.{$config['system']['domain']}\"\n");
		fwrite ($handle, "jail_{$out_jail['jailname']}_interface=\"{$out_jail['if']}\"\n");
		fwrite ($handle, "jail_{$out_jail['jailname']}_ip=\"{$out_jail['ipaddr']}/{$out_jail['subnet']}\"\n");
		fwrite ($handle, "jail_{$out_jail['jailname']}_exec_start=\"{$out_jail['exec_start']}\"\n");
		fwrite ($handle, "jail_{$out_jail['jailname']}_exec_afterstart0=\"{$out_jail['afterstart0']}\"\n");
		fwrite ($handle, "jail_{$out_jail['jailname']}_exec_afterstart1=\"{$out_jail['afterstart1']}\"\n");
		fwrite ($handle, "jail_{$out_jail['jailname']}_exec_stop=\"{$out_jail['exec_stop']}\"\n");
		fwrite ($handle, "jail_{$out_jail['jailname']}_flags=\"{$out_jail['extraoptions']}\"\n");
		fwrite ($handle, "jail_{$out_jail['jailname']}_parameters=\"{$out_jail['jail_parameters']}\"\n");
		fwrite($handle, "jail_{$out_jail['jailname']}_fdescfs_enable=\"NO\"\n");
		fwrite ($handle, "jail_{$out_jail['jailname']}_fstab=\"/etc/fstab.{$out_jail['jailname']}\"\n");
		If (isset($out_jail['jail_mount'])) {
			fwrite ($handle, "jail_{$out_jail['jailname']}_mount_enable=\"YES\"\n");
		} else {fwrite($handle, "jail_{$out_jail['jailname']}_mount_enable=\"NO\"\n");
		}
		fwrite ($handle, "jail_{$out_jail['jailname']}_devfs_ruleset=\"{$out_jail['devfsrules']}\"\n");
		If (isset($out_jail['devfs_enable'])) {
			fwrite ($handle, "jail_{$out_jail['jailname']}_devfs_enable=\"YES\"\n");
		} else {fwrite($handle, "jail_{$out_jail['jailname']}_devfs_enable=\"NO\"\n");
		}
		If (isset($out_jail['proc_enable'])) {
			fwrite ($handle, "jail_{$out_jail['jailname']}_procfs_enable=\"YES\"\n");
		} else {fwrite($handle, "jail_{$out_jail['jailname']}_procfs_enable=\"NO\"\n");
		}
		$fstabfile= "/etc/fstab.{$out_jail['jailname']}";
		$handle1 = fopen($fstabfile, "w");
		if  ($out_jail['jail_type'] === "slim") {
			$basejailfolder	= 	 substr($config['thebrig']['basejail']['folder'], 0, strlen($config['thebrig']['basejail']['folder'])-1);
			fwrite( $handle1 ,  $basejailfolder . " " .  $out_jail['jailpath'] . "basejail nullfs ro 0 0\n");
		}
		if (TRUE == is_array($out_jail['auxparam'])) {
			foreach ($out_jail['auxparam'] as $fstab) {
				fwrite ($handle1, $fstab."\n");
			}
		} else {}
		if (isset($out_jail['ports'])) { 
		$portsjailfolder	= 	$config['thebrig']['rootfolder']."conf/ports";
		fwrite( $handle1 ,  $portsjailfolder . " " .  $out_jail['jailpath'] . "usr/ports nullfs ro 0 0\n");
		} else {}
		if  (($out_jail['jail_type'] === "slim") || ($out_jail['jail_type'] === "full")) {
			If (isset($out_jail['devfs_enable'])) { fwrite( $handle1 ,  "devfs " .  $out_jail['jailpath'] . "dev devfs rw 0 0\n");} else {}
			If (isset($out_jail['proc_enable'])) { fwrite( $handle1 ,  "procfs " .  $out_jail['jailpath'] . "proc procfs rw 0 0\n");} else {}
			}
		fclose($handle1);
	}
	$startonboot = ltrim( $startonboot );
	fwrite ($handle, "jail_list=\"{$startonboot}\"\n");
	fclose($handle);
}   
// cmd_exec execute process and have output and error
//$cmd - proccess 
//$stdout - result
//$stderr - messages about error
function cmd_exec($cmd, &$stdout, &$stderr)
{
    $outfile = tempnam("/tmp", "cmd");
    $errfile = tempnam("/tmp", "cmd");
    $descriptorspec = array(
        0 => array("pipe", "r"),
        1 => array("file", $outfile, "w"),
        2 => array("file", $errfile, "w")
    );
    $proc = proc_open($cmd, $descriptorspec, $pipes);
    
    if (!is_resource($proc)) return 255;

    fclose($pipes[0]);    //Don't really want to give any input

    $exit = proc_close($proc);
    $stdout = file($outfile);
    $stderr = file($errfile);

    unlink($outfile);
    unlink($errfile);
    return $exit;
}
// write message to log. 
// $tolog - message for store
//$fac = facility - as ERROR, warning, info... etc
function write_briglog ($tolog, $fac) {
	global $config;
	$filelog = $config['thebrig']['rootfolder']."thebrig.log";
	$handle1 = fopen($filelog, "a+");
	if (is_array ($tolog)) { foreach ($tolog as $tolog1) {
	fwrite ($handle1, "[".date("Y/m/d H:i:s")."]: TheBrig " .$fac . ": ".trim($tolog1)."\n" ); }
	} else { fwrite ($handle1, "[".date("Y/m/d H:i:s")."]: TheBrig " .$fac . ": ".trim($tolog)."\n" ); }
	
	fclose ($handle1); 

}
//this function check if zfs used by system and defined 1 dataset (+1 as pool )
function brig_zfs_check() {	
	$output = array(); $errors = array(); 
	cmd_exec("zfs list -o jailed,name,mountpoint",$output, $errors);
	if (3 < count($output) ){ return true; } else { return false;}
}
function brig_datasets_list() {
	global $config;
	$output = array(); $errors = array(); 
	cmd_exec("zfs list -o jailed,name,mountpoint",$output, $errors);
	if (3 < count($output) ){
	for ($i=1; $i < count($output);) { 
		$stripped = preg_replace(array('/\s{2,}/', '/[\t\n]/'), ' ', $output[$i]);
		$cell[$i] = explode (" ", $stripped);
		++$i;
		}
		foreach ($cell as $cells ) { $cell1[] = array_filter($cells); }
		for ($i=0; $i< count($cell1);) { 
			$pattern1=$cell1[$i][2];
			$pattern1 = preg_quote($pattern1, '/' );
			$pattern = "/".$pattern1."/"; 
			$match = preg_match($pattern, $config['thebrig']['rootfolder']);
			if (1 == $match) { unset ($cell1[$i]); } 
			++$i;
			}
		$cell1 =  array_filter($cell1);
		foreach ($cell1 as $cells ) { $datasets_list[] = array_filter($cells); }
												
	return $datasets_list;
	} else { return false; }
}
function write_defs_rules() {
	global $config, $g;
	
	$file = "/etc/devfs.rules";
	$handle = fopen($file, "w");
	fwrite($handle, "##Thebrig rulesets##");
	$jails = $config['thebrig']['content'];
	array_sort_key($jails, "jailno");
	foreach ($jails as $out_jail ) {
		$ruleset_mumber = 4+$out_jail['jailno'];
		$ruleset_tag = "[devfsrules_jail_".$out_jail['jailname']."=".$ruleset_mumber."]";
				fwrite($handle, "\n".$ruleset_tag."\n");
				fwrite($handle, "add include \$devfsrules_hide_all\nadd include \$devfsrules_unhide_basic\nadd include \$devfsrules_unhide_login\n");
		if (is_array ($out_jail['rule'])) { foreach ($out_jail['rule'] as $rule ) { fwrite($handle, $rule."\n");}
			  }	  
	 }
	 fclose($handle);
}

 function write_jailconf () {
 	global $config, $g;
 			$startonboot ="";
 			if (!is_dir($config['thebrig']['rootfolder']."conf/jails")) { mkdir ($config['thebrig']['rootfolder']."conf/jails", 0755);} else {}
 			$file =  $config['thebrig']['rootfolder'] ."conf/thebrig.conf";
 			$handle=fopen($file, "w");
 	// copy part of config in temporary array
 			$t_jail = $config['thebrig']['content'];
			array_sort_key($t_jail, "jailno");
			foreach ($t_jail as $out_jail ) {
 				If (isset($out_jail['enable'])) {$a_startonboot[] = $out_jail['jailname']; } else { }
 				fwrite ($handle, $out_jail['jailname'] . "\t {\n"  );
 				$out_jail_comb = substr($out_jail['jailpath'], 0, strlen($out_jail['jailpath'])-1);
 				fwrite ($handle, "\tpath = \"" . $out_jail_comb . "\";\n");
 				fwrite ($handle, "\tpersist;\n");
 				fwrite ($handle, "\thost.hostname =\"" . $out_jail['jailname'] .".". $config['system']['domain'] ."\";\n");
 				fwrite ($handle, "\tenforce_statfs =" . $out_jail['statfs'] .";\n");
 				fwrite ($handle, "\tdevfs_ruleset=" . (4+ $out_jail['jailno']) .";\n");
 				fwrite ($handle, "\tmount.fstab=\"".$config['thebrig']['rootfolder'] ."conf/jails/fstab." . $out_jail['jailname'] ."\";\n");
 				if (isset($out_jail['jail_vnet'] )) {  fwrite ($handle, "\tvnet;\n"); } else {
 				
 					if (count ($out_jail['allowedip'] ) > 1 ) {
 							foreach ($out_jail['allowedip'] as $a_ips ) {
 								$b_ips = explode("|", $a_ips);
 								$c_ips = explode ("/", $b_ips[1]);
 								if (is_ipv4addr($c_ips[0])) { 
 									fwrite ($handle, "\tip4.addr += \"" . $a_ips . "\";\n");
 								} else {
 									fwrite ($handle, "\tip6.addr += \"" . $a_ips ."\";\n");
 								}
							  // check, if this schema work.  If not --> replace number0 as ip = re|dfgfgfg
 							}
 				} else { /* if only one address specified */
 				$a_ips = $out_jail['allowedip'][0];
					$b_ips = explode("|", $a_ips);
					$c_ips = explode ("/", $b_ips[1]);
					if (is_ipv4addr($c_ips[0])) {
						fwrite ($handle, "\tip4.addr = \"" . $out_jail['allowedip'][0] . "\";\n");
					} else {
						fwrite ($handle, "\tip6.addr = \"" . $out_jail['allowedip'][0] . "\";\n");
						}
					
				} 	/* end of allowed ip */ } /* end of networking */
				
				if (is_array($out_jail['param'] )) {
					foreach ($out_jail['param'] as $a_param ) { fwrite ($handle, "\t". $a_param .";\n"); }  }
		// create fstab
				$fstabfile=  $config['thebrig']['rootfolder'] . "conf/jails/fstab.{$out_jail['jailname']}";
				$handle1 = fopen($fstabfile, "w");
				if  ($out_jail['jail_type'] === "slim") {
					$basejailfolder	= 	 substr($config['thebrig']['basejail']['folder'], 0, strlen($config['thebrig']['basejail']['folder'])-1);
					fwrite( $handle1 ,  $basejailfolder . " " .  $out_jail['jailpath'] . "basejail nullfs ro 0 0\n");
					}
				if (TRUE == is_array($out_jail['auxparam'])) {
					  foreach ($out_jail['auxparam'] as $fstab) {  fwrite ($handle1, $fstab."\n"); }
				} else {}
				if (isset($out_jail['ports'])) { 
					$portsjailfolder	= 	$config['thebrig']['rootfolder']."conf/ports";
					fwrite( $handle1 ,  $portsjailfolder . " " .  $out_jail['jailpath'] . "usr/ports nullfs ro 0 0\n");
				  } else {}
				If (isset($out_jail['devfs_enable'])) { fwrite( $handle , "\tmount.devfs;\n") ;} else {}
				if  (($out_jail['jail_type'] === "slim") || ($out_jail['jail_type'] === "full")) {
					 // If (isset($out_jail['fdescfs_enable'])) { fwrite( $handle1 ,  "fdescfs " .  $out_jail['jailpath'] . "dev/fd fdescfs rw 0 0\n");} else {}
					  If (isset($out_jail['proc_enable'])) { fwrite( $handle1 ,  "procfs " .  $out_jail['jailpath'] . "proc procfs rw 0 0\n");} else {}
				}
				fclose($handle1);
				
				 
		// commands processing
		
				
				if (TRUE == is_array($out_jail['cmd'])) { $mess = "zfs not working"; $facility = "info"; 
					  //if (!is_dir("/etc/jails")) mkdir( "/etc/jails", 0755);
					  $commandfile=  $config['thebrig']['rootfolder']."conf/jails/prestart.". $out_jail['jailname'];
					  $handle1 = fopen( $commandfile, "w");
					  fwrite ($handle1, "#!/bin/sh\n#\n");
					  $prestart_cmd = preg_grep("/^prestart\|/", $out_jail['cmd']);
					 
					  foreach ($prestart_cmd as $command) { $a_command = explode( "|", $command); 
						   fwrite ($handle1, $a_command[2] . "\n"); }
			 // zfs prepare 
			 // check zfs health 
					if (false === brig_zfs_check()) {  write_briglog (" zfs not running ", "INFO");} else {
					if (isset($out_jail['zfs_enable']) && !empty($out_jail['zfs_datasets']) ) {
						$cachefile = $config['thebrig']['rootfolder']."conf/jails/zfscache.". $out_jail['jailname'];
						$handle2 = fopen( $cachefile, "w");
						
						$a_dataset = explode(";",$out_jail['zfs_datasets']);
						foreach ($a_dataset as $b_dataset) {
							$c_dataset =  explode("|",$b_dataset);
							fwrite ($handle1, "zfs set jailed=on " . $c_dataset[0] . "\n");
							$mainmountpoint = exec ("zfs get -H mountpoint " . $c_dataset[0] . " | awk '{print \$3}'");
							fwrite ($handle2, $mainmountpoint . " " . $c_dataset[0] . "\n");
							
							} fclose ($handle2);} 
					}
			// vnet script
					   if (isset($out_jail['jail_vnet'] )) {	  
						fwrite ($handle1, "sysctl net.inet.ip.forwarding=1 >/dev/null\n");
						fwrite ($handle1, "ifconfig bridge" . ($out_jail['jailno']+10) ." create >/dev/null\n");
						fwrite ($handle1, "ifconfig bridge" . ($out_jail['jailno']+10) ." inet " . $out_jail['epair_a_ip'] ."/" . $out_jail['epair_a_mask'] ."\n");
						fwrite ($handle1, "ifconfig bridge" . ($out_jail['jailno']+10) ." addm " . $out_jail['if'] ."\n");						
						fwrite ($handle1, "ifconfig epair" . $out_jail['jailno']." create\n");
						fwrite ($handle1, "ifconfig bridge" .($out_jail['jailno']+10) . " addm epair" . $out_jail['jailno']."a\n");
						fwrite ($handle1, "ifconfig epair". $out_jail['jailno']."a up\n");
					  } else {} 
					  fclose($handle1);
					  
			 // poststart script
					  $commandfile=  $config['thebrig']['rootfolder'] . "conf/jails/poststart.". $out_jail['jailname'];
					  $handle1 = fopen( $commandfile, "w");
					  fwrite ($handle1, "#!/bin/sh\n#\n");
					  if (isset($out_jail['jail_vnet'] )) {
		 				fwrite ($handle1, "ifconfig epair".$out_jail['jailno']."b vnet " . $out_jail['jailname']."\n");
						fwrite ($handle1, "jexec " . $out_jail['jailname'] . " ifconfig epair".$out_jail['jailno']."b inet " . $out_jail['epair_b_ip']. "/" .  $out_jail['epair_b_mask'] . " up\n");
						fwrite ($handle1, "jexec " . $out_jail['jailname'] . " route add default " . $out_jail['epair_a_ip'] . "\n");
						fwrite ($handle1, "jexec " . $out_jail['jailname'] . " /bin/sh /etc/rc\n");
						//fwrite ($handle1, "ifconfig bridge" . ($out_jail['jailno']+10) ." addm " . $config['interfaces']['lan']['if'] ."\n");
					  }  else {}
					If (isset($out_jail['fdescfs_enable'])) { 
						   fwrite( $handle1 ,  "mount -t fdescfs null " . $out_jail['jailpath'] . "dev/fd\n");  
					    } else {}
				//zfs   
					if (isset($out_jail['zfs_enable']) && !empty($out_jail['zfs_datasets']) && (FALSE !== brig_zfs_check())) {
						
						fwrite ($handle1, "JID=`jls -j " . $out_jail['jailname'] . " jid`\n");
						$a_dataset = explode(";",$out_jail['zfs_datasets']);
						foreach ($a_dataset as $b_dataset) {
							$c_dataset =  explode("|",$b_dataset);
							fwrite ($handle1, "/sbin/zfs jail \${JID} " . $c_dataset[0] . "\n");
							fwrite ($handle1, "jexec " . $out_jail['jailname'] . " zfs set mountpoint=".$c_dataset[1]." ". $c_dataset[0]."\n");
							}
						fwrite ($handle1, "jexec " . $out_jail['jailname'] . " zfs mount -a\n");
					}
				
				// add users poststart command		  
					 $poststart_cmd_sys = preg_grep("/^afterstart_for_main\|/",$out_jail['cmd']);
					 if (!empty($poststart_cmd_sys)) {
						  
						foreach ($poststart_cmd_sys as $command) { 
							$a_command = explode( "|", $command); 
							fwrite ($handle1, $a_command[2] . "\n");
							
							  }
					    } else {}
					 $poststart_cmd_jail = preg_grep("/^afterstart_for_jail\|/", $out_jail['cmd']);
						foreach ($poststart_cmd_jail as $command) { 
							  $a_command = explode( "|", $command); 
							  fwrite ($handle1, "jexec " . $out_jail['jailname'] ." ". $a_command[2] . "\n"); 
						 }
					 fclose($handle1);
				 // prestop jail		
					 $commandfile=  $config['thebrig']['rootfolder'] . "conf/jails/prestop.". $out_jail['jailname'];
					 $handle1 = fopen( $commandfile, "w");
					 fwrite ($handle1, "#!/bin/sh\n#\n");
					 $prestop_cmd_jail = preg_grep("/^prestop_for_jail\|/", $out_jail['cmd']);
					 foreach ($prestop_cmd_jail as $command) { 
						   $a_command = explode( "|", $command); 
						   fwrite ($handle1, "jexec " . $out_jail['jailname'] ." ". $a_command[2] . "\n"); 
					   }
			
					  $prestop_cmd_sys = preg_grep("/^prestop_for_main\|/", $out_jail['cmd']);
					    foreach ($prestop_cmd_sys as $command) { 
						   $a_command = explode( "|", $command); 
						   fwrite ($handle1, $a_command[2] . "\n"); 						   
						   }
				//zfs_datasets
					 if (isset($out_jail['zfs_enable']) && !empty($out_jail['zfs_datasets']) && (FALSE !== brig_zfs_check())) {
						fwrite ($handle1, "JID=`jls -j " . $out_jail['jailname'] . " jid`\n");
						$a_dataset = explode(";",$out_jail['zfs_datasets']);
						foreach ($a_dataset as $b_dataset) {
							$c_dataset =  explode("|",$b_dataset);
							fwrite ($handle1, "jexec " . $out_jail['jailname'] ." zfs unmount -f " . $c_dataset[1] . "\n");
							fwrite ($handle1, "zfs unjail \${JID} " . $c_dataset[0] . "\n");
							fwrite ($handle1, "zfs set jailed=off " . $c_dataset[0] . "\n");
							//fwrite ($handle1, "zfs set mountpoint=/mnt/" . $c_dataset[0] ." ".$c_dataset[0] . "\n");
							}
						// restore saved mountpoints
						$cachefile_1 = file ($cachefile);
						foreach ($cachefile_1 as $line) { fwrite ($handle1, "zfs set mountpoint=" . $line ); }
					 }
					   If (isset($out_jail['fdescfs_enable'])) { 
						  fwrite( $handle1 ,  "umount " . $out_jail['jailpath']."dev/fd\n");
						    }
		   
					fclose($handle1);
					
					 $commandfile=  $config['thebrig']['rootfolder'] ."conf/jails/poststop.". $out_jail['jailname'];
					 $handle1 = fopen( $commandfile, "w");
					 fwrite ($handle1, "#!/bin/sh\n#\n");
					  if (isset($out_jail['jail_vnet'] )) {					 
						 fwrite( $handle1 ,  "ifconfig bridge".($out_jail['jailno']+10)." deletem epair".$out_jail['jailno']."a\n");
						 fwrite( $handle1 ,  "ifconfig epair". $out_jail['jailno']."a destroy\n");
						 fwrite( $handle1 ,  "ifconfig bridge".($out_jail['jailno']+10)." deletem " . $out_jail['if'] ."\n");
						 fwrite( $handle1 ,  "ifconfig bridge". ($out_jail['jailno']+10) ." destroy\n");
						 
					 }else{}
					 if (isset($out_jail['zfs_enable']) && !empty($out_jail['zfs_datasets'])&& (FALSE !== brig_zfs_check())) {
						fwrite ($handle1, "zfs mount -a");
					 }
					 $poststop_cmd = preg_grep("/^afterstop\|/", $out_jail['cmd']);
					    foreach ($poststop_cmd as $command) { 
						   $a_command = explode( "|", $command); 
						   fwrite ($handle1,  $a_command[2] . "\n"); 
						   }
					 fclose($handle1);
					  if (is_file( $config['thebrig']['rootfolder'] . "conf/jails/prestart.". $out_jail['jailname'])) { fwrite ($handle, "\texec.prestart =\"/bin/sh ". $config['thebrig']['rootfolder']."conf/jails/prestart.". $out_jail['jailname'] . "\";\n"); } else {}
					  if (false === isset($out_jail['jail_vnet'] )) { fwrite ($handle, "\texec.start =\"" . $out_jail['exec_start'] . "\";\n"); } else {}
					  if (is_file( $config['thebrig']['rootfolder'] ."conf/jails/poststart.". $out_jail['jailname'])) { fwrite ($handle, "\texec.poststart =\"/bin/sh ". $config['thebrig']['rootfolder']."conf/jails/poststart.". $out_jail['jailname'] . "\";\n"); } else {}
					  if (is_file( $config['thebrig']['rootfolder'] . "conf/jails/prestop.". $out_jail['jailname'])) { fwrite ($handle, "\texec.prestop =\"/bin/sh ". $config['thebrig']['rootfolder']."conf/jails/prestop.". $out_jail['jailname'] . "\";\n"); } else {}
					  fwrite ($handle, "\texec.stop =\"" . $out_jail['exec_stop'] . "\";\n");
					 if (is_file( $config['thebrig']['rootfolder'] . "conf/jails/poststop.". $out_jail['jailname'])) { fwrite ($handle, "\texec.poststop =\"/bin/sh ". $config['thebrig']['rootfolder']."conf/jails/poststop.". $out_jail['jailname'] . "\";\n"); } else {}

			
			
			 } else {}
				
			fwrite ($handle, "}\n"  );
			
			}
fclose($handle);
if (is_array ($a_startonboot)) { 
	$startonboot = implode(" ", $a_startonboot);
	mwexec("/usr/local/sbin/rconf attribute set jail_list {$startonboot}");  
	} else { 
	mwexec("/usr/local/sbin/rconf attribute remove jail_list");
	}

}
//  For Ajax
function get_jailinfo() {
	global $config;
	$tabledata = array();
	if (is_array($config['thebrig']['content']) ) { 
		array_sort_key($config['thebrig']['content'], "jailno");
		$jails =  $config['thebrig']['content'];
		foreach ($jails as $n_jail){
			$tabledata['name'][$n_jail["jailno"]] = $n_jail['jailname'];
			if (!is_dir( $n_jail['jailpath'] ."var/run")) 	{
					$tabledata['built'][$n_jail["jailno"]] = 'OFF';
					$tabledata['builtports'][$n_jail["jailno"]] = 'OFF';
					$tabledata['builtsrc'][$n_jail["jailno"]] = 'OFF';
				}else{
					$tabledata['built'][$n_jail["jailno"]] = 'ON';
					if (is_dir( $n_jail['jailpath'] . "usr/ports/Mk")) {
						$tabledata['builtports'][$n_jail["jailno"]] = "ON";
						} else {
						$tabledata['builtports'][$n_jail["jailno"]] = "OFF";
						}
					if (is_dir( $n_jail['jailpath'] . "usr/src/sys")) {
						$tabledata['builtsrc'][$n_jail["jailno"]] = "ON";
						} else {
						$tabledata['builtsrc'][$n_jail["jailno"]] = "OFF";
						}
				}	
			$file_id = "/var/run/jail_".$n_jail['jailname'].".id";
			if (true === is_file($file_id)) {
			$jail_id = exec ("jls -j ".$n_jail['jailname']. " jid");
					$sleep_cmd = "ps -o jid,stat -ax -J | awk 'BEGIN{c=0}\$1~\"{$jail_id}\"&&(\$2~\"S\"||\$2~\"I\")&&\$2!~\"S[\+]\"{++c}END{print c}'";
					$runn_cmd = "ps -o jid,stat -ax | awk 'BEGIN{c=0}\$1~\"{$jail_id}\"&&(\$2~\"R\"||\$2~\"S[\+]\"){++c}END{print c}'";
					$sleep_cnt = exec ( $sleep_cmd ); 
					$runn_cnt = exec ( $runn_cmd);
					$total = intval($sleep_cnt) + intval($runn_cnt);
					$tabledata['status'][$n_jail["jailno"]] = "{$total} processes: {$runn_cnt} running, {$sleep_cnt} sleeping";
					$tabledata['id'][$n_jail["jailno"]] = $jail_id;
					if (1 == exec ("jls -j ".$n_jail['jailname']. " vnet") ) { 
						$cmd = "jexec ".$n_jail['jailname']." ifconfig epair" . $n_jail["jailno"] ."b | grep inet | awk '{ print \$2}'";
						exec ($cmd, $result); 
						$tabledata['ip'][$n_jail["jailno"]] = implode(",", $result); } else {
					$tabledata['ip'][$n_jail["jailno"]] = exec ("jls -j ".$n_jail['jailname']." ip4.addr"); }
					$tabledata['hostname'][$n_jail["jailno"]] = exec ("jls -j ".$n_jail['jailname']." host.hostname");
					$tabledata['path'][$n_jail["jailno"]] = exec ("jls -j ".$n_jail['jailname']." path");
					$tabledata['file_id'][$n_jail["jailno"]] = $file_id;
				} else {
					$tabledata['status'][$n_jail["jailno"]] = 'OFF'; 
					$tabledata['id'][$n_jail["jailno"]] = 'OFF';
					$tabledata['ip'][$n_jail["jailno"]] = 'OFF';
					$tabledata['hostname'][$n_jail["jailno"]] = 'OFF';
					$tabledata['path'][$n_jail["jailno"]] = 'OFF';
					$tabledata['file_id'][$n_jail["jailno"]] = false;
				}
		}
	}
return $tabledata;
}
function print_thebrig_confirm_box() {
	echo "<div id='applybox'>";
	print_info_box("Do you realy want update TheBrig? ");
	echo "<input name=\"agree\" type=\"submit\" class=\"formbtn\" id=\"agree\" value=\"Agree\" />";
	echo "<input name=\"cancel\" type=\"submit\" class=\"formbtn\" id=\"cancel\" value=\"Cancel\" />";
	echo "</div>";
}
