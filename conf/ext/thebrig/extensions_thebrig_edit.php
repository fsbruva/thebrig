<?php
/*
 * extensions_thebrig_edit.php
	*/
require("auth.inc");
require("guiconfig.inc");
require_once("ext/thebrig/lang.inc");
require_once("ext/thebrig/functions.inc");
// I'm sorry, but I want next line commented.  I create page trap.php for trap _POST _GET messages, for testing my code.  
//  include_once ("ext/thebrig/trap.php");
if (is_file("/tmp/tempjail")){unlink ("/tmp/tempjail");}
//I check install.
if ( !isset( $config['thebrig']['rootfolder']) || !is_dir( $config['thebrig']['rootfolder']."work" )) {
	$input_errors[] = _THEBRIG_NOT_CONFIRMED;
} // end of elseif

// This determines if the page was arrived at because of an edit (the UUID of the jail)
// was passed to the page) or for a new creation.
if (isset($_GET['uuid']))
	// Use the existing jail's UUID
	$uuid = $_GET['uuid'];
if (isset($_POST['uuid']))
	// Use the new jail's UUID
	$uuid = $_POST['uuid'];

// Page title
$pgtitle = array(_THEBRIG_TITLE, _THEBRIG_JAIL, isset($uuid) ? _THEBRIG_EDIT : _THEBRIG_ADD );
$snid = "jail60"; // what is this for?

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

$a_interface = array(get_ifname($config['interfaces']['lan']['if']) => "LAN"); for ($i = 1; isset($config['interfaces']['opt' . $i]); ++$i) { $a_interface[$config['interfaces']['opt' . $i]['if']] = $config['interfaces']['opt' . $i]['descr']; }

