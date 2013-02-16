<?php
/*
 * extensions_thebrig_edit.php
	*/
require("auth.inc");
require("guiconfig.inc");
require_once("ext/thebrig/lang.inc");
require_once("ext/thebrig/functions.inc");

//I check install.
if ( !is_dir ( $config['thebrig']['rootfolder']."/work") ) { 
	$input_errors[] = _THEBRIG_NOT_CONFIRMED;  header ("Location: /extension_thebrig_config.php"); // May be replace previos if ???
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
$pgtitle = array(gettext("TheBrig"), gettext("Jail"), isset($uuid) ? gettext("Edit") : gettext("Add"));
$snid = "jail60"; // what is this for?

// This checks if the current XML config has a section for jails, or if it's an array
if (!isset($config['thebrig']['jail']) || !is_array($config['thebrig']['jail']))
	// If the array doesn't exist, it is created.
	$config['thebrig']['jail'] = array();

// This sorts thebrig's configuration array by the jailno
array_sort_key($config['thebrig']['jail'], "jailno");
// This identifies the jail section of the XML, but does so by reference.
$a_jail = &$config['thebrig']['jail'];

// This checks that the $uuid variable is set, and that the 
// attempt to determine the index of the jail config that has the same 
// uuid as the page was entered with
if (isset($uuid) && (FALSE !== ($cnid = array_search_ex($uuid, $a_jail, "uuid")))) {
	$pconfig['uuid'] = $a_jail[$cnid]['uuid'];
	$pconfig['enable'] = isset($a_jail[$cnid]['enable']);
	$pconfig['jailno'] = $a_jail[$cnid]['jailno'];
	$pconfig['jailname'] = $a_jail[$cnid]['jailname'];
	$pconfig['if'] = $a_jail[$cnid]['if'];
	$pconfig['ipaddr'] = $a_jail[$cnid]['ipaddr'];
	$pconfig['subnet'] = $a_jail[$cnid]['subnet'];
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
}
// In this case, the $uuid isn't set (this is a new jail) 
else {
	$pconfig['uuid'] = uuid();
	$pconfig['enable'] = false;
	$pconfig['jailno'] = get_next_jailnumber();
	$pconfig['jailname'] = "";
	$pconfig['if'] = "";
	$pconfig['ipaddr'] = "";
	$pconfig['subnet'] = "32";
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
}

$myrelease = exec("/usr/bin/uname -r");
	$myarch = exec("/usr/bin/uname -p");
	$mysystem = exec("/usr/bin/uname -s");
	$myfile = $config['thebrig']['rootfolder'] . "/work/" . $mysystem ."-" . $myarch . "-" . $myrelease . "-base.txz";
	if (!is_file($myfile)) { header("Location: extensions_thebrig_tarballs.php"); }
{}
if ($_POST) {
	unset($input_errors);
	$pconfig = $_POST;

	if (isset($_POST['Cancel']) && $_POST['Cancel']) {
		header("Location: extensions_thebrig.php");
		exit;
	}

	// Input validation.
	// Validate if jail number is unique.
	// Alexey - why do we care about the jail number or the uuid?
	// Why not use the name?
	$index = array_search_ex($_POST['jailno'], $a_jail, "jailno");
	if (FALSE !== $index) {
		if (!((FALSE !== $cnid) && ($a_rule[$cnid]['uuid'] === $a_rule[$index]['uuid']))) {
			$input_errors[] = gettext("The unique jail number is already used.");
		}
	}

	if ( empty( $input_errors )) {
		$jail = array();
		$jail['uuid'] = $_POST['uuid'];
		$jail['enable'] = isset($_POST['enable']) ? true : false;
		$jail['jailno'] = $_POST['jailno'];
		$jail['jailname'] = $_POST['jailname'];
		$jail['if'] = $_POST['if'];
		$jail['ipaddr'] = $_POST['ipaddr'];
		$jail['subnet'] = $_POST['subnet'];
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
		
		// This determines if it was an update or a new jail
		if (isset($uuid) && (FALSE !== $cnid)) {
			// Copies newly modified properties over the old
			$a_jail[$cnid] = $jail;
			$mode = UPDATENOTIFY_MODE_MODIFIED;
		} else {
			// Copies the first jail into $a_jail
			$a_jail[("cell" . $jail['jailno'])] = $jail;
			$mode = UPDATENOTIFY_MODE_NEW;
		}
		
		updatenotify_set("thebrig", $mode, $jail['uuid']);
		write_config();
		mwexec ("/bin/mkdir {$config['thebrig']['rootfolder']}/{$jail['jailname']}");
		//extract tarball into jail
		if (isset($_POST['exractbin']) ) {
		$commandextract = "tar xvf ".$config['thebrig']['rootfolder']."/work/".$mysystem."-".$myarch."-".$myrelease."-base.txz -C ". $config['thebrig']['rootfolder']."/".$jail['jailname']."/";
		$commandresolv = "cp /etc/resolv.conf ".$config['thebrig']['rootfolder']."/".$jail['jailname']."/etc/";
		
		mwexec ($commandextract);
		mwexec ($commandresolv);
		
		}
		header("Location: extensions_thebrig.php");
		exit;
	}
}

// Get next jail number.
function get_next_jailnumber() {
	global $config;

	// Set starting jail number
	$jailno = 1;

	$a_jails = $config['thebrig']['jail'];
	if (false !== array_search_ex(strval($jailno), $a_jails, "jailno")) {
		do {
			$jailno += 1; // Increase jail number until a unused one is found.
		} while (false !== array_search_ex(strval($jailno), $a_jails, "jailno"));
	}

	return $jailno;
}
?>
<?php include("fbegin.inc");?>
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
        	<?php html_titleline_checkbox("enable", gettext("Jail start on boot"), !empty($pconfig['enable']) ? true : false, gettext("Enable"));?>
        	<?php html_inputbox("jailno", gettext("Jail number"), $pconfig['jailno'], gettext("The jail number determines the order of the jail."), true, 10);?>
			<?php html_inputbox("jailname", gettext("Jail name"), $pconfig['jailname'], gettext("The jail's  name."), true, 15);?>
			<?php $a_interface = array(get_ifname($config['interfaces']['lan']['if']) => "LAN"); for ($i = 1; isset($config['interfaces']['opt' . $i]); ++$i) { $a_interface[$config['interfaces']['opt' . $i]['if']] = $config['interfaces']['opt' . $i]['descr']; }?>
			<?php html_combobox("if", gettext("Jail Interface"), $pconfig['if'], $a_interface, gettext("Choose jail interface"), true);?>
			<?php html_ipv4addrbox("ipaddr", "subnet", gettext("Jail IP address"), $pconfig['ipaddr'], $pconfig['subnet'], "", true);?>
			<?php html_separator();?>
			<?php html_titleline(gettext("Mount"));?>
			<?php html_checkbox("jail_mount", gettext("mount/umount jail's fs"), !empty($pconfig['jail_mount']) ? true : false, gettext("enable"), "");?>
			<?php html_checkbox("devfs_enable", gettext("Enable mount devfs"), !empty($pconfig['devfs_enable']) ? true : false, gettext("Use for enable master devfs to jail over fstab"), "", false);?>
			<?php html_inputbox("devfsrules", gettext("Devfs ruleset name"), $pconfig['devfsrules'], gettext("usually you want <i>devfsrules_jail</i>"), false, 30);?>
			<?php html_checkbox("proc_enable", gettext("Enable mount procfs"), !empty($pconfig['proc_enable']) ? true : false, "", "", false);?>
			<?php html_checkbox("fdescfs_enable", gettext("Enable mount fdescfs"), !empty($pconfig['fdescfs_enable']) ? true : false, "", "", false);?>
			<?php html_textarea("fstab", gettext("fstab"), !empty($pconfig['fstab']) ? $pconfig['fstab'] : "devfs /mnt/data/jail/proto/dev devfs rw 0 0", sprintf(gettext(" This will be added to fstab.  Format: device &lt;space&gt; mount-point as full path &lt;space&gt; fstype &lt;space&gt; options &lt;space&gt; dumpfreq &lt;space&gt; passno. <a href=http://www.freebsd.org/doc/en_US.ISO8859-1/books/handbook/mount-unmount.html target=\"_blank\">Manual</a> ")), false, 65, 5, false, false);?>
			<?php html_separator();?>
			<?php html_titleline(gettext("Commands"));?>
			<?php html_inputbox("afterstart0", gettext("User command 0"), $pconfig['afterstart0'], gettext("command to execute after the one for starting the jail."), false, 50);?>
			<?php html_inputbox("afterstart1", gettext("User command 1"), $pconfig['afterstart1'], gettext("command to execute after the one for starting the jail."), false, 50);?>
			<?php html_inputbox("exec_stop", gettext("User command stop"), $pconfig['exec_stop'], gettext("command to execute in jail for stopping. Usually <i>/bin/sh /etc/rc.shutdown</i>, but can defined by user for execute prestop script"), false, 50);?>
			<?php html_inputbox("extraoptions", gettext("Options. "), $pconfig['extraoptions'], gettext("Add to rc.conf.local"), false, 40);?>
			<?php html_inputbox("desc", gettext("Description"), $pconfig['desc'], gettext("You may enter a description here for your reference."), false, 50);?>
			<?php html_separator();?>
			<?php html_checkbox("exractbin", gettext("Extract binaries"), $pconfig['exractbin'], "If you wan't extract binaries now check it", "", false);?>
			
				</table>
				<div id="submit">
					<input name="Submit" type="submit" class="formbtn" value="<?=(isset($uuid) && (FALSE !== $cnid)) ? gettext("Save") : gettext("Add")?>" />
					<input name="Cancel" type="submit" class="formbtn" value="<?=gettext("Cancel");?>" />
					<input name="uuid" type="hidden" value="<?=$pconfig['uuid'];?>" />
				</div>
				<?php include("formend.inc");?>
			</form>
		</td>
	
	</tr>
</table>
<?php include("fend.inc");?>