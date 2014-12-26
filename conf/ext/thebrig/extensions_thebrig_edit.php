<?php
/*
 * extensions_thebrig_edit.php
	*/
require("auth.inc");
require("guiconfig.inc");
require_once("ext/thebrig/lang.inc");
require_once("ext/thebrig/functions.inc");
require("ext/thebrig/gui_addons.inc");
$in_jail_allow = array (
"allow.sysvipc",
"allow.raw_sockets",
"allow.chflags",
"allow.mount",
"allow.mount.devfs",
"allow.mount.nullfs",
"allow.mount.procfs",
"allow.mount.zfs",
"allow.quotas",
"allow.socket_af"
);
// I'm sorry, but I want next line commented.  I create page trap.php for trap _POST _GET messages, for testing my code.  
//  include_once ("ext/thebrig/trap.php");
if (is_file("/tmp/tempjail")){unlink ("/tmp/tempjail");}

//I check install.
if ( !isset( $config['thebrig']['rootfolder']) || !is_dir( $config['thebrig']['rootfolder']."work" )) {
	$input_errors[] = _THEBRIG_NOT_CONFIRMED;
} // end of elseif

// This determines if the page was arrived at because of an edit (the UUID of the jail)
// was passed to the page) or for a new creation.
if (isset($_GET['uuid'])) $uuid = $_GET['uuid']; // Use the existing jail's UUID
	
	
if (isset($_POST['uuid'])) $uuid = $_POST['uuid']; // Use the new jail's UUID
	
	

// Page title
$pgtitle = array(_THEBRIG_TITLE, _THEBRIG_JAIL, isset($uuid) ? _THEBRIG_EDIT : _THEBRIG_ADD );


// This checks if the current XML config has a section for jails, or if it's an array
if ( !isset($config['thebrig']['content']) || !is_array($config['thebrig']['content']) )
	// If the array doesn't exist, it is created.
	$config['thebrig']['content'] = array();

// This determines if the requisite tarballs exist in  work
$tar_check = thebrig_tarball_check();

// Since 1 gets added if there is no base, then we know if the result is odd, 
// we need a base tarball 
if ( $tar_check % 2 == 1 ) 
	$input_errors[] = _THEBRIG_NO_BASE ;

$myarch = exec("/usr/bin/uname -p");
// Since 32 gets added if there is no lib32, this lets us know we need one
if ( $tar_check > 31 && $myarch == "amd64" ) 
	$input_errors[] = _THEBRIG_NO_LIB32 ;



// This sorts thebrig's configuration array by the jailno
array_sort_key($config['thebrig']['content'], "jailno");
// This identifies the jail section of the XML, but does so by reference.
$a_jail = &$config['thebrig']['content'];

//$a_interface = array(get_ifname($config['interfaces']['lan']['if']) => "LAN"); for ($i = 1; isset($config['interfaces']['opt' . $i]); ++$i) { $a_interface[$config['interfaces']['opt' . $i]['if']] = $config['interfaces']['opt' . $i]['descr']; }

