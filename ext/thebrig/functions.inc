<?php


// function slimserver_initfolders($killpidfile) {
// 	global $config;

// 	if (!is_dir($config['slimserver']['rootfolder'])) {
// 		return;
// 	}

// 	if (!is_dir("{$config['slimserver']['rootfolder']}/etc")) {
// 		mkdir("{$config['slimserver']['rootfolder']}/etc");
// 	}

// 	if (!is_dir("{$config['slimserver']['rootfolder']}/etc/squeezebox")) {
// 		mkdir("{$config['slimserver']['rootfolder']}/etc/squeezebox");
// 	}

// 	if (!is_dir("{$config['slimserver']['rootfolder']}/var")) {
// 		mkdir("{$config['slimserver']['rootfolder']}/var");
// 	}

// 	if (!is_dir("{$config['slimserver']['rootfolder']}/var/squeezebox")) {
// 		mkdir("{$config['slimserver']['rootfolder']}/var/squeezebox");
// 	}

// 	if (!is_dir("{$config['slimserver']['rootfolder']}/var/squeezebox/Logs")) {
// 		mkdir("{$config['slimserver']['rootfolder']}/var/squeezebox/Logs");
// 	}

// 	if (!is_dir("{$config['slimserver']['rootfolder']}/var/squeezebox/Cache")) {
// 		mkdir("{$config['slimserver']['rootfolder']}/var/squeezebox/Cache");
// 	}

// 	if (!is_dir("/usr/local/www/ext")) {
// 		mkdir("/usr/local/www/ext");
// 	}

// 	if (!is_dir("/usr/local/www/ext/slimserver")) {
// 		mkdir("/usr/local/www/ext/slimserver");
// 	}


// 	mwexec("/usr/sbin/chown -R www:wheel {$config['slimserver']['rootfolder']}/squeezebox");
// 	mwexec("/usr/sbin/chown -R www:wheel {$config['slimserver']['rootfolder']}/var");
// 	mwexec("/usr/sbin/chown -R www:wheel {$config['slimserver']['rootfolder']}/etc");
// 	mwexec("/bin/chmod -R a+w {$config['slimserver']['rootfolder']}");
// 	mwexec("/bin/chmod -R g+w /ftmp");
// 	mwexec("/bin/chmod -R g+w /tmp");

// 	mwexec("/bin/cp -R -f {$config['slimserver']['rootfolder']}/usr/www/*.inc /usr/local/www/ext/slimserver/");
// 	mwexec("/bin/cp -R -f {$config['slimserver']['rootfolder']}/usr/www/*.php /usr/local/www/");
// }

// function slimserver_register() {
// 	global $config;

// 	$i = 0;
	
// 	if (is_array($config['rc']['postinit']['cmd'])) {
// 		for ($i; $i < count($config['rc']['postinit']['cmd']); $i++) {
// 			if (preg_match('/services_slimserver_init\.php/', $config['rc']['postinit']['cmd'][$i]))
// 				break;
// 		}
// 	}

// 	$config['rc']['postinit']['cmd'][$i] = "/usr/local/bin/php -f {$config['slimserver']['rootfolder']}/usr/www/services_slimserver_init.php";


// 	$i = 0;
// 	if (is_array($config['rc']['shutdown']['cmd'])) {
// 		for ($i; $i < count($config['rc']['shutdown']['cmd']); $i++) {
// 			if (preg_match('/services_slimserver_stop\.php/', $config['rc']['shutdown']['cmd'][$i]))
// 				break;
// 		}
// 	}

// 	$config['rc']['shutdown']['cmd'][$i] = "/usr/local/bin/php -f {$config['slimserver']['rootfolder']}/usr/www/services_slimserver_stop.php";

// 	write_config();
// }

function thebrig_unregister() {
 	global $config;
 	
 	$i = 0;
 	if (is_array($config['rc']['postinit']['cmd'])) {
 		for ($i; $i < count($config['rc']['postinit']['cmd']); $i++) {
 			if (preg_match('/thebrig_start\.sh/', $config['rc']['postinit']['cmd'][$i]))
 				unset($config['rc']['postinit']['cmd'][$i]);	// Disable the old startup
 		}	// end for
 	} // end if (is array)

 	// remove TheBrig related config and webgui settings - store folder location temporarily
 	$brigroot = $config['thebrig']['rootfolder'];
 	// clears the config variables
 	unset( $config['thebrig'] );
 	write_config();
 	
 	// remove all the webgui stuff (particularly useful for "full" installs
 	
 	// First, glob all the php files from the extensions folder into an array of fully pathed filenames
 	// and work through them one at a time as file. 
 	foreach ( glob( "{$brigroot}/ext/thebrig/*.php" ) as $file ) {
 			// Carry out the string replacement operation. For each file, search for the string 
 			//"{$brigroot}/ext/thebrig" and replace it with /usr/local/www. This will create a 
 			// string that has a fully pathed filename for the extension's webpages (which are 
 			// actually symbolic links we need to destroy.
 		$file = str_replace("{$brigroot}/ext/thebrig", "/usr/local/www", $file);
 			// only carry out the delete if it is a symbolic link (in case users have customised their own)
 		if ( is_link( $file ) ) {
 			unlink( $file ); 	// remove the ymbolic link
 		} // end if
 	} // end for loop
 	
 	// This loops runs for each of the files in the extension folder for TheBrig, and removes
 	// all the files
 	foreach ( glob( "/usr/local/www/ext/thebrig/*" ) as $file ) {
 		unlink( $file );
 	}

 	// Check to see if the folder is a folder, then remove it
 	if ( is_dir( "/usr/local/www/ext/thebrig" ) ) {
 		rmdir( "/usr/local/www/ext/thebrig" );
 	}
	// Make the former root folder writeable by all, so that it can be easily deleted.
 	mwexec("/bin/chmod -R 777 {$brigroot}");
} // end of thebrig_unregister function

