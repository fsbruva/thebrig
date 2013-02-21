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

//I check install.
if ( !is_dir ( $config['thebrig']['rootfolder']."/work") ) { 
	$input_errors[] = _THEBRIG_NOT_CONFIRMED;  // May be replace previos if ???
	}
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

// This checks that the $uuid variable is set, and that the 
// attempt to determine the index of the jail config that has the same 
// uuid as the page was entered with is not empty
if (isset($uuid) && (FALSE !== ($cnid = array_search_ex($uuid, $a_jail, "uuid")))) {
	$pconfig['uuid'] = $a_jail[$cnid]['uuid'];
	$pconfig['enable'] = isset($a_jail[$cnid]['enable']);
	$pconfig['jailno'] = $a_jail[$cnid]['jailno'];
	$pconfig['jailname'] = $a_jail[$cnid]['jailname'];
	$pconfig['if'] = $a_jail[$cnid]['if'];
	$pconfig['ipaddr'] = $a_jail[$cnid]['ipaddr'];
	$pconfig['subnet'] = $a_jail[$cnid]['subnet'];
	$pconfig['jailpath'] = $a_jail[$cnid]['jailpath'];
	$pconfig['jail_mount'] = isset($a_jail[$cnid]['jail_mount']);
	$pconfig['devfs_enable'] = isset($a_jail[$cnid]['devfs_enable']);
	$pconfig['proc_enable'] = isset($a_jail[$cnid]['proc_enable']);
	$pconfig['fdescfs_enable'] = isset($a_jail[$cnid]['fdescfs_enable']);
	$pconfig['devfsrules'] = $a_jail[$cnid]['devfsrules'];
	$pconfig['fstab'] = $a_jail[$cnid]['fstab'];
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
}
// In this case, the $uuid isn't set (this is a new jail), so set some default values
else {
	$pconfig['uuid'] = uuid();
	$pconfig['enable'] = false;
	$pconfig['jailno'] = thebrig_get_next_jailnumber();
	$pconfig['jailname'] = "";
	$pconfig['if'] = "";
	$pconfig['ipaddr'] = "";
	$pconfig['subnet'] = "32";
	$pconfig['jailpath']="";
	$pconfig['jail_mount'] = false;
	$pconfig['devfs_enable'] = false;
	$pconfig['proc_enable'] = false;
	$pconfig['fdescfs_enable'] = false;
	$pconfig['devfsrules'] = "";
	$pconfig['fstab'] = "";
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
	$pconfig['fib'] = "";
}