//$input_errors[] = implode ( " | " , array_keys ( $a_interface ));
//$input_errors[] = implode( " | " , $a_interface);
// This checks that the $uuid variable is set, and that the 
// attempt to determine the index of the jail config that has the same 
// uuid as the page was entered with is not empty
if (isset($uuid) && (FALSE !== ($cnid = array_search_ex($uuid, $a_jail, "uuid")))) {
	$pconfig['uuid'] = $a_jail[$cnid]['uuid'];
	$pconfig['enable'] = isset($a_jail[$cnid]['enable']);
	$pconfig['jailno'] = $a_jail[$cnid]['jailno'];
	$pconfig['jailname'] = $a_jail[$cnid]['jailname'];
	$pconfig['jail_type'] = $a_jail[$cnid]['jail_type'];
	$pconfig['param'] = $a_jail[$cnid]['param'];
	$pconfig['allowedip'] = $a_jail[$cnid]['allowedip'];  // new entries
	//$pconfig['if'] = $a_jail[$cnid]['if'];
	//$pconfig['ipaddr'] = $a_jail[$cnid]['ipaddr'];
	//$pconfig['subnet'] = $a_jail[$cnid]['subnet'];
	$pconfig['jail_vnet'] = isset($a_jail[$cnid]['jail_vnet']);
	$pconfig['epair_a_ip'] = $a_jail[$cnid]['epair_a_ip'];  // new entries = ip for systemside epair interface
	$pconfig['epair_a_mask'] = $a_jail[$cnid]['epair_a_mask'];  // new entries mask for systemside epair interface
	$pconfig['epair_b_ip'] = $a_jail[$cnid]['epair_b_ip'];  // new entries = ip for jailside epair interface
	$pconfig['epair_b_mask'] = $a_jail[$cnid]['epair_b_mask'];  // new entries mask for jailside epair interface
	$pconfig['jailpath'] = $a_jail[$cnid]['jailpath'];
	$pconfig['jail_mount'] = isset($a_jail[$cnid]['jail_mount']);
	$pconfig['statfs'] = $a_jail[$cnid]['statfs'];
	$pconfig['devfs_enable'] = isset($a_jail[$cnid]['devfs_enable']);
	$pconfig['proc_enable'] = isset($a_jail[$cnid]['proc_enable']);
	$pconfig['fdescfs_enable'] = isset($a_jail[$cnid]['fdescfs_enable']);
	$pconfig['rule'] = $a_jail[$cnid]['rule'];
	unset ($pconfig['auxparam']);
	if (isset($a_jail[$cnid]['auxparam']) && is_array($a_jail[$cnid]['auxparam']))
		$pconfig['auxparam'] = implode("\n", $a_jail[$cnid]['auxparam']);
	//$pconfig['exec_prestart'] = $a_jail[$cnid]['exec_prestart'];
	$pconfig['exec_start'] = $a_jail[$cnid]['exec_start'];
	$pconfig['cmd'] = $a_jail[$cnid]['cmd'];
	//$pconfig['afterstart0'] = $a_jail[$cnid]['afterstart0'];
	//$pconfig['afterstart1'] = $a_jail[$cnid]['afterstart1'];
	$pconfig['exec_stop'] = $a_jail[$cnid]['exec_stop'];
	//$pconfig['extraoptions'] = $a_jail[$cnid]['extraoptions'];
	//$pconfig['jail_parameters'] = $a_jail[$cnid]['jail_parameters'];
	$pconfig['desc'] = $a_jail[$cnid]['desc'];
	$pconfig['base_ver'] = $a_jail[$cnid]['base_ver'];
	$pconfig['lib_ver'] = $a_jail[$cnid]['lib_ver'];
	$pconfig['src_ver'] = $a_jail[$cnid]['src_ver'];
	$pconfig['doc_ver'] = $a_jail[$cnid]['doc_ver'];
	$pconfig['image'] = $a_jail[$cnid]['image'];
	$pconfig['image_type'] = $a_jail[$cnid]['image_type'];
	$pconfig['attach_params'] = $a_jail[$cnid]['attach_params'];
	$pconfig['attach_blocking'] = $a_jail[$cnid]['attach_blocking'];
	$pconfig['force_blocking'] = $a_jail[$cnid]['force_blocking'];
	$pconfig['zfs_datasets'] = $a_jail[$cnid]['zfs_datasets'];
	if (FALSE == $a_jail[$cnid]['fib']) { unset ($pconfig['fib']);} else {$pconfig['fib'] = $a_jail[$cnid]['fib'];}
	if (FALSE == $a_jail[$cnid]['ports']) { unset ($pconfig['ports']);} else {$pconfig['ports'] = $a_jail[$cnid]['ports'];}
	// $pconfig['ports'] = ( isset($a_jail[$cnid]['ports']) ) ? true : false ;
	// By default, when editing an existing jail, path and name will be read only.
	$path_ro = true;
	$name_ro = true;
	if ( !is_dir( $pconfig['jailpath']) ) {
		$input_errors[] = "The specified jail location does not exist - probably because you imported the jail's config. Please choose another.";
		$path_ro = false;
	}
	if ( (FALSE !== ( $ncid = array_search_ex($pconfig['jailname'], $a_jail, "jailname"))) && $ncid !== $cnid ){
		$input_errors[] = "The specified jailname is a duplicate - probably because you imported the jail's config. Please choose another.";	
		$name_ro = false;
	}
}
// In this case, the $uuid isn't set (this is a new jail), so set some default values
else {
	$pconfig['uuid'] = uuid();
	$pconfig['enable'] = false;
	$pconfig['jailno'] = thebrig_get_next_jailnumber();
	$pconfig['jailname'] = "";
	$pconfig['jail_type']="Slim";
	$pconfig['param'] = array("allow.mount", "allow.mount.devfs");
	unset ($pconfig['allowedip']);
	unset ($pconfig['jail_vnet']);
	$pconfig['epair_a_ip'] = "192.168.1.251"; 
	$pconfig['epair_a_mask'] = "24"; 
	$pconfig['epair_b_ip'] = "192.168.1.251"; 
	$pconfig['epair_b_mask'] = "24";	
	//$pconfig['if'] = "";
	//$pconfig['ipaddr'] = "";
	//$pconfig['subnet'] = "32";
	$pconfig['jailpath']="";
	$pconfig['jail_mount'] = true;
	$pconfig['statfs'] = "2";
	$pconfig['devfs_enable'] = false;
	$pconfig['proc_enable'] = false;
	$pconfig['fdescfs_enable'] = false;
	unset ($pconfig['rule'] );
	unset($pconfig['auxparam']);
	unset($pconfig['cmd']);
	//$pconfig['exec_prestart'] = "";
	$pconfig['exec_start'] = "/bin/sh /etc/rc";
	//$pconfig['afterstart0'] = "";
	//$pconfig['afterstart1'] = "";
	$pconfig['exec_stop'] = "/bin/sh /etc/rc.shutdown";
	//$pconfig['extraoptions'] = "";
	//$pconfig['jail_parameters'] = "";
	$pconfig['desc'] = "";
	$pconfig['base_ver'] = "Unknown";
	$pconfig['lib_ver'] = "Not Installed";
	$pconfig['src_ver'] = "Not Installed";
	$pconfig['doc_ver'] = "Not Installed";
	$pconfig['image'] = "";
	$pconfig['image_type'] = "";
	$pconfig['attach_params'] = "";
	$pconfig['attach_blocking'] = "";
	$pconfig['force_blocking'] = "";
	$pconfig['zfs_datasets'] = "";
	unset ($pconfig['fib']);
	$path_ro = false;
	$name_ro = false;
}


