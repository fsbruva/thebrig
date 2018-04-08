<?php
/*
	file: extensions_thebrig_edit.php
	
	  	Copyright 2012-2015 Matthew Kempe & Alexey Kruglov

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

     http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.

	
	*/
require("auth.inc");
require("guiconfig.inc");
//require_once("ext/thebrig/lang.inc");
require_once("ext/thebrig/functions.inc");
require_once("ext/thebrig/gui_addons.inc");
require_once("zfs.inc");

$in_jail_allow = array (
"allow.sysvipc",
"allow.raw_sockets",
"allow.chflags",
"allow.mount",
"allow.mount,tmpfs",
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
else {
	$pglocalheader= <<< EOD
<style type="text/css">
.modal {
    display:    none;
    position:   fixed;
    z-index:    1000;
    top:        0;
    left:       0;
    height:     100%;
    width:      100%;
    background: rgba( 255, 255, 255, .8 ) 
                url('ext/thebrig/ajax-loader.gif') 
                50% 50% 
                no-repeat;
}

/* When the body has the loading class, we turn
   the scrollbar off with overflow:hidden */
body.loading {
    overflow: hidden;   
}

/* Anytime the body has the loading class, our
   modal element will be visible */
body.loading .modal {
    display: block;
}
</style>
'<script type="text/javascript" src="ext/thebrig/spin.min.js"></script>'
EOD;
}

// This determines if the page was arrived at because of an edit (the UUID of the jail)
// was passed to the page) or for a new creation.
if (isset($_GET['uuid'])){ $uuid = $_GET['uuid'];} // Use the existing jail's UUID
	
	
if (isset($_POST['uuid'])) {$uuid = $_POST['uuid']; }// Use the new jail's UUID
	
	

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


// This checks that the $uuid variable is set, and that the 
// attempt to determine the index of the jail config that has the same 
// uuid as the page was entered with is not empty
if (isset($uuid) && (FALSE !== ($cnid = array_search_ex($uuid, $a_jail, "uuid")))) {
	$pconfig['uuid'] = $a_jail[$cnid]['uuid'];
	$pconfig['enable'] = isset($a_jail[$cnid]['enable']);
	$pconfig['jailno'] = $a_jail[$cnid]['jailno'];
	$pconfig['jailname'] = $a_jail[$cnid]['jailname'];
	$pconfig['jail_type'] = $a_jail[$cnid]['jail_type'];
	$pconfig['filesystemschem'] = $a_jail[$cnid]['filesystemschem'];
	$pconfig['zfspool'] = $a_jail[$cnid]['zfspool'];
	$pconfig['compression'] = $a_jail[$cnid]['compression'];
	$pconfig['param'] = $a_jail[$cnid]['param'];
	$pconfig['allowedip'] = $a_jail[$cnid]['allowedip'];  // new entries
	$pconfig['if'] = $a_jail[$cnid]['if'];
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
	$pconfig['exec_start'] = $a_jail[$cnid]['exec_start'];
	$pconfig['cmd'] = $a_jail[$cnid]['cmd'];
	$pconfig['exec_stop'] = $a_jail[$cnid]['exec_stop'];
	$pconfig['desc'] = $a_jail[$cnid]['desc'];
	$pconfig['base_ver'] = $a_jail[$cnid]['base_ver'];
	$pconfig['zfs_dataset'] = explode (";", $a_jail[$cnid]['zfs_datasets']);
	$pconfig['zfs_enable'] =  $a_jail[$cnid]['zfs_enable'];
	if (FALSE == $a_jail[$cnid]['fib']) { unset ($pconfig['fib']);} else {$pconfig['fib'] = $a_jail[$cnid]['fib'];}
	if (FALSE == $a_jail[$cnid]['ports']) { unset ($pconfig['ports']);} else {$pconfig['ports'] = $a_jail[$cnid]['ports'];}
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
	$next_jailnumber = thebrig_get_next_jailnumber();
	$pconfig['uuid'] = uuid();
	$pconfig['enable'] = false;
	$pconfig['jailno'] = $next_jailnumber;
	$pconfig['jailname'] = "jail".$next_jailnumber;
	$pconfig['jail_type']="Slim";
	$pconfig['filesystemschem']="simple";
	$pconfig['param'] = array("allow.mount", "allow.mount.devfs");
	unset ($pconfig['allowedip']);
	unset ($pconfig['jail_vnet']);
	$pconfig['epair_a_ip'] = "192.168.1.251"; 
	$pconfig['epair_a_mask'] = "24"; 
	$pconfig['epair_b_ip'] = "192.168.1.252"; 
	$pconfig['epair_b_mask'] = "24";	
	$pconfig['if'] = "";
	$pconfig['jailpath']="";
	$pconfig['jail_mount'] = true;
	$pconfig['statfs'] = "2";
	$pconfig['devfs_enable'] = true;
	$pconfig['proc_enable'] = true;
	$pconfig['fdescfs_enable'] = false;
	unset ($pconfig['rule'] );
	unset($pconfig['auxparam']);
	unset($pconfig['cmd']);
	$pconfig['exec_start'] = "/bin/sh /etc/rc";
	$pconfig['exec_stop'] = "/bin/sh /etc/rc.shutdown";
	$pconfig['desc'] = "";
	$pconfig['base_ver'] = "Unknown";
	unset ($pconfig['zfs_enable']);
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
	unset ($pconfig['zfs_datasetfiletype']);
	unset ($pconfig['zfs_datasetdata']); 
	// Check is jailname defined
	if (!isset($pconfig['jailname']) || ($pconfig['jailname'] === "")) {
			$input_errors[] = sprintf( gettext("The attribute '%s' is required."), "Jail name");
		}
	/*explode network entries and check IP address.  I check if address, if not more then 1 IP adresses specified, and not more then 1 address in jails set.*/
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
	if (  isset ( $pconfig['jail_mount'] ) ||  isset (  $pconfig['devfs_enable'] ) ||  isset ( $pconfig['proc_enable'] ) ||  isset ( $pconfig['zfs_enable'] )) {
		
		if(is_array($pconfig['param'])) { foreach ($pconfig['param'] as $parameter) {
				unset ( $matches);
				$parameter_1 =  $parameter;
				preg_match ("/allow.mount/", $parameter_1, $matches );
				$matches_1[] = $matches;
				if (1 == $matches[0][1] )  { $cache_param[] =  $parameter_1; unset ($parameter); }
				}
			if ( isset ( $pconfig['zfs_enable'] )) {  $cache_param_1[] = "allow.mount.zfs"; $cache_param_1[] = "allow.mount"; }			
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
	// check zfs mount setting 1. enforce_statfs insert foggoten values
	if ( isset ( $pconfig['zfs_enable'] )) { $config['thebrig']['gl_statfs'] =0; $pconfig['statfs'] =0; } else {}
	// remove possible empty string after edit into form
	if (is_array($pconfig['zfs_dataset'])) $pconfig['zfs_dataset'] = array_filter($pconfig['zfs_dataset']);
	// 3 remove whitespaces on input 
	if (is_array($pconfig['zfs_dataset'])) {
		foreach ($pconfig['zfs_dataset'] as $zfsdataset ) {
			$mountpath1 = explode ("|", $zfsdataset );
			$mountpath1[1] = trim($mountpath1[1]);
			$zfsdataset1[] = $mountpath1[0]."|".$mountpath1[1];
		  // 3 Check is valid characters into path
			$mountpath2 =  trim($mountpath1[1], "/");
			$path_parts =  explode ("/", $mountpath2 );
			foreach ($path_parts as $parts) {
				if (!is_hostname ($parts)) $input_errors[] = sprintf( gettext("The attribute into zfs mount point <i>'%s'</i> contains invalid characters ."), $parts);
				}
		// 4. Check, if defined dataset previously defined for another jail.
		$jaileddataset = $mountpath1[0];
		$jaileddataset = preg_quote($jaileddataset, '/' );
		$pattern = "/".$jaileddataset."/";
		foreach ($config['thebrig']['content'] as $check_jail) {
			$match = preg_match($pattern, $check_jail['zfs_datasets']);
			if ( 1 == $match &&  $check_jail['jailname'] != $pconfig['jailname'] ) {
				$input_errors[] = sprintf( gettext("The selected dataset was previously assigned to another jail. Possible conflict, and therefore assign a different dataset or edit another prison <b>'%s'</b>."),  $check_jail['jailname']);
				}
			}
		// 5. Check for forbidden pathes http://forums.nas4free.org/viewtopic.php?f=66&t=8081
		$forbiddenpathes = array("/", "/bin", "/sbin", "/lib", "/usr", "/usr/bin", "/usr/sbin", "/usr/lib", "/etc" );
		if (false !== array_search ( $mountpath1[1], $forbiddenpathes )) $input_errors[] = sprintf( gettext("The specified mount point <i>'%s'</i> is forbidden. Why? <a href=\"http://forums.nas4free.org/viewtopic.php?f=66&t=8081\">I so want!</a>  Please choose another."),  $mountpath1[1]);
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
	if (false === zfs_is_valid_dataset_name($pconfig['jailname'])) {
			$input_errors[] = sprintf(gettext("The attribute '%s' contains invalid characters."), gettext('Jail name'));
		}
	//Dataset full name construkt as pool/dataset
	$datasetfullname = $pconfig['zfspool'] . "/" . $pconfig['jailname']	;
	
			// Check to make sure there are not any duplicate files selected
	
	if ( true === count_ext( $files_selected, ">", 0 )){
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
	if ( !is_dir( $pconfig['jailpath'] ) && ( isset($input_errors) === FALSE ) ) {
		if (!mkdir( $pconfig['jailpath'], 0774, true)) {
		     $input_errors[] ="Could not create directory for jail to live in!";
		      }
		  }
	
	
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
		$jail['filesystemschem'] = $pconfig['filesystemschem'];
		$jail['zfspool'] = $pconfig['zfspool'];
		$jail['compression'] = $pconfig['compression'];
		$jail['jailpath'] = $pconfig['jailpath'];		
		$jail['allowedip'] = $pconfig['allowedip'];		
		$jail['jail_vnet'] = isset($pconfig['jail_vnet']) ? true : false;
		$jail['epair_a_ip'] = $pconfig['epair_a_ip'];  
		$jail['epair_a_mask'] = $pconfig['epair_a_mask'];  
		$jail['epair_b_ip'] = $pconfig['epair_b_ip']; 
		$jail['epair_b_mask'] = $pconfig['epair_b_mask'];
		$jail['if'] = $pconfig['if'];
		$jail['rule'] = $pconfig['rule'];
		$jail['jail_mount'] = isset($pconfig['jail_mount']) ? true : false;
		$jail['statfs'] = $pconfig['statfs'];
		$jail['devfs_enable'] = isset($pconfig['devfs_enable']) ? true : false;
		$jail['proc_enable'] = isset($pconfig['proc_enable']) ? true : false;	
		$jail['fdescfs_enable'] = isset($pconfig['fdescfs_enable']) ? true : false;
		if (empty($pconfig['fdescfs_enable'])) { unset( $jail['fdescfs_enable']);} else { $jail['fdescfs_enable'] = 'yes';}
		unset($jail['auxparam']);
		foreach (explode("\n", $_POST['auxparam']) as $auxparam) {
			$auxparam = trim($auxparam, "\t\n\r");
			if (!empty($auxparam)) $jail['auxparam'][] = $auxparam;
			else {};
			}
			
		$jail['cmd'] = $pconfig['cmd'];
		$jail['exec_start'] = $pconfig['exec_start'];
		$jail['exec_stop'] = $pconfig['exec_stop'];
		if (empty ($pconfig['extraoptions'])) { $pconfig['extraoptions'] = "-l -U root -n ".$pconfig['jailname'];} else {}
		$jail['desc'] = $pconfig['desc'];
		$jail['base_ver'] = $pconfig['base_ver'];
		// compress array to string
		if (!empty( $zfsdataset1 )) { $jail['zfs_datasets'] = implode(";", $zfsdataset1); } else { unset ($jail['zfs_datasets']);}
		$jail['zfs_enable'] = !empty($pconfig['zfs_enable']) ? true : false;
		$jail['fib'] = $pconfig['fib'];
		$jail['ports'] = isset( $pconfig['ports'] ) ? true : false ;
		// Create zfs dataset, if it defined
		if (FALSE === ($cnid = array_search_ex($uuid, $a_jail, "uuid")) && $jail['filesystemschem'] == "zfs") {
		// Create new jail with zfs schem => Prepare dataset for jail 
		// create dataset
		$result = 0;
		$option = " -o mountpoint=".rtrim($jail['jailpath'], "/")." -o compression=".$jail['compression'];
		$cmd = 'zfs create'. $option . ' '. $datasetfullname;
		write_log($cmd);
		$result |= mwexec($cmd, true);
	if ($result != 0) {
		write_log(sprintf('Error: Failed to create dataset %1$s', $datasetfullname));
		exit;
		} }
		// Populate the jail. The simplest case is a full jail using tarballs.
		
		if ( $pconfig['source'] === "tarballs" && ( count_ext ( $files_selected, ">", 0 )) && $jail['jail_type'] === "full")
			thebrig_split_world($pconfig['jailpath'] , false , $files_selected );
		
		elseif ( $pconfig['source'] === "template" && $jail['jail_type'] === "full" )
			thebrig_split_world($pconfig['jailpath'] , false);
		// Next simplest is to split the world if we're making a slim jail out of tarballs.
		elseif ( $jail['jail_type'] === "slim" ) {
			// We know we're making a slim jail now
			$config['thebrig']['basejail']['base_ver'] = $pconfig['base_ver'];
			
			if ( $pconfig['source'] === "tarballs" && count_ext ( $files_selected, ">", 0 ) )
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
// function count sanitized, example ===> count ( $files_selected ) > 0 )
function count_ext($array, $sign, $act) {
	//global $config;
	unset($result);
	if ( isset ($array) && is_array($array)) {
		switch ($sign) {
				case "<":
						$result = (count ( $array ) < $act);
						break;
				case "==":
						$result= (count ( $array ) == $act);
						break;
				case ">":
						$result = (count ( $array ) > $act);
						break;
		}	
		
	} 	
return $result;
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

$l_compressionmode = [
	'on' => 'On',
	'off' => 'Off',
	'lz4' => 'lz4',
	'lzjb' => 'lzjb',
	'gzip' => 'gzip',
	'gzip-1' => 'gzip-1',
	'gzip-2' => 'gzip-2',
	'gzip-3' => 'gzip-3',
	'gzip-4' => 'gzip-4',
	'gzip-5' => 'gzip-5',
	'gzip-6' => 'gzip-6',
	'gzip-7' => 'gzip-7',
	'gzip-8' => 'gzip-8',
	'gzip-9' => 'gzip-9',
	'zle' => 'zle'
];
?>
<?php include("fbegin.inc");?>
<script type="text/javascript">//<![CDATA[
function submitted() {
	var tarball_count=0;
	$("input[name='formFiles[]']:checked").each( function(){
		tarball_count++;  // count the selected tarballs
	});
	if ( tarball_count == 0 || 
	(tarball_count > 0 && confirm ("Your jail will now be built/updated. Please be patient, and do not navigate away from this page or close your browser. This may take up to 2 minutes.")) ) {
		$body = $("body");
		$body.addClass("loading");
		onsubmit_cmd(); 
		onsubmit_allowedip(); 
		onsubmit_rule(); 
		onsubmit_param(); 
		if ( $('#zfs_enable').is(":visible") == true ) {
			onsubmit_zfs();
		}	
		return true;
	}
	else { return false; }
}
$(document).ready(function(){
	var gui = new GUI;
	$('#jail_type').change(function() {
		switch ($('#jail_type').val()) {
	case "slim":
		$('#jail_mount_tr').hide();
		break;
	case "full":	
		$('#jail_mount_tr').show();
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
$('#jail_vnet').change(function() {
		switch ($('#jail_vnet').is(':checked')) {
		case false :
			$('#allowedip_tr').show(1500);
			$('#epair_tr').hide(300);
			$('#exec_start_tr').show(300);
			$('#if_tr').hide(300);
			break;
			
		case true :	
			$('#allowedip_tr').hide(300);
			$('#epair_tr').show(1500);
			$('#exec_start_tr').hide(1500);
			$('#if_tr').show(1500);
			break;
            } 
        });
$('#zfs_enable').change(function() {
	switch ($('#zfs_enable').is(':checked')) {
	case false :
		$('#zfs_dataset_tr').hide();
		break;
	case true :
		$('#zfs_dataset_tr').show();
		break;
		}
	});
$('#moreless').click(function (){
		switch ($('#moreless').val()) {
			case "More":
				$('#moreless').val("Less");
				$('#jail_mount_tr').show();
				$('#param_tr').show();
				$('#statfs_tr').show();
				$('#devfs_enable_tr').show();
				$('#rule_tr').show();
				$('#proc_enable_tr').show();
				$('#fdescfs_enable_tr').show();
				$('#auxparam_tr').show();
				$('#cmd_tr').show();
				$('#mounts_separator').show();
				$('#zfs_enable_tr').show();
			break;
			case "Less":
			default:
				$('#moreless').val("More");
				$('#jail_mount_tr').hide();
				$('#param_tr').hide();
				$('#statfs_tr').hide();
				$('#devfs_enable_tr').hide();
				$('#rule_tr').hide();
				$('#proc_enable_tr').hide();
				$('#fdescfs_enable_tr').hide();
				$('#auxparam_tr').hide();
				$('#cmd_tr').hide();
				$('#mounts_separator').hide();
				$('#zfs_enable_tr').hide();
			break;
		}
});
$('#filesystemschem').change(function(){
	switch ($('#filesystemschem').val()) {
	case "simple":
			$('#filesystemschem1_tr').hide();	
			$('#zfspool_tr').hide();
			$('#compression_tr').hide();
		break;
	case "zfs":
		$('#filesystemschem1_tr').hide();	
		$('#zfspool_tr').show();
		$('#compression_tr').show();
		}
});
$('#moreless').click();
$('#zfs_enable').change();
$('#jail_type').change();
$('#source').change();
$('#jail_vnet').change();
$('#filesystemschem').change();
$('#moreless').click();

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
function helpbox() { alert("Slim - This is a fully functional jail, but when first installed, only occupies about 2 MB in its folder.\n\n full - This is a full sized jail, about 300 MB per jail, and is completely self contained."); }
function redirect() { window.location = "extensions_thebrig_fstab.php?uuid=<?=$pconfig['uuid'];?>&act=editor" }
//]]>
</script>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr><td class="tabnavtbl">
		<ul id="tabnav">
			<li class="tabact"><a href="extensions_thebrig.php"><span><?=_THEBRIG_JAILS;?></span></a></li>
			
			<li class="tabinact"><a href="extensions_thebrig_config.php"><span><?=_THEBRIG_MAINTENANCE;?></span></a></li>
		</ul>
	</td></tr>
		<td class="tabcont">
    <form action="extensions_thebrig_edit.php" method="post" name="iform" id="iform">  
    <!--     <form action="test.php" method="post" name="iform" id="iform">-->
      <input name="jailpath" type="hidden" value="<?=$pconfig['jailpath'];?>" />
					<input name="base_ver" type="hidden" value="<?=$pconfig['base_ver'];?>" />
					<input name="fib" type="hidden" value="<?=$pconfig['fib'];?>" />
      	<?php if (!empty($input_errors)) print_input_errors($input_errors); ?>
        <table width="100%" border="0" cellpadding="6" cellspacing="0">
			<?php html_titleline(_THEBRIG_JAIL_PARAMETERS);?>
        	<?php html_inputbox("jailno", _THEBRIG_JAIL_NUMBER, $pconfig['jailno'], _THEBRIG_JAIL_NUMBER_EXPL,true, 10, true);?>
			<?php html_inputbox("jailname", _THEBRIG_TABLE1_TITLE1, $pconfig['jailname'], _THEBRIG_TABLE1_TITLE1_EXPL, true, 15,isset($uuid) && (FALSE !== $cnid) && $name_ro );?>
			<?php html_combobox("jail_type", _THEBRIG_JAIL_TYPE, $pconfig['jail_type'], array('full'=> 'Full','slim' =>'Slim'), _THEBRIG_JAIL_TYPE_EXPL, true,isset($uuid) && (FALSE !== $cnid),"type_change()");
			html_combobox("filesystemschem", _THEBRIG_TABLE1_TITLE_ZFS, $pconfig['filesystemschem'], array("simple" => "Simple folder","zfs"=>"zfs dataset for this"), _THEBRIG_TABLE1_TITLEZFS_EXPL, true,isset($uuid) && (FALSE !== $cnid) && $name_ro,"");
			if (FALSE !== ($datasets_list1 = brig_datasets_list())) {
				foreach ( $datasets_list1 as $b_dataset) {$c_dataset[] = $b_dataset[1];}
			
			}
			$a_pools = zfs_get_pool_list();?>
			<?php foreach ( $a_pools as $a_pool_n => $a_pool_val) {$poolname[$a_pool_val['name']] = $a_pool_val['name'];} ?>
	
			<?php html_combobox("zfspool", _THEBRIG_J_POOL, $pconfig['zfspool'], $poolname, _THEBRIG_J_POOL_EXPL, false,isset($uuid) && (FALSE !== $cnid) && $name_ro,"");	?>
			<?php html_combobox2('compression', _THEBRIG_DATASET_COMPRESS, $pconfig['compression'], $l_compressionmode, _THEBRIG_DATASET_COMPRESS_EXPL,	false,isset($uuid) && (FALSE !== $cnid) && $name_ro); ?>

			<?php html_checkbox("enable", _THEBRIG_TABLE1_TITLE3,			!empty($pconfig['enable']) ? true : false, _THEBRIG_TABLE1_TITLE3_EXPL, "");?>
			<?php html_inputbox("jailpath", _THEBRIG_ONLINETABLE_TITLE4, $pconfig['jailpath'], _THEBRIG_ONLINETABLE_TITLE4_EXPL, false, 40,isset($uuid) && (FALSE !== $cnid) && $path_ro);?>
			<?php html_optionsbox("param", _THEBRIG_J_ALLOW , $pconfig['param'], $in_jail_allow, false, false); ?>
			
			<tr id='mounts_separator_empty'>	<td colspan='2' class='list' height='6'></td>
			<tr id='mounts_separator'><td colspan='2' valign='top' class='listtopic'>Mounts</td></tr>
				
 			<?php html_checkbox("jail_mount", _THEBRIG_J_MOUNTFSTAB, $pconfig['jail_mount'], _THEBRIG_J_MOUNTFSTAB_EXPL,false, false, "jail_mount_enable()");?>
 			<?php for ($i = $config['thebrig']['gl_statfs']; $i <= 2; ) { $combovalues[$i] = $i ; $i++; }  
 			
 			html_combobox("statfs", _THEBRIG_J_STATFS, $pconfig['statfs'], $combovalues , _THEBRIG_J_STATFS_EXPL, false,false);?>
		
 						
			<?php html_checkbox("devfs_enable", _THEBRIG_J_DEVFS, $pconfig['devfs_enable'], _THEBRIG_J_DEVFS_EXPL, "", "", "");?>
	<?php html_brigdevfs_box("rule", _THEBRIG_J_DEVFSRULES, !empty($pconfig['rule']) ? $pconfig['rule'] : array(), _THEBRIG_J_DEVFSRULES_EXPL, false);?>
							
			
						<?php html_checkbox("proc_enable", _THEBRIG_J_PROCFS, $pconfig['proc_enable'], "", _THEBRIG_J_PROCFS_EXPL, " ", " ");?>
			<?php html_checkbox("fdescfs_enable", _THEBRIG_J_FDESCFS, $pconfig['fdescfs_enable'], "", _THEBRIG_J_FDESCFS_NOTE, " ");?>
			<?php if (FALSE !== ($datasets_list = brig_datasets_list())) {
				html_checkbox("zfs_enable", _THEBRIG_J_ZFS, isset($pconfig['zfs_enable']) ? true : false, "", "", " ");
				html_zfs_box("zfs_dataset", _THEBRIG_J_ZFS_MOUNTED, $pconfig['zfs_dataset'], $datasets_list, false, false); 
			} else { /*echo " <input name='zfs_enable' type='hidden' value='' />";*/}
			?>
			<?php html_textarea("auxparam", _THEBRIG_J_FSTAB, $pconfig['auxparam'] , _THEBRIG_J_FSTAB_EXPL, false, 65, 5, false, false);?>

			<?php html_separator();?>
			<tr id='mounts_separator0'><td colspan='2' valign='top' class='listtopic'>Networking</td></tr>
			<?php if ($g['arch'] == 'x64') {
			html_checkbox("jail_vnet", _THEBRIG_J_VNET, $pconfig['jail_vnet'], _THEBRIG_J_VNET_EXPL
, "", ""," ");?>
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
						 
						<span class='vexpl'><?="All scripts TheBrig create automatically"?></span>
					</td>
				</tr>
						
			<?php $a_interface = array(get_ifname($config['interfaces']['lan']['if']) => "LAN"); 
			for ($i = 1; isset($config['interfaces']['opt' . $i]); ++$i) { 
			$a_interface[$config['interfaces']['opt' . $i]['if']] = $config['interfaces']['opt' . $i]['descr']; }?>
			<?php html_combobox("if", _THEBRIG_VNET_LAN, $pconfig['if'], $a_interface, _THEBRIG_VNET_LAN_EXPL, true);?>
			<?php } ?>
			<?php  html_brig_network_box("allowedip",  _THEBRIG_J_NETWORK, $pconfig['allowedip'], "May be multiple IPs and LANs", false, false) ; ?>
				
			<?php html_separator();?>
			<?php html_titleline(_THEBRIG_J_COMMAND);?>
			
			<?php html_brig_command_box("cmd",  _THEBRIG_J_COMMANDS, $pconfig['cmd'], "User can define commands for execute during  start/stop jail.  May be script.",  false,  false); ?>
			<?php html_inputbox("exec_start", _THEBRIG_J_STARTCOMMAND, $pconfig['exec_start'], _THEBRIG_J_STARTCOMMAND_EXPL, false, 50);?>
			<?php html_inputbox("exec_stop", _THEBRIG_J_STOPCOMMAND, $pconfig['exec_stop'] ,_THEBRIG_J_STOPCOMMAND_EXPL, false, 50);?>
			<?php html_inputbox("desc", _THEBRIG_J_DESCRIPTION, $pconfig['desc'], _THEBRIG_J_DESCRIPTION_EXPL, false, 50);?>
			<!-- in edit mode user not have access to extract binaries. I strongly disagree. -->
			<tr id='install_source_empty'><td colspan='2' class='list' height='12'></td></tr>
			<tr id='install_source'><td colspan='2' valign='top' class='listtopic'><?=_THEBRIG_J_SRC_TITLE;?></td></tr>
			<?php html_combobox("source", _THEBRIG_J_SRC, $pconfig['source'], array('tarballs' =>'From Archive','template'=> 'From Template'), _THEBRIG_SRC_EXPL, true, false , "source_change()" );?>
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
					<input type="button" style = "font-family:Tahoma,Verdana,Arial,Helvetica,sans-serif;font-size: 11px;font-weight:bold;" value="More" id="moreless" />
					<p><p>
					<input name="Submit" type="submit" class="formbtn" value="<?=(isset($uuid) && (FALSE !== $cnid)) ? _THEBRIG_SAVE_BUTTON : _THEBRIG_ADD_BUTTON?>" onclick="return submitted();"/>
					<input name="Cancel" type="submit" class="formbtn" value="<?=_THEBRIG_CANCEL_BUTTON;?>" />
					<input type="button" style = "font-family:Tahoma,Verdana,Arial,Helvetica,sans-serif;font-size: 11px;font-weight:bold;" onclick="redirect()" value="<?=_THEBRIG_FSTAB_BUTTON;?>"/>
					<input name="uuid" type="hidden" value="<?=$pconfig['uuid'];?>" />
					<input name="http_redirect" type="hidden" value="extensions_thebrig.php" />
					
					<?php if ( TRUE === isset( $pconfig['ports'])) { ?>
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
<div class="modal"><!-- This is for blocking page when user clicks add/save --></div>