if ($_POST) {
	unset($input_errors);
	$pconfig = $_POST;
	$files_selected = $pconfig['formFiles'];

	if (isset($_POST['Cancel']) && $_POST['Cancel']) {
		header("Location: extensions_thebrig.php");
		exit;
	}

	// Input validation.
	$reqdfields = explode(" ", "jailno jailname ipaddr");
	$reqdfieldsn = array(gettext("Jail Number"), gettext("Jail Name"), gettext("Jail IP Address") );
	$reqdfieldst = explode(" ", "numericint hostname ipaddr");
	
	do_input_validation($pconfig, $reqdfields, $reqdfieldsn, $input_errors);
	do_input_validation_type($pconfig, $reqdfields, $reqdfieldsn, $reqdfieldst, $input_errors);
	
	// Check to see if duplicate jail names:
	$index = array_search_ex($_POST['jailname'], $a_jail, "jailname");
	if ( FALSE !== $index ) {
		// If $index is not null, then there is a name conflict
		if (!(isset($uuid) && (FALSE !== ($cnid = array_search_ex($uuid, $a_jail, "uuid")))))
			// This means we are not editing an existing jail - we are creating a new one
			$input_errors[] = "The specified jailname is already in use. Please choose another.";
	}
	
	// Check to see if duplicate ip addresses:
	$index = array_search_ex($_POST['ipaddr'], $a_jail, "ipaddr");
	if ( FALSE !== $index && strcmp( $_POST['type'] , "Base" ) != 0) {
		// If $index is not null, then there is a name conflict
		if (!(isset($uuid) && (FALSE !== ($cnid = array_search_ex($uuid, $a_jail, "uuid")))))
			// This means we are not editing an existing jail - we are creating a new one
			$input_errors[] = "The specified ip address is already in use. Please choose another.";
	}
	
	// If they haven't set a path, then we need to assume one
	if ( ! isset($_POST['jailpath']) || empty($_POST['jailpath']) ) {
		$pconfig['jailpath']=$config['thebrig']['rootfolder']."/".$_POST['jailname'];
	}
	// If the specified path doesn't exist, we need to create it.
	if ( !is_dir( $pconfig['jailpath'] )) {
		mwexec ("/bin/mkdir {$pconfig['jailpath']}");
	}
	
	// This is a second test to see if the directory was created properly.
	if ( !is_dir( $pconfig['jailpath'] )){
		$input_errors[] = "Could not create directory for jail to live in!";
	}
	
	
	
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
				$_POST['base_ver'] = $file_split[2] . "-" . $file_split[3]; 
			}
			elseif ( strcmp($file_split[0], 'FreeBSD') == 0 && strcmp($file_split[4], 'lib32.txz') == 0 ){
				$lib_count++;
				$_POST['lib_ver'] = $file_split[2] . "-" . $file_split[3] ;
			}
			elseif ( strcmp($file_split[0], 'FreeBSD') == 0 && strcmp($file_split[4], 'doc.txz') == 0 ){
				$doc_count++;
				$_POST['doc_ver'] = $file_split[2] . "-" . $file_split[3] ;
			}
			elseif ( strcmp($file_split[0], 'FreeBSD') == 0 && strcmp($file_split[4], 'src.txz') == 0 ){
				$src_count++;
				$_POST['src_ver'] = $file_split[2] . "-" . $file_split[3] ;
			}
			else {
				$_POST['base_ver']= "Unknown";
				$_POST['lib_ver'] = "Unknown";
				$_POST['src_ver'] = "Unknown";
				$_POST['doc_ver'] = "Unknown";
			}
		} // End of foreach
	} // end of if ( files selected )
		
	// Need to deal with keeping track of the lib version as the same as the base version
	if ( $myarch != "amd64" ){
		$lib_ver = $base_ver ;
	}
	
	// Make sure only one tarball of each type is selected
	if ( $src_count > 1 || $base_count > 1 || $lib_count > 1 || $doc_count > 1 )
		$input_errors[] = "You have selected more than one of a given tarball type!!";
		
	// Validate if jail number is unique in order to reorder the jails (if necessary)
	// Alexey - why do we care about the jail number or the uuid?
	// Why not use the name?
	
	// a_jail is the list of all jails, sorted by their jail number
	
	// Index is the location within a_jail that has the same jailnumber as the one just entered
	$index = array_search_ex($_POST['jailno'], $a_jail, "jailno");
	// for each jail? How can i determine the loop control variables?

	if ( FALSE !== $index ) {
		// If $index is not null, then there is a number conflict (the jail number use in $POST conflicts
		// with a currently configured jail's number. The jail that has the conflict is jail $index
		
		// So, starting with that jail, running through all the rest, their jail number needs to be incremented
		// by one, to allow for the insertion of the newest jail
		if (isset($uuid) && (FALSE !== ($cnid = array_search_ex($uuid, $a_jail, "uuid")))){
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

	if ( empty( $input_errors )) {
		$jail = array();
		$jail['uuid'] = $_POST['uuid'];
		$jail['enable'] = isset($_POST['enable']) ? true : false;
		$jail['jailno'] = $_POST['jailno'];
		$jail['jailname'] = $_POST['jailname'];
		$jail['if'] = $_POST['if'];
		$jail['ipaddr'] = $_POST['ipaddr'];
		$jail['subnet'] = $_POST['subnet'];
		$jail['jailpath'] = $_POST['jailpath'];
		$jail['devfsrules'] = $_POST['dst'];
		$jail['jail_mount'] = isset($_POST['jail_mount']) ? true : false;
		$jail['devfs_enable'] = isset($_POST['devfs_enable']) ? true : false;
		$jail['proc_enable'] = isset($_POST['proc_enable']) ? true : false;
		$jail['fdescfs_enable'] = isset($_POST['fdescfs_enable']) ? true : false;
		$jail['fstab'] = $_POST['fstab'];
		$jail['afterstart0'] = $_POST['afterstart0'];
		$jail['afterstart1'] = $_POST['afterstart1'];
		$jail['exec_stop'] = $_POST['exec_stop'];
		$jail['extraoptions'] = $_POST['extraoptions'];
		$jail['desc'] = $_POST['desc'];
		$jail['base_ver'] = $_POST['base_ver'];
		$jail['lib_ver'] = $_POST['lib_ver'];
		$jail['src_ver'] = $_POST['src_ver'];
		$jail['doc_ver'] = $_POST['doc_ver'];
		$jail['image'] = $_POST['image'];
		$jail['image_type'] = $_POST['image_type'];
		$jail['attach_params'] = $_POST['attach_params'];
		$jail['attach_blocking'] = $_POST['attach_blocking'];
		$jail['force_blocking'] = $_POST['force_blocking'];
		$jail['zfs_datasets'] = $_POST['zfs_datasets'];
		$jail['fib'] = $_POST['fib'];
		
		// For each of the files in the array
		if ( count ( $files_selected ) > 0 ){
			foreach ( $files_selected as $file ) {
			// Delete the selected file from the "work" directory
				$commandextract = "tar xvf ".$config['thebrig']['rootfolder']."/work/".$file." -C ".$jail['jailpath'];
				mwexec_bg( $commandextract );
			}
		}
		
		
		// This determines if it was an update or a new jail
		if (isset($uuid) && (FALSE !== $cnid)) {
			// Copies newly modified properties over the old
			$a_jail[$cnid] = $jail;
			$mode = UPDATENOTIFY_MODE_MODIFIED;
		} else {
			// Copies the first jail into $a_jail
			$a_jail[] = $jail;
			
			$commandresolv = "cp /etc/resolv.conf ".$jail['jailpath']."/etc/";
			$commandtime = "cp ".$jail['jailpath']."/usr/share/zoneinfo/".$config['system']['timezone']." ".$jail['jailpath']."/etc/localtime";
			mwexec ($commandresolv);
			mwexec ($commandtime);
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
	showElementById('devfs_enable_tr','hide');
	showElementById('devfsrules_tr','hide');
	showElementById('proc_enable_tr','hide');
	showElementById('fdescfs_enable_tr','hide');
	showElementById('fstab_tr','hide');
	showElementById('txzfile_tr','hide');
$('#exractbin').change(function(){switch ($('#exractbin').val()) {
		case "custom":
			$('#txzfile_tr').show();
			break;
		case "freebsd":
			$('#txzfile_tr').hide();
			break;
		case "none":
			$('#txzfile_tr').hide();
			break;	
		default:
			$('#txzfile_tr').hide();
			
			break;
		}
	});
});

function mount_enable_change() {
	switch (document.iform.jail_mount.checked) {
		case false:
			showElementById('devfs_enable_tr','hide');
			showElementById('devfsrules_tr','hide');
			showElementById('proc_enable_tr','hide');
			showElementById('fdescfs_enable_tr','hide');
			showElementById('fstab_tr','hide');
			break;
		case true:
			showElementById('devfs_enable_tr','show');
			showElementById('devfsrules_tr','show');
			showElementById('proc_enable_tr','show');
			showElementById('fdescfs_enable_tr','show');
			showElementById('fstab_tr','show');
			break;
	}
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
      	<?php if (!empty($input_errors)) print_input_errors($input_errors); ?>
        <table width="100%" border="0" cellpadding="6" cellspacing="0">
			<?php html_titleline(gettext("Jail parameters"));?>
        	<?php html_inputbox("jailno", gettext("Jail number"), $pconfig['jailno'], gettext("The jail number determines the order of the jail."), true, 10);?>
			<?php html_inputbox("jailname", gettext("Jail name"), $pconfig['jailname'], gettext("The jail's  name."), true, 15,isset($uuid) && (FALSE !== $cnid));?>
			<?php $a_interface = array(get_ifname($config['interfaces']['lan']['if']) => "LAN"); for ($i = 1; isset($config['interfaces']['opt' . $i]); ++$i) { $a_interface[$config['interfaces']['opt' . $i]['if']] = $config['interfaces']['opt' . $i]['descr']; }?>
			<?php html_combobox("if", gettext("Jail Interface"), $pconfig['if'], $a_interface, gettext("Choose jail interface"), true);?>
			<?php html_ipv4addrbox("ipaddr", "subnet", gettext("Jail IP address"), $pconfig['ipaddr'], $pconfig['subnet'], "", true);?>
			<?php html_checkbox("enable", gettext("Jail start on boot"),			!empty($pconfig['enable']) ? true : false, gettext("Enable"), "");?>
			<?php html_inputbox("jailpath", gettext("Jail Location"), $pconfig['jailpath'], gettext("Sets an alternate location for the jail. Default is {$config['thebrig']['rootfolder']}{jail_name}/."), false, 40,isset($uuid) && (FALSE !== $cnid));?>
			<?php html_separator();?>
			<?php html_titleline(gettext("Mount"));?>
 			<?php html_checkbox("jail_mount", gettext("mount/umount jail's fs"), !empty($pconfig['jail_mount']) ? true : false, gettext("enable")," " ," ","mount_enable_change()");?>
			<?php html_checkbox("devfs_enable", gettext("Enable mount devfs"), !empty($pconfig['devfs_enable']) ? true : false, gettext("Use for enable master devfs to jail over fstab"), "", false);?>
			<?php html_inputbox("devfsrules", gettext("Devfs ruleset name"), !empty($pconfig['devfsrules']) ? $pconfig['devfsrules'] : "devfsrules_jail", gettext("You can change standart ruleset"), false, 30);?>
			<?php html_checkbox("proc_enable", gettext("Enable mount procfs"), !empty($pconfig['proc_enable']) ? true : false, "", "", false);?>
			<?php html_checkbox("fdescfs_enable", gettext("Enable mount fdescfs"), !empty($pconfig['fdescfs_enable']) ? true : false, "", "", false);?>
			<?php html_textarea("fstab", gettext("fstab"), !empty($pconfig['fstab']) ? $pconfig['fstab'] : "devfs /mnt/data/jail/_____/dev devfs rw 0 0", sprintf(gettext(" This will be added to fstab.  Format: device &lt;space&gt; mount-point as full path &lt;space&gt; fstype &lt;space&gt; options &lt;space&gt; dumpfreq &lt;space&gt; passno. If no need fstab - delete default line.  <a href=http://www.freebsd.org/doc/en_US.ISO8859-1/books/handbook/mount-unmount.html target=\"_blank\">Manual</a> ")), false, 65, 5, false, false);?>
			<?php html_separator();?>
			<?php html_titleline(gettext("Commands"));?>
			<?php html_inputbox("afterstart0", gettext("User command 0"), $pconfig['afterstart0'], gettext("command to execute after the one for starting the jail."), false, 50);?>
			<?php html_inputbox("afterstart1", gettext("User command 1"), $pconfig['afterstart1'], gettext("command to execute after the one for starting the jail."), false, 50);?>
			<?php html_inputbox("exec_stop", gettext("User command stop"), !empty($pconfig['exec_stop']) ? $pconfig['exec_stop'] : "/bin/sh /etc/rc.shutdown" , gettext("command to execute in jail for stopping. Usually <i>/bin/sh /etc/rc.shutdown</i>, but can defined by user for execute prestop script"), false, 50);?>
			<?php html_inputbox("extraoptions", gettext("Options. "), !empty($pconfig['extraoptions']) ? $pconfig['extraoptions'] : "-l -U root -n _____", gettext("Add to rc.conf.local variable jail_jailname_flags. "), false, 40);?>
			<?php html_inputbox("desc", gettext("Description"), $pconfig['desc'], gettext("You may enter a description here for your reference."), false, 50);?>
			<!-- in edit mode user not have access to extract binaries. I strongly disagree. -->
		
			<?php html_titleline(gettext("Tarballs"));?>
			<?php
			// This obtains a list of files that match the criteria (named anything FreeBSD*)
			// within the /work folder.
			$file_list = thebrig_tarball_list("FreeBSD*");
			// This filelist is then used to generate html code with checkboxes
			$installLib = thebrig_checkbox_list($file_list);
			if ( $installLib ) { // If the array exists and has a size, then display that html code?>
		<!-- The first td of this row is the box in the top row, far left. -->
		<tr><td width="22%" valign="top" class="vncellreq"><?=_THEBRIG_OFFICIAL_TB; ?></td>
		<!-- The next td is the larger box to the right, which contains the text box and info --> 
		<td width="78%" class="vtable">
			<?php echo $installLib; ?>
			</td></tr>
			<?php } //endif ?>
			
			
			<?php
			// This obtains a list of files that match the criteria (named anything *, excluding FreeBSD)
			// within the /work folder.
			$file_list = thebrig_tarball_list( "*" , array( "FreeBSD"  ) );
			// This filelist is then used to generate html code with checkboxes
			$installLib = thebrig_checkbox_list( $file_list );
			if ( $installLib )  {  // If the array exists and has a size, then display that html code?>
			<!-- The first td of this row is the box in the top row, far left. -->
		<tr><td width="22%" valign="top" class="vncellreq"><?=_THEBRIG_CUSTOM_TB; ?></td>
		<!-- The next td is the larger box to the right, which contains the text box and info --> 
		<td width="78%" class="vtable">
			<?php echo $installLib; ?>
			</td></tr>
			<?php } //endif ?>	
				</table>
				<div id="submit">
					<input name="Submit" type="submit" class="formbtn" value="<?=(isset($uuid) && (FALSE !== $cnid)) ? gettext("Save") : gettext("Add")?>" />
					<input name="Cancel" type="submit" class="formbtn" value="<?=gettext("Cancel");?>" />
					<input name="uuid" type="hidden" value="<?=$pconfig['uuid'];?>" />
				</div>
				<?php include("formend.inc");?>
			</form>
		</td>
	<?php  echo  $pconfig['name']. $pconfig['txzfile'];?>
	</tr>
</table>
<?php include("fend.inc");?>