if ($_POST) {
	unset($input_errors);
	
	if (isset($_POST['Cancel']) && $_POST['Cancel']) {
		header("Location: extensions_thebrig.php");
		exit;
	}
	
	$pconfig = $_POST;
	// for clean work with new system env
	unset ($pconfig['allowedipfiletype']);
	unset ($pconfig['cmdfiletype']);
	unset ($pconfig['cmddatanice']);
	
	
	/*explode network entries and check IP addres.  I check if address, if not more then 1 IP adresses specified, and not more then 1 address in jails set.*/
	if (is_array( $pconfig['allowedip'] ) && !isset($pconfig['jail_vnet'])) {
	foreach ($pconfig['allowedip'] as $a_ips ) {
		  $b_ips = explode("|", $a_ips);
		  $c_ips = explode ("/", $b_ips[1]);
		 $delimit = "/".$c_ips[0]."/";
		  if (!is_ipaddr($c_ips[0])) $input_errors[] = sprintf( gettext("The attribute '%s' is not a valid IP address."), $c_ips[0]);
		  $matches = preg_grep ($delimit, $pconfig['allowedip'] );
		  if (count ($matches) > 1)  $input_errors[] = sprintf( gettext("Duplicate IP address input detected - '%s' "), $c_ips[0]);
		   $matches = preg_grep ($delimit, $a_jail );
		   if (count ($matches) > 1)  $input_errors[] = sprintf( gettext("The specified ip address '%s'  is already in use. Please choose another."), $c_ips[0]);
	}
	}
	// check device filesystem rules.  If we have one defined -> set enables  for devfs_enable
	if (isset ( $pconfig['rule'] ) && count ($pconfig['rule'] > 0) && !empty($pconfig['rule']) ) {
		 //$pconfig['jail_mount'] = "yes";
		 $pconfig['devfs_enable'] = "yes";
		 }
	
	// check alowes.  Subroutine check mount section checkboxes, and give allow values allow.mount.blabla, if user not define its.
	$cache_param_1 = array();
	$cache_param = array();
	if (  isset ( $pconfig['jail_mount'] ) ||  isset (  $pconfig['devfs_enable'] ) ||  isset ( $pconfig['proc_enable'] ) ) {
		
		if(is_array($pconfig['param'])) { foreach ($pconfig['param'] as $parameter) {
				unset ( $matches);
				$parameter_1 =  $parameter;
				preg_match ("/allow.mount/", $parameter_1, $matches );
				$matches_1[] = $matches;
				if (1 == $matches[0][1] )  { $cache_param[] =  $parameter_1; unset ($parameter); }
				}
						
			if ( isset ( $pconfig['proc_enable'] )) {  $cache_param_1[] = "allow.mount.procfs"; $cache_param_1[] = "allow.mount"; }
			if ( isset ( $pconfig['devfs_enable'] )) {  $cache_param_1[] = "allow.mount.devfs"; $cache_param_1[] = "allow.mount"; }
			$cache_param_1 = array_unique ( $cache_param_1 );
			$result = array_merge_recursive( $cache_param,  $cache_param_1);
			$result = array_unique (  $result );
			$pconfig['param'] =  array_merge_recursive ($pconfig['param'], $result);
			$pconfig['param'] = array_unique (  $pconfig['param'] );
			    // file_put_contents ("/tmp/thebrig.error.4.txt", serialize ($anyarray));  very nice for diagnostic.  Cache values
			}
	
		
		}

	
	/*  Primitive check jail commands for duplicates*/
	// Detect duplicates into commands numbers..  converted as <commandtip><number>, --> prestart5
	if (is_array ($pconfig['cmd'])) {
	foreach ($pconfig['cmd'] as $a_cmd ) {  $b_cmd = explode("|", $a_cmd); $c_cmd[] =  $b_cmd[0] . $b_cmd[1]; } 
	
	if (count($c_cmd) != count (array_unique($c_cmd)))  $input_errors[] = sprintf( gettext("Duplicate command detected, please inspect command mice values."));
	}
	$files_selected = $_POST['formFiles'];
	// Input validation.
	$reqdfields = explode(" ", "jailno jailname");
	$reqdfieldsn = array(gettext("Jail Number"), gettext("Jail Name") );
	$reqdfieldst = explode(" ", "numericint hostname");
	
	do_input_validation($pconfig, $reqdfields, $reqdfieldsn, $input_errors);
	do_input_validation_type($pconfig, $reqdfields, $reqdfieldsn, $reqdfieldst, $input_errors);
	
	// Check to see if duplicate jail names:
	$index = array_search_ex($pconfig['jailname'], $a_jail, "jailname");
	if ( FALSE !== $index ) {
		// If $index is not null, then there is a name conflict
		if (!(isset($uuid) && (FALSE !== $cnid )) )
			// This means we are not editing an existing jail - we are creating a new one
			$input_errors[] = "The specified jailname is already in use. Please choose another.";
	}
	
	// Ensure we are not attempting to create a jail whose name is used by thebrig, or install to a directory that
	// thebrig uses.
	$thebrig_names = array ("basejail" , "work" , "conf" , "template" ); 
	$thebrig_dirs = $thebrig_names + array ( "conf/ext" , "conf/bin");
	$thebrig_dirs = preg_replace("/(.+)/", $config['thebrig']['rootfolder'] . "$1/", $thebrig_dirs, 1);
	
	if ( array_search ($pconfig['jailname'], $thebrig_names ) !== FALSE)
		$input_errors[] = "The specified jailname is reserved. Please choose another.";
		
	
	
	// If they haven't set a path, then we need to assume one
	if ( ! isset($pconfig['jailpath']) || empty($pconfig['jailpath']) ) {
		$pconfig['jailpath']=$config['thebrig']['rootfolder'] . $pconfig['jailname'] ;
	}
	// Ensure there is a / after the folder name
	if ( $pconfig['jailpath'][strlen($pconfig['jailpath'])-1] != "/")  {
		$pconfig['jailpath'] = $pconfig['jailpath'] . "/";
	}
	
	// Check to make sure they are not attempting to install to a folder that thebrig uses.
	if ( array_search ($pconfig['jailpath'], $thebrig_dirs ) !== FALSE)
		$input_errors[] = "The specified jail location is reserved. Please choose another.";
		
			// Check to make sure there are not any duplicate files selected
	if ( count( $files_selected) > 0 ){
		$base_count = 0;
		$lib_count = 0;
		$doc_count = 0;
		$src_count = 0;
		// Examine the list of files specified by the user, verify no duplicates
		foreach ( $files_selected as $file ){
			$file_split = explode( '-', $file );
			if ( strcmp($file_split[0], 'FreeBSD') == 0 && strcmp($file_split[4], 'base.txz') == 0 ) {
				$base_count++;
				$pconfig['base_ver'] = $file_split[2] . "-" . $file_split[3]; 
			}
			elseif ( strcmp($file_split[0], 'FreeBSD') == 0 && strcmp($file_split[4], 'lib32.txz') == 0 ){
				$lib_count++;
				$pconfig['lib_ver'] = $file_split[2] . "-" . $file_split[3] ;
			}
			elseif ( strcmp($file_split[0], 'FreeBSD') == 0 && strcmp($file_split[4], 'doc.txz') == 0 ){
				$doc_count++;
				$pconfig['doc_ver'] = $file_split[2] . "-" . $file_split[3] ;
			}
			elseif ( strcmp($file_split[0], 'FreeBSD') == 0 && strcmp($file_split[4], 'src.txz') == 0 ){
				$src_count++;
				$pconfig['src_ver'] = $file_split[2] . "-" . $file_split[3] ;
			}
			else {
				$pconfig['base_ver']= "Unknown";
				$pconfig['lib_ver'] = "Unknown";
				$pconfig['src_ver'] = "Unknown";
				$pconfig['doc_ver'] = "Unknown";
			}
		} // End of foreach
		// Need to deal with keeping track of the lib version as the same as the base version
		if ( $myarch != "amd64" ){
			$pconfig['lib_ver'] = $pconfig['base_ver'] ;
		}
	} // end of if ( files selected )
		

	if ( $myarch != "amd64" && $lib_count > 1 ){
		$input_errors[] = "You have selected lib32, but you are running i386!!";
	}
	
	// Make sure only one tarball of each type is selected
	if ( $src_count > 1 || $base_count > 1 || $lib_count > 1 || $doc_count > 1 )
		$input_errors[] = "You have selected more than one of a given tarball type!!  Please select only one of each type";
		
	// If the specified path doesn't exist, we need to create it.
	if ( !is_dir( $pconfig['jailpath'] ) && ( count($input_errors) == 0 ) ) {
		if (!mkdir( $pconfig['jailpath'], 0774, true)) {
		     $input_errors[] ="Could not create directory for jail to live in!";
		      }
		  }
	
	// This is a second test to see if the directory was created properly.
	//if ( !is_dir( $pconfig['jailpath'] )){
	//	$input_errors[] = "Could not create directory for jail to live in!";
	//}
		
	// Validate if jail number is unique in order to reorder the jails (if necessary)
	// Alexey - why do we care about the jail number or the uuid?
	// Why not use the name?
	
	// a_jail is the list of all jails, sorted by their jail number
	
	if ( empty( $input_errors )) {
		// Index is the location within a_jail that has the same jailnumber as the one just entered
		$index = array_search_ex($pconfig['jailno'], $a_jail, "jailno");
		// for each jail? How can i determine the loop control variables?
		
		if ( FALSE !== $index ) {
			// If $index is not null, then there is a number conflict (the jail number use in $POST conflicts
			// with a currently configured jail's number. The jail that has the conflict is jail $index
		
			// So, starting with that jail, running through all the rest, their jail number needs to be incremented
			// by one, to allow for the insertion of the newest jail
			if (isset($uuid) && (FALSE !== $cnid )){
				// This indicates that we are editing an existing jail, with a uuid field that matches $uuid
				if ( $cnid < $index ){
					// This indicates that a list item has been made later
					// We need to move all the the ones that follow the old location earlier by one, up
					// to and including the conflict
					for ( $i = $cnid; $i <= $index ; $i++ ){
						$a_jail[$i]['jailno'] -= 1;
					} // end for
				} // end $cnid < $index
				elseif ( $cnid > $index ) {
					// This indicates that a list item has been made earlier
					// We need to move all the list items (starting with the conflict) later by one, up to
					// the currently edited jail's id
					for ( $i = $index; $i < $cnid ; $i++ ){
						$a_jail[$i]['jailno'] += 1;
					} // end for loop
				} // end elseif
			} // end of editing existing jail
			else {
				// This indicates we are creating a new jail
				for ( $i = $index; $i < count( $a_jail ); $i++ ){
					$a_jail[$i]['jailno'] += 1;
				} // end of for loop
			} // end of else (we're adding a new jail
		} // end of jail number conflict
		$jail = array();
		$jail['uuid'] = $pconfig['uuid'];
		$jail['enable'] = isset($pconfig['enable']) ? true : false;
		$jail['jailno'] = $pconfig['jailno'];
		$jail['jailname'] = $pconfig['jailname'];
		$jail['jail_type'] = $pconfig['jail_type'];
		$jail['param'] = $pconfig['param'];
		$jail['jailpath'] = $pconfig['jailpath'];
		
		$jail['allowedip'] = $pconfig['allowedip'];		
		$jail['jail_vnet'] = isset($pconfig['jail_vnet']) ? true : false;
		$jail['epair_a_ip'] = $pconfig['epair_a_ip'];  
		$jail['epair_a_mask'] = $pconfig['epair_a_mask'];  
		$jail['epair_b_ip'] = $pconfig['epair_b_ip']; 
		$jail['epair_b_mask'] = $pconfig['epair_b_mask'];
		
		$jail['rule'] = $pconfig['rule'];
		$jail['jail_mount'] = isset($pconfig['jail_mount']) ? true : false;
		$jail['statfs'] = $pconfig['statfs'];
		$jail['devfs_enable'] = isset($pconfig['devfs_enable']) ? true : false;
		$jail['proc_enable'] = isset($pconfig['proc_enable']) ? true : false;		
		$jail['fdescfs_enable'] = isset($pconfig['fdescfs_enable']) ? true : false;
		unset($jail['auxparam']);
		foreach (explode("\n", $_POST['auxparam']) as $auxparam) {
			$auxparam = trim($auxparam, "\t\n\r");
			if (!empty($auxparam)) $jail['auxparam'][] = $auxparam;
			else {};
			}
			
		$jail['cmd'] = $pconfig['cmd'];
		$jail['exec_start'] = $pconfig['exec_start'];
		//$jail['afterstart0'] = $pconfig['afterstart0'];
		//$jail['afterstart1'] = $pconfig['afterstart1'];
		$jail['exec_stop'] = $pconfig['exec_stop'];
		if (empty ($pconfig['extraoptions'])) { $pconfig['extraoptions'] = "-l -U root -n ".$pconfig['jailname'];} else {}
		//$jail['extraoptions'] = $pconfig['extraoptions'];
		//$jail['jail_parameters'] = $pconfig['jail_parameters'];
		$jail['desc'] = $pconfig['desc'];
		$jail['base_ver'] = $pconfig['base_ver'];
		$jail['lib_ver'] = $pconfig['lib_ver'];
		$jail['src_ver'] = $pconfig['src_ver'];
		$jail['doc_ver'] = $pconfig['doc_ver'];
		$jail['image'] = $pconfig['image'];
		$jail['image_type'] = $pconfig['image_type'];
		$jail['attach_params'] = $pconfig['attach_params'];
		$jail['attach_blocking'] = $pconfig['attach_blocking'];
		$jail['force_blocking'] = $pconfig['force_blocking'];
		$jail['zfs_datasets'] = $pconfig['zfs_datasets'];
		$jail['fib'] = $pconfig['fib'];
		$jail['ports'] = isset( $pconfig['ports'] ) ? true : false ;
		// Populate the jail. The simplest case is a full jail using tarballs.
		if ( $pconfig['source'] === "tarballs" && ( count ( $files_selected ) > 0 ) && $jail['jail_type'] === "full")
			thebrig_split_world($pconfig['jailpath'] , false , $files_selected );
		elseif ( $pconfig['source'] === "template" && $jail['jail_type'] === "full" )
			thebrig_split_world($pconfig['jailpath'] , false);
		// Next simplest is to split the world if we're making a slim jail out of tarballs.
		elseif ( $jail['jail_type'] === "slim" ) {
			// We know we're making a slim jail now
			$config['thebrig']['basejail']['base_ver'] = $pconfig['base_ver'];
			$config['thebrig']['basejail']['lib_ver'] = $pconfig['lib_ver'];
			$config['thebrig']['basejail']['src_ver'] = $pconfig['src_ver'];
			$config['thebrig']['basejail']['doc_ver'] = $pconfig['doc_ver'];
			if ( $pconfig['source'] === "tarballs" && count ( $files_selected ) > 0 ) 
				thebrig_split_world($pconfig['jailpath'] , true , $files_selected );
			elseif (  $pconfig['source'] === "template" )
				thebrig_split_world($pconfig['jailpath'] , true);
		}		
		// This determines if it was an update or a new jail
		if (isset($uuid) && (FALSE !== $cnid)) {
			// Copies newly modified properties over the old
			$a_jail[$cnid] = $jail;
			$mode = UPDATENOTIFY_MODE_MODIFIED;
		} else {
			// Copies the first jail into $a_jail
			$a_jail[] = $jail;
			$mode = UPDATENOTIFY_MODE_NEW;
		}
		
		updatenotify_set("thebrig", $mode, $jail['uuid']);
		write_config();

		header("Location: extensions_thebrig.php");
		exit;
	}
}