function get_download_info($filePath , $feedURL) {
	exec("fetch -o" . $filePath . " " . $feedURL , $result);
	return implode("\n", $result);
}


// Function name: 	thebrig_populate
// Inputs: 			none
// Outputs: 		logical result of the creation of the proper directory structure in the install folder
// Description:		This function allows the installation (or movement of) the installation folder and
//					directory tree
function thebrig_populate ($new_location, $old_location) {
	global $config ;
	// First we need to see if old location has a directory tree. If it does, we chould clean up after
	// ourselves. This involves moving the php and .inc files. Then, the creation of the directory structure
	// and startup scripts can progress normally
	if ( is_dir ( $old_location )) {
		//mkdir("{$new_location}/ext" );		// Creates the new home for the web stuff
		//mkdir("{$new_location}/bin" );		// Creates the new home for the binaries 
		mwexec("/bin/mv -f {$old_location}/ext {$new_location}/");	// moves the web stuff
		mwexec( "/bin/mv -r {$old_location}/bin {$new_location}/");	// moves the binaries
		mkdir ( "{$new_location}/conf");
		mkdir ( "{$new_location}/work");
		touch ( "${new_location}/conf/jail_start" );	
		rmdir( $old_location );
		// This section finds and unsets the startup command for the extension (code from slimserver install)
		$i = 0;
		if (is_array($config['rc']['postinit']['cmd'])) {
			for ($i; $i < count($config['rc']['postinit']['cmd']); $i++) {
				if (preg_match('/thebrig_start\.sh/', $config['rc']['postinit']['cmd'][$i]))
					unset($config['rc']['postinit']['cmd'][$i]);	// Disable the old startup
			 	}	// end for		
		} // end if (is array)
	} // end if
	// This will search the startup commands to find the appropriate index to place thebrig's startup script command
	$i = 0;
	// Don't want to attempt array operations if there are no postinit commands.
	if ( is_array( $config['rc']['postinit']['cmd'] ) ) {
		for ($i; $i < count($config['rc']['postinit']['cmd']); $i++) {
			// This loops through all the post init command script entries, and if it finds thebrig_start.sh (placed by hand), 
			// it will update the entry to reflect the new location by breaking out of the for loop at the correct index.
			if (preg_match('/thebrig_start\.sh/', $config['rc']['postinit']['cmd'][$i]))
				break;
		} // end of for loop
	} // end of array if statment
	// update the value of the postinit command.
	$config['rc']['postinit']['cmd'][$i] = "/bin/sh {$new_location}/bin/thebrig_start.sh";
	// update all the symbolic links and such by executing the startup script from the new location
	mwexec("/bin/sh {$new_location}/bin/thebrig_start.sh");
}	// end of thebrig_populate()

// Function name: 	check_thebrig
// Inputs: 			none
// Outputs: 		logical result if thebrig has been installed
// Description:		This function evaluates whether or not thebrig has been properly installed
//					to the root folder yet
function check_thebrig() {
	global $config;		// import global config
		// The presence of /conf/jail_start is an analogue for the installation
	return file_exists($config['thebrig']['rootfolder'] . "/conf/jail_start");
}

// Function name: 	radio_filename_list
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

// Function name: 	radio_checkbox_list
// Inputs: 			file_list			array of filenames with suffix to create list for
// Outputs: 		installFiles		html formatted block with a radio next to each file
// Description:		This function creates an html code block with the files listed on the right
//					and radio buttons next to each on the left.
function thebrig_checkbox_list ($file_list) {
	global $config ;
	// This function
	$installFiles = "";
	if (is_dir($config['thebrig']['rootfolder'])) {
		foreach ( $file_list as $file) {
			$file = str_replace($config['thebrig']['rootfolder'] . "/work/", "", $file);
			$installFiles .= "<input type=\"checkbox\" name=\"formFiles[]\" value=\"$file\"> "
			. str_replace("work/", "", $file)
			. "  " . "<br/>";
		} // end of completed folder, filename, suffix creation
	} 
	return $installFiles ;
}

// Function name: 	thebrig_tarball_list
// Inputs: 			file_list			array of filenames with suffix to create list for
//					exclude				Optional array used to remove certain results
// Outputs: 		file_list			html formatted block with a radio next to each file
// Description:		This function creates an html code block with the files listed on the right
//					and radio buttons next to each on the left.
function thebrig_tarball_list ($contains , $exclude='') {
	global $config ;
	// This function
	$installFiles = "";
	if (is_dir($config['thebrig']['rootfolder'])) {
		$raw_list = glob($config['thebrig']['rootfolder'] . "/work/" . $contains . ".{txz,tgz}", GLOB_BRACE);
		$file_list = array_unique( $raw_list );
		if ( $exclude ) {
			foreach ( $exclude as $search_pattern ) {
				$file_list = preg_grep( "/" . $search_pattern . "/" , $file_list , PREG_GREP_INVERT );
			}
		}
	} // end of verifying rootfolder as valid location
	return $file_list ;
}

// Function name: 	thebrig_menu_list
// Inputs: 			list				array of filenames with suffix to create list for
// Outputs: 		menu				html formatted block of the menu
// Description:		This function creates an html code block with a dropdown box
function thebrig_menu_list ( $list , $list_name , $chosen ) {
	global $config ;
	// This function
	$menu = "<select name =\"$list_name\">";
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
?>