//input_errors[] = implode ( " | " , array_keys ( $a_interface ));
//$input_errors[] = implode( " | " , $a_interface);
// This checks that the $uuid variable is set, and that the 
// attempt to determine the index of the jail config that has the same 
// uuid as the page was entered with is not empty
if (isset($uuid) && (FALSE !== ($cnid = array_search_ex($uuid, $a_jail, "uuid")))) {
	$pconfig['uuid'] = $a_jail[$cnid]['uuid'];
	$pconfig['enable'] = isset($a_jail[$cnid]['enable']);
	$pconfig['jailno'] = $a_jail[$cnid]['jailno'];
	$pconfig['jailname'] = $a_jail[$cnid]['jailname'];
	$pconfig['type'] = $a_jail[$cnid]['type'];
	$pconfig['if'] = $a_jail[$cnid]['if'];
	$pconfig['ipaddr'] = $a_jail[$cnid]['ipaddr'];
	$pconfig['subnet'] = $a_jail[$cnid]['subnet'];
	$pconfig['jailpath'] = $a_jail[$cnid]['jailpath'];
	$pconfig['jail_mount'] = isset($a_jail[$cnid]['jail_mount']);
	$pconfig['devfs_enable'] = isset($a_jail[$cnid]['devfs_enable']);
	$pconfig['proc_enable'] = isset($a_jail[$cnid]['proc_enable']);
	$pconfig['fdescfs_enable'] = isset($a_jail[$cnid]['fdescfs_enable']);
	// $pconfig['devfsrules'] = $a_jail[$cnid]['devfsrules'];
	unset ($pconfig['auxparam']);
	if (isset($a_jail[$cnid]['auxparam']) && is_array($a_jail[$cnid]['auxparam']))
		$pconfig['auxparam'] = implode("\n", $a_jail[$cnid]['auxparam']);
	$pconfig['exec_start'] = $a_jail[$cnid]['exec_start'];
	$pconfig['afterstart0'] = $a_jail[$cnid]['afterstart0'];
	$pconfig['afterstart1'] = $a_jail[$cnid]['afterstart1'];
	$pconfig['exec_stop'] = $a_jail[$cnid]['exec_stop'];
	$pconfig['extraoptions'] = $a_jail[$cnid]['extraoptions'];
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
	$pconfig['fib'] = $a_jail[$cnid]['fib'];
	$pconfig['ports'] = ( isset($a_jail[$cnid]['ports']) ) ? true : false ;
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
	$pconfig['type']="Slim";
	$pconfig['if'] = "";
	$pconfig['ipaddr'] = "";
	$pconfig['subnet'] = "32";
	$pconfig['jailpath']="";
	$pconfig['jail_mount'] = true;
	$pconfig['devfs_enable'] = true;
	$pconfig['proc_enable'] = false;
	$pconfig['fdescfs_enable'] = false;
	// unset ($pconfig['devfsrules'] );
	unset($pconfig['auxparam']);
	$pconfig['exec_start'] = "/bin/sh /etc/rc";
	$pconfig['afterstart0'] = "";
	$pconfig['afterstart1'] = "";
	$pconfig['exec_stop'] = "";
	$pconfig['extraoptions'] = "";
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
	$pconfig['fib'] = false;
	$pconfig['ports'] = false;
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
	$files_selected = $_POST['formFiles'];
	// Input validation.
	$reqdfields = explode(" ", "jailno jailname ipaddr");
	$reqdfieldsn = array(gettext("Jail Number"), gettext("Jail Name"), gettext("Jail IP Address") );
	$reqdfieldst = explode(" ", "numericint hostname ipaddr");
	
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
		
	// Check to see if duplicate ip addresses:
	$index = array_search_ex($pconfig['ipaddr'], $a_jail, "ipaddr");
	if ( FALSE !== $index ) {
		// If $index is not null, then there is a name conflict
		if (!(isset($uuid) && (FALSE !== $cnid )))
			// This means we are not editing an existing jail - we are creating a new one
			$input_errors[] = "The specified ip address is already in use. Please choose another.";
	}
	
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
		mwexec ("/bin/mkdir {$pconfig['jailpath']}");
	}
	
	// This is a second test to see if the directory was created properly.
	if ( !is_dir( $pconfig['jailpath'] )){
		$input_errors[] = "Could not create directory for jail to live in!";
	}
		
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
		$jail['type'] = $pconfig['type'];
		$jail['if'] = $pconfig['if'];
		$jail['ipaddr'] = $pconfig['ipaddr'];
		$jail['subnet'] = $pconfig['subnet'];
		$jail['jailpath'] = $pconfig['jailpath'];
		switch ($pconfig['type']) {
			case "slim":
				$jail['jail_mount'] =  true ;
				$jail['devfs_enable'] = isset($pconfig['devfs_enable']) ? true : false;
				$jail['proc_enable'] = isset($pconfig['proc_enable']) ? true : false;
				$jail['fdescfs_enable'] = isset($pconfig['fdescfs_enable']) ? true : false;
				$jail['ports'] = ( isset($pconfig['ports']) ) ? true : false ;
				break;
			case "full":
				$jail['jail_mount'] = isset($pconfig['jail_mount']) ? true : false;
				$jail['devfs_enable'] = isset($pconfig['devfs_enable']) ? true : false;
				$jail['proc_enable'] = isset($pconfig['proc_enable']) ? true : false;
				$jail['fdescfs_enable'] = isset($pconfig['fdescfs_enable']) ? true : false;
				$jail['ports'] = ( isset($pconfig['ports']) ) ? true : false ;
				break;
			case "linux":
				$jail['jail_mount'] =  true ;
				$jail['devfs_enable'] =  true ;
				$jail['proc_enable'] =  true ;
				$jail['fdescfs_enable'] =  true ;
				$jail['ports'] = false ;
				break;
			case "custom":
				$jail['jail_mount'] = isset($pconfig['jail_mount']) ? true : false;
				$jail['devfs_enable'] = isset($pconfig['devfs_enable']) ? true : false;
				$jail['proc_enable'] = isset($pconfig['proc_enable']) ? true : false;
				$jail['fdescfs_enable'] = isset($pconfig['fdescfs_enable']) ? true : false;
				$jail['ports'] = ( isset($pconfig['ports']) ) ? true : false ;
				break;
	}
		unset($jail['auxparam']);
		foreach (explode("\n", $_POST['auxparam']) as $auxparam) {
			$auxparam = trim($auxparam, "\t\n\r");
			if (!empty($auxparam)) $jail['auxparam'][] = $auxparam;
			}
		$jail['exec_start'] = $pconfig['exec_start'];
		$jail['afterstart0'] = $pconfig['afterstart0'];
		$jail['afterstart1'] = $pconfig['afterstart1'];
		$jail['exec_stop'] = $pconfig['exec_stop'];
		$jail['extraoptions'] = $pconfig['extraoptions'];
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
			
		// Populate the jail. The simplest case is a full jail using tarballs.
		if ( $pconfig['source'] === "tarballs" && count ( $files_selected ) > 0 && strcmp ( $jail['type'], "full") == 0)
			thebrig_split_world($pconfig['jailpath'] , false , $files_selected );
		elseif ( $pconfig['source'] === "template" && strcmp ( $jail['type'], "full") == 0 )
			thebrig_split_world($pconfig['jailpath'] , false);
		// Next simplest is to split the world if we're making a slim jail out of tarballs.
		elseif ( $jail['type'] === "slim" ) {
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
?>
<?php include("fbegin.inc");?>
<script type="text/javascript">
<!--
$(document).ready(function () {
	source_change();
	type_change();
});

function type_change(){
	var x=document.iform.jail_type.selectedIndex;
	var y=document.iform.jail_type.options;
	document.iform.type.value = y[x].value;
	switch (x) {
	case 0:
		showElementById('mounts_separator_empty','show');
		showElementById('mounts_separator','show');
		showElementById('jail_mount_tr','hide');
		showElementById('devfs_enable_tr','show');
		showElementById('proc_enable_tr','show');
		showElementById('fdescfs_enable_tr','show');
		showElementById('install_source_empty','show');
		showElementById('install_source','show');
		showElementById('source_tr','show');
		showElementById('official_tr','show');
		break;
	case 1:	
		showElementById('mounts_separator_empty','show');
		showElementById('mounts_separator','show');
		showElementById('jail_mount_tr','show');
		showElementById('devfs_enable_tr','show');
		showElementById('proc_enable_tr','show');
		showElementById('fdescfs_enable_tr','show');
		showElementById('install_source_empty','show');
		showElementById('install_source','show');
		showElementById('source_tr','show');
		showElementById('official_tr','show');
		break;
	case 2:	
		showElementById('mounts_separator_empty','hide');
		showElementById('mounts_separator','hide');
		showElementById('jail_mount_tr','hide');
		showElementById('devfs_enable_tr','hide');
		showElementById('proc_enable_tr','hide');
		showElementById('fdescfs_enable_tr','hide');
		showElementById('install_source_empty','hide');
		showElementById('install_source','hide');
		showElementById('source_tr','hide');
		showElementById('official_tr','hide');
		
		break;	
	    
	case 3:	
		showElementById('mounts_separator_empty','show');
		showElementById('mounts_separator','show');
		showElementById('jail_mount_tr','show');
		showElementById('devfs_enable_tr','show');
		showElementById('proc_enable_tr','show');
		showElementById('fdescfs_enable_tr','show');
		showElementById('install_source_empty','hide');
		showElementById('install_source','hide');
		showElementById('source_tr','hide');
		showElementById('official_tr','hide');


		break;

	}
}
function source_change() {
	switch (document.iform.source.selectedIndex) {
		case 0:
			showElementById('official_tr','show');
			showElementById('homegrown_tr','show');
			break;
		case 1:
			showElementById('official_tr','hide');
			showElementById('homegrown_tr','hide');
			break;
	}
}
function helpbox()
{
alert("Slim - This is a fully functional jail, but when first installed, only occupies about 2 MB in its folder.\n\n full - This is a full sized jail, about 300 MB per jail, and is completely self contained.\n\n Linux - jail for Linux, such Debian.\n\n custom- this only create jail folder and make simulation without install. Usefull for migrate jails." );
}
function redirect() {
	window.location = "extensions_thebrig_fstab.php?uuid=<?=$pconfig['uuid'];?>&act=editor"

}
// -->
</script>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr><td class="tabnavtbl">
		<ul id="tabnav">
			<li class="tabact">
				<a href="extensions_thebrig.php"><span><?=_THEBRIG_JAILS;?></span></a>
			</li>
			<li class="tabinact">
				<a href="extensions_thebrig_config.php"><span><?=_THEBRIG_MAINTENANCE;?></span></a>
			</li>
		</ul>
	</td></tr>
		<td class="tabcont">
      <form action="extensions_thebrig_edit.php" method="post" name="iform" id="iform">
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
			<?php html_combobox("jail_type", gettext("Jail Type \n <input type=\"button\" onclick=\"helpbox()\" value=\"Help\" />"), $pconfig['type'], array('slim' =>'Slim','full'=> 'Full', 'linux'=> 'Linux', 'custom'=> 'Custom'), "Choose jail type ", true,isset($uuid) && (FALSE !== $cnid),"type_change()");?>
			<?php $a_interface = array(get_ifname($config['interfaces']['lan']['if']) => "LAN"); for ($i = 1; isset($config['interfaces']['opt' . $i]); ++$i) { $a_interface[$config['interfaces']['opt' . $i]['if']] = $config['interfaces']['opt' . $i]['descr']; }?>
			<?php html_combobox("if", gettext("Jail Interface"), $pconfig['if'], $a_interface, gettext("Choose jail interface"), true);?>
			<?php html_ipv4addrbox("ipaddr", "subnet", gettext("Jail IP address"), $pconfig['ipaddr'], $pconfig['subnet'], "", true);?>
			<?php html_checkbox("enable", gettext("Jail start on boot"),			!empty($pconfig['enable']) ? true : false, gettext("Enable"), "");?>
			<?php html_inputbox("jailpath", gettext("Jail Location"), $pconfig['jailpath'], gettext("Sets an alternate location for the jail. Default is {$config['thebrig']['rootfolder']}{jail_name}/."), false, 40,isset($uuid) && (FALSE !== $cnid) && $path_ro);?>
			<?php html_separator();?>
			<tr id='mounts_separator_empty'>	<td colspan='2' class='list' height='12'></td>
			<tr id='mounts_separator'><td colspan='2' valign='top' class='listtopic'>Mounts</td></tr>
			
 			<?php html_checkbox("jail_mount", gettext("mount/umount jail's fs"), !empty($pconfig['jail_mount']) ? true : false, gettext("Enable the jail to automount its fstab file. ")," " ," ", "");?>
			<?php html_checkbox("devfs_enable", gettext("Enable mount devfs"), !empty($pconfig['devfs_enable']) ? true : false, gettext("Use to mount the device file system inside the jail. <br>"), "", false);?>
			<?php /*html_inputbox("devfsrules", gettext("Devfs ruleset name"), !empty($pconfig['devfsrules']) ? $pconfig['devfsrules'] : "devfsrules_jail", gettext("You can change standart ruleset"), false, 30);*/?>
			<?php html_checkbox("proc_enable", gettext("Enable mount procfs"), !empty($pconfig['proc_enable']) ? true : false, "This must be checked if you want 'ps', 'top' or most rc.d scripts to function inside jail.", "", false);?>
			<?php html_checkbox("fdescfs_enable", gettext("Enable mount fdescfs"), !empty($pconfig['fdescfs_enable']) ? true : false, "The file-descriptor file system, or <a href=http://www.freebsd.org/cgi/man.cgi?query=fdescfs&sektion=5>fdescfs</a>, provides access to the perprocess file descriptor namespace in the global file system namespace.", "", false);?>
			<?php html_separator();?>
			<?php html_titleline(gettext("Fstab"));?>
			<?php html_textarea("auxparam", gettext("Fstab"), $pconfig['auxparam'] , sprintf(gettext(" This will be added to fstab.  Format: device &lt;space&gt; mount-point as full path &lt;space&gt; fstype &lt;space&gt; options &lt;space&gt; dumpfreq &lt;space&gt; passno. <a href=http://www.freebsd.org/doc/en_US.ISO8859-1/books/handbook/mount-unmount.html target=\"_blank\">Manual</a> <p> Also you can use fstab editor ")), false, 65, 5, false, false);?>
			<?php html_separator();?>
			<?php html_titleline(gettext("Commands"));?>
			<?php html_inputbox("exec_start", gettext("Jail start command"), $pconfig['exec_start'], gettext("command to execute  for starting the jail."), false, 50);?>
			<?php html_inputbox("afterstart0", gettext("User command 0"), $pconfig['afterstart0'], gettext("command to execute after the one for starting the jail."), false, 50);?>
			<?php html_inputbox("afterstart1", gettext("User command 1"), $pconfig['afterstart1'], gettext("command to execute after the one for starting the jail."), false, 50);?>
			<?php html_inputbox("exec_stop", gettext("User command stop"), !empty($pconfig['exec_stop']) ? $pconfig['exec_stop'] : "/bin/sh /etc/rc.shutdown" , gettext("command to execute in jail for stopping. Usually <i>/bin/sh /etc/rc.shutdown</i>, but can defined by user for execute prestop script"), false, 50);?>
			<?php html_inputbox("extraoptions", gettext("Options. "),  $pconfig['extraoptions'], gettext("Add to rc.conf.local variable jail_jailname_flags. Example: -l -U root -n {jailname}"), false, 40);?>
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
					<input name="Submit" type="submit" class="formbtn" value="<?=(isset($uuid) && (FALSE !== $cnid)) ? gettext("Save") : gettext("Add")?>" />
					<input name="Cancel" type="submit" class="formbtn" value="<?=gettext("Cancel");?>" />
					<input type="button" style = "font-family:Tahoma,Verdana,Arial,Helvetica,sans-serif;font-size: 11px;font-weight:bold;" onclick="redirect()" value="Fstab editor">
					<input name="uuid" type="hidden" value="<?=$pconfig['uuid'];?>" />
					<input name="type" type="hidden" value="<?=$pconfig['type'];?>" />
				</div>
				
				
				<?php include("formend.inc");?>
			</form>
		</td>
	</tr>
</table>
<?php include("fend.inc");?>