// Get next jail number.
function thebrig_get_next_jailnumber() {
	global $config;

	// Set starting jail number
	$jailno = 1;

	$a_jails = $config['thebrig']['content'];
	if (false !== array_search_ex(strval($jailno), $a_jails, "jailno")) {
		do {
			$jailno += 1; // Increase jail number until a unused one is found.
		} while (false !== array_search_ex(strval($jailno), $a_jails, "jailno"));
	}
	return $jailno;
}
function get_jail_interface_list() {
	/* build interface list with netstat */
	exec("/usr/bin/netstat -inW -f link", $linkinfo);
	array_shift($linkinfo);

	$iflist = array();

	foreach ($linkinfo as $link) {
		$alink = preg_split("/\s+/", $link);
		$ifname = chop($alink[0]);

		if (substr($ifname, -1) === "*")
			$ifname = substr($ifname, 0, strlen($ifname) - 1);
		/* add the plip interface to be excluded too */
		if (!preg_match("/^(ppp|sl|gif|faith|lo|plip|ipfw|usbus|carp)/", $ifname)) {
			$iflist[$ifname] = array();
			$iflist[$ifname]['mac'] = chop($alink[3]);
		}
	}
	return array_keys($iflist);
}
?>

<?php include("fbegin.inc");?>
<script type="text/javascript">//<![CDATA[
$(document).ready(function(){
	var gui = new GUI;
	$('#jail_type').change(function() {
		switch ($('#jail_type').val()) {
	case "slim":
		$('#mounts_separator_empty').show();
		$('#mounts_separator').show();
		$('#jail_mount_tr').hide();
		$('#devfs_enable_tr').show();
		$('#proc_enable_tr').show();
		$('#fdescfs_enable_tr').show();
		$('#install_source_empty').show();
		$('#install_source').show();
		$('#source_tr').show();
		$('#official_tr').show();
		$('#jail_mount').attr('checked', true);


		break;
	case "full":	
		$('#mounts_separator_empty').show();
		$('#mounts_separator').show();
		$('#jail_mount_tr').show();
		$('#devfs_enable_tr').show();
		$('#proc_enable_tr').show();
		$('#fdescfs_enable_tr').show();
		$('#install_source_empty').show();
		$('#install_source').show();
		$('#source_tr').show();
		$('#official_tr').show();



		break;
	case "linux":	
		$('#mounts_separator_empty').hide();
		$('#mounts_separator').hide();
		$('#jail_mount_tr').hide();
		$('#devfs_enable_tr').hide();
		$('#proc_enable_tr').hide();
		$('#fdescfs_enable_tr').hide();
		$('#install_source_empty').hide();
		$('#install_source').hide();
		$('#source_tr').hide();
		$('#official_tr').hide();
		$('#jail_mount').prop('checked', true);
		$('#devfs_enable').prop('checked', true);
		$('#proc_enable').prop('checked', true);
		break;	
	case "custom":
		$('#mounts_separator_empty').show();
		$('#mounts_separator').show();
		$('#jail_mount_tr').show();
		$('#devfs_enable_tr').show();
		$('#proc_enable_tr').show();
		$('#fdescfs_enable_tr').hide();
		$('#install_source_empty').hide();
		$('#install_source').hide();
		$('#source_tr').hide();
		$('#official_tr').hide();



		break;
		}
	});
$('#source').change(function(){
	switch ($('#source').val()) {
	case "tarballs":
		$('#official_tr').show();	
		$('#homegrown_tr').show();
		break;
	case "template":
		$('#official_tr').hide();
		$('#homegrown_tr').hide();
		}
	});
$('#devfs_type').change(function() {
	switch ($('#devfs_enable').val()) {
	case "parent":
		$('#mounts_separator_empty').show();
		break;
	case "standart":
		$('#mounts_separator_empty').show();
		break;
	case "custom":
		$('#mounts_separator_empty').show();
		break;
		}
	});
$('#jail_vnet').change(function() {
		switch ($('#jail_vnet').is(':checked')) {
		case false :
			$('#allowedip_tr').show(1500);
			$('#epair_tr').hide(300);
			$('#exec_start_tr').show(300);
			break;
			
		case true :	
			$('#allowedip_tr').hide(300);
			$('#epair_tr').show(1500);
			$('#exec_start_tr').hide(1500);
			break;
            } 
        });
	
$('#jail_type').change();
$('#source').change();
$('#jail_vnet').change();
});
function jail_mount_enable() {
	switch (document.iform.jail_mount.checked) {
	case false:
			showElementById('statfs_tr','hide');
			break;

		case true:
			showElementById('statfs_tr','show');
			break;
		}
	}
function helpbox() { alert("Slim - This is a fully functional jail, but when first installed, only occupies about 2 MB in its folder.\n\n full - This is a full sized jail, about 300 MB per jail, and is completely self contained.\n\n Linux - jail for Linux, such Debian.\n\n custom- this only create jail folder and make simulation without install. Usefull for migrate jails." ); }
function redirect() { window.location = "extensions_thebrig_fstab.php?uuid=<?=$pconfig['uuid'];?>&act=editor" }
//]]>
</script>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr><td class="tabnavtbl">
		<ul id="tabnav">
			<li class="tabact"><a href="extensions_thebrig.php"><span><?=_THEBRIG_JAILS;?></span></a></li>
			<?php If (!empty($config['thebrig']['content'])) { 
			$thebrigupdates=_THEBRIG_UPDATES;
			echo "<li class=\"tabinact\"><a href=\"extensions_thebrig_update.php\"><span>{$thebrigupdates}</span></a></li>";
			} else {} ?>
			<li class="tabinact"><a href="extensions_thebrig_config.php"><span><?=_THEBRIG_MAINTENANCE;?></span></a></li>
		</ul>
	</td></tr>
		<td class="tabcont">
      <form action="extensions_thebrig_edit.php" method="post" name="iform" id="iform"> 
      <!-- <form action="test.php" method="post" name="iform" id="iform"> -->
      <input name="jailpath" type="hidden" value="<?=$pconfig['jailpath'];?>" />
					<input name="base_ver" type="hidden" value="<?=$pconfig['base_ver'];?>" />
					<input name="lib_ver" type="hidden" value="<?=$pconfig['lib_ver'];?>" />
					<input name="doc_ver" type="hidden" value="<?=$pconfig['doc_ver'];?>" />
					<input name="src_ver" type="hidden" value="<?=$pconfig['src_ver'];?>" />
					<input name="image" type="hidden" value="<?=$pconfig['image'];?>" />
					<input name="image_type" type="hidden" value="<?=$pconfig['image_type'];?>" />
					<input name="attach_params" type="hidden" value="<?=$pconfig['attach_params'];?>" />
					<input name="force_blocking" type="hidden" value="<?=$pconfig['force_blocking'];?>" />
					<input name="zfs_datasets" type="hidden" value="<?=$pconfig['zfs_datasets'];?>" />
					<input name="fib" type="hidden" value="<?=$pconfig['fib'];?>" />
					<input name="attach_blocking" type="hidden" value="<?=$pconfig['attach_blocking'];?>" />
      	<?php if (!empty($input_errors)) print_input_errors($input_errors); ?>
        <table width="100%" border="0" cellpadding="6" cellspacing="0">
			<?php html_titleline(gettext("Jail parameters"));?>
        	<?php html_inputbox("jailno", gettext("Jail number"), $pconfig['jailno'], gettext("The jail number determines the order of the jail."), true, 10);?>
			<?php html_inputbox("jailname", gettext("Jail name "), $pconfig['jailname'], gettext("The jail's  name."), true, 15,isset($uuid) && (FALSE !== $cnid) && $name_ro );?>
			<?php html_combobox("jail_type", gettext("Jail Type \n <input type=\"button\" onclick=\"helpbox()\" value=\"Help\" />"), $pconfig['jail_type'], array('slim' =>'Slim','full'=> 'Full', 'linux'=> 'Linux', 'custom'=> 'Custom'), "Choose jail type ", true,isset($uuid) && (FALSE !== $cnid),"type_change()");?>
			
			<?php html_checkbox("enable", gettext("Jail start on boot"),			!empty($pconfig['enable']) ? true : false, gettext("Enable"), "");?>
			<?php html_inputbox("jailpath", gettext("Jail Location"), $pconfig['jailpath'], gettext("Sets an alternate location for the jail. Default is {$config['thebrig']['rootfolder']}{jail_name}/."), false, 40,isset($uuid) && (FALSE !== $cnid) && $path_ro);?>
			<?php html_optionsbox("param", gettext("In jail allow"), $pconfig['param'], $in_jail_allow, false, false); ?>
			
			<tr id='mounts_separator_empty'>	<td colspan='2' class='list' height='6'></td>
			<tr id='mounts_separator'><td colspan='2' valign='top' class='listtopic'>Mounts</td></tr>
				
 			<?php html_checkbox("jail_mount", gettext("mount/umount jail's fs"), $pconfig['jail_mount'], gettext("Enable the jail to automount its fstab file. <b>This is not optional for slim jails.</b> "),false , false, "jail_mount_enable()");?>
 			<?php for ($i = $config['thebrig']['gl_statfs']; $i <= 2; ) { $combovalues[$i] = $i ; $i++; }  
 			
 			html_combobox("statfs", gettext("information about a mounted file system (statfs)"),  $pconfig['statfs'], /*array('2' =>'2','1'=> '1', '0'=> '0')*/ $combovalues , "Choose enforce_statfs. Default value =2. It not allow for jail user mount inside a jail. \"High\" = 1  and \"All\" = 0 values allow mount jail-friendly filesystems  ", false,false);?>
		
 			<?php //html_combobox("devfs_enable", gettext("Enable mount devfs \n <input type=\"button\" onclick=\"helpbox()\" value=\"Help\" />"), $pconfig['devfs_enable'], array('parent' =>'Main devfs','standart'=> 'Standart', 'custom'=> 'With ruleset'), "Choose devfs type", false,false,"devfs_change()");?>
			
			<?php html_checkbox("devfs_enable", gettext("Enable mount devfs"), $pconfig['devfs_enable'], gettext("Use to mount the device file system inside the jail. <br><b>This must be checked if you want 'ps', 'top' or most rc.d scripts to function inside jail.</b>"), "", "", "");?>
	<?php html_brigdevfs_box("rule", gettext("Devfs ruleset"), !empty($pconfig['rule']) ? $pconfig['rule'] : array(), gettext("Define additional rules for current jail."), false);?>
							
			
			<?php //html_inputbox("devfsrules", gettext("Devfs ruleset name"), !empty($pconfig['devfsrules']) ? $pconfig['devfsrules'] : "devfsrules_jail", gettext("You can change standart ruleset"), false, 30);?>
			<?php html_checkbox("proc_enable", gettext("Enable mount procfs"), $pconfig['proc_enable'], "", "<font color=magenta>if this checked, TheBrig will add entry to fstab automatically</color>", " ", " ");?>
			<?php html_checkbox("fdescfs_enable", gettext("Enable mount fdescfs"), $pconfig['fdescfs_enable'], "", "", " ");?>
			<?php html_checkbox("zfs_enable", gettext("Enable mount zfs dataset"), $pconfig['zfs_enable'], "", "", " ");?>
			
			<?php html_separator();?>
			<tr id='mounts_separator0'><td colspan='2' valign='top' class='listtopic'>Networking</td></tr>
			<?php html_checkbox("jail_vnet", gettext("Virtual network"), $pconfig['jail_vnet'], gettext("Enable virtual network stack (vnet)"), "", "","vnet_enable()");?>
			<tr id='epair_tr'>
					<td width='22%' valign='top' class='vncell'><label for='epair'>Epair interface</label></td>
					<td width='78%' class='vtable'>
					
						  <table class="formdata" width="100%" border="0">
							<tr><td width='50%'>Side A (system)</td><td width='50%'>Side B (jail)</td>
							<tr><td width='50%'>
								  <input name='epair_a_ip' type='text' class='formfld' id='homefolder' size='30' value=<?=$pconfig['epair_a_ip']?>  />/
								  <input name='epair_a_mask' type='text' class='formfld' id='homefolder' size='3' value=<?=$pconfig['epair_a_mask']?>  />
								  <br /><span class='vexpl'>System side of interface, eq: 192.168.1.251/24</span>
							    </td>
							    <td width='50%'>
								    <input name='epair_b_ip' type='text' class='formfld' id='homefolder' size='30' value=<?=$pconfig['epair_b_ip']?>  />/
								  <input name='epair_b_mask' type='text' class='formfld' id='homefolder' size='3' value=<?=$pconfig['epair_b_mask']?>  />
								 <br /><span class='vexpl'>Jail side of interface, eq: 192.168.1.252/24</span>
							 </td></tr>
						  </table>
						 

					</td>
				</tr>
			
			<?php //$a_interface = array(get_ifname($config['interfaces']['lan']['if']) => "LAN"); for ($i = 1; isset($config['interfaces']['opt' . $i]); ++$i) { $a_interface[$config['interfaces']['opt' . $i]['if']] = $config['interfaces']['opt' . $i]['descr']; }?>
			<?php //html_combobox("if", gettext("Jail Interface"), $pconfig['if'], $a_interface, gettext("Choose jail interface"), true);?>
			<?php //html_ipv4addrbox("ipaddr", "subnet", gettext("Jail IPv4 address"), $pconfig['ipaddr'], $pconfig['subnet'], "", false);?>
			<?php // html_ipv6addrbox("ipaddr6", "subnet6", gettext("Jail IPv6 address"), $pconfig['ipaddr6'], $pconfig['subnet6'], "", false);?>
			<?php  html_brig_network_box("allowedip",  gettext("Jail Network settings"), $pconfig['allowedip'], "", false, false) ; ?>
				
			<?php html_separator();?>
			<?php html_titleline(gettext("Fstab"));?>
			<?php html_textarea("auxparam", gettext("Fstab"), $pconfig['auxparam'] , sprintf(gettext(" This will be added to fstab.  Format: device &lt;space&gt; mount-point as full path &lt;space&gt; fstype &lt;space&gt; options &lt;space&gt; dumpfreq &lt;space&gt; passno. <a href=http://www.freebsd.org/doc/en_US.ISO8859-1/books/handbook/mount-unmount.html target=\"_blank\">Manual</a> <p> Also you can use fstab editor ")), false, 65, 5, false, false);?>
			<?php html_separator();?>
			<?php html_titleline(gettext("Commands"));?>
			
			<?php html_brig_command_box("cmd",  gettext("Jail  commands"), $pconfig['cmd'], "",  false,  false); ?>
			<?php //html_inputbox("exec_prestart", gettext("Jail prestart command"), $pconfig['exec_prestart'], gettext("NAS4Free command to execute  <b>before</b> starting the jail. May be user's script"), false, 50);?>
			<?php html_inputbox("exec_start", gettext("Jail start command"), $pconfig['exec_start'], gettext("command to execute  when starting the jail. /etc/rc command load rc scripts."), false, 50);?>
			<?php //html_inputbox("afterstart0", gettext("User command 0"), $pconfig['afterstart0'], gettext("command to execute after the one for starting the jail."), false, 50);?>
			<?php //html_inputbox("afterstart1", gettext("User command 1"), $pconfig['afterstart1'], gettext("command to execute after the one for starting the jail."), false, 50);?>
			<?php html_inputbox("exec_stop", gettext("User command stop"), $pconfig['exec_stop'] , gettext("command to execute in jail for stopping. Usually <i>/bin/sh /etc/rc.shutdown</i>, but can defined by user for execute prestop script"), false, 50);?>
			<?php //html_inputbox("extraoptions", gettext("Options. "),  $pconfig['extraoptions'], gettext("Add to rc.conf.local variable jail_jailname_flags. Example: -l -U root -n {jailname}"), false, 40);?>
			<?php //html_inputbox("jail_parameters", gettext("Addition Parameters "),  $pconfig['jail_parameters'], gettext("Add to rc.conf.local variable jail_parameters. Must be separated by space. See <a href=http://www.freebsd.org/cgi/man.cgi?query=jail&sektion=8>jail(8)</a>"), false, 80);?>
			<?php html_inputbox("desc", gettext("Description"), $pconfig['desc'], gettext("You may enter a description here for your reference."), false, 50);?>
			<!-- in edit mode user not have access to extract binaries. I strongly disagree. -->
			<tr id='install_source_empty'><td colspan='2' class='list' height='12'></td></tr>
			<tr id='install_source'><td colspan='2' valign='top' class='listtopic'>Installation Source</td></tr>
			<?php html_combobox("source", gettext("Jail Source"), $pconfig['source'], array('tarballs' =>'From Archive','template'=> 'From Template'), gettext("Choose jail source. Selecting 'From Template' will clone the jail specified by the template folder." ), true, false , "source_change()" );?>
			<?php
			// This obtains a list of files that match the criteria (named anything FreeBSD*)
			// within the /work folder.
			$file_list = thebrig_tarball_list("FreeBSD*");
			// This filelist is then used to generate html code with checkboxes
			$installLib = thebrig_checkbox_list($file_list);
			if ( $installLib ) { 
				html_text("official",_THEBRIG_OFFICIAL_TB, $installLib );		
			} //endif 
			
			// This obtains a list of files that match the criteria (named anything *, excluding FreeBSD)
			// within the /work folder.
			$file_list = thebrig_tarball_list( "*" , array( "FreeBSD"  ) );
			// This filelist is then used to generate html code with checkboxes
			$installLib = thebrig_checkbox_list( $file_list );
			if ( $installLib )  {  
				// If the array exists and has a size, then display that html code
				html_text("homegrown",_THEBRIG_CUSTOM_TB, $installLib);
			} //endif ?>	
				</table>
				<div id="submit">
					<input name="Submit" type="submit" class="formbtn" value="<?=(isset($uuid) && (FALSE !== $cnid)) ? gettext("Save") : gettext("Add")?>" onclick="onsubmit_cmd(); onsubmit_allowedip(); onsubmit_rule(); onsubmit_param();"/>
					<input name="Cancel" type="submit" class="formbtn" value="<?=gettext("Cancel");?>" />
					<input type="button" style = "font-family:Tahoma,Verdana,Arial,Helvetica,sans-serif;font-size: 11px;font-weight:bold;" onclick="redirect()" value="Fstab editor">
					<input name="uuid" type="hidden" value="<?=$pconfig['uuid'];?>" />
					<input name="http_redirect" type="hidden" value="extensions_thebrig.php" />
					
					<?php if ( TRUE == isset( $pconfig['ports'])) { ?>
						<input name="ports" type="hidden" value="<?= true;?>" />
					<?php }?>
					<?php if ( isset($uuid) && (FALSE !== $cnid)) { ?>
					<input name="jail_type" type="hidden" value="<?=$pconfig['jail_type'];?>" />
					
					<?php }?>
				</div>
				<?php include("formend.inc");?>
			</form>
		</td>
	</tr>
</table>
<?php include("fend.inc");?>
