<?php
/*
   File:  extensions_thebrig.php
*/
require("auth.inc");
require("guiconfig.inc");
require_once("ext/thebrig/lang.inc");
require_once("ext/thebrig/functions.inc");
//include("trap.php"); 
require_once("XML/Serializer.php");
require_once("XML/Unserializer.php");

$pgtitle = array(_THEBRIG_EXTN,_THEBRIG_TITLE);

if ( !isset( $config['thebrig']['rootfolder']) || !is_dir( $config['thebrig']['rootfolder']."work" )) {
	$input_errors[] = _THEBRIG_NOT_CONFIRMED;
} // end of elseif

if (isset($_GET['name']) && ! isset($_GET['act'])) {
	$jailnameexec=$_GET['name'];
	$jailnamecmd=$_GET['action'];
	// Next lines write messages to log
	cmd_exec("/etc/rc.d/jail {$jailnamecmd} {$jailnameexec}",$a_tolog, $a_tolog1);
	$filelog = $config['thebrig']['rootfolder']."thebrig.log";
	$handle1 = fopen($filelog, "a+");
	foreach ($a_tolog1 as $tolog1 ) { fwrite ($handle1, "[".date("Y/m/d H:i:s")."]: TheBrig error!: ".trim($tolog1)."\n" ); }
	fclose ($handle1);
}

// sent to page data from config.xml
$rootfolder = $config['thebrig']['rootfolder'];
$pconfig['parastart'] = isset( $config['thebrig']['parastart'] ) ;
$pconfig['sethostname'] = isset($config['thebrig']['sethostname']); 
$pconfig['unixiproute'] = isset($config['thebrig']['unixiproute']); 
$pconfig['systenv'] = isset($config['thebrig']['systenv']); 
//

if ($_POST) {
	// insert into pconfig changes
	$pconfig = $_POST;

	$config['thebrig']['parastart'] = isset( $_POST['parastart'] );
	$config['thebrig']['sethostname'] = isset ( $_POST['sethostname'] );
	$config['thebrig']['unixiproute'] = isset ( $_POST['unixiproute'] );
	$config['thebrig']['systenv'] = isset ( $_POST['systenv'] );
	write_config();

	$retval = 0;
	// This checks to see if any webgui changes require a reboot, and create rc.conf.local
		if ( !file_exists($d_sysrebootreqd_path) && isset($config['thebrig']['content']) ) {
		write_rcconflocal();
		// OR the return value from the attempt to process the notification
		$retval |= updatenotify_process("thebrig", "thebrig_process_updatenotification");
		// Lock the config
		config_lock();
		//$retval |= rc_update_service("jail"); // This need be checked.  For jail this way no good
		// Unlock the config
		config_unlock();
	}
	// Set the save message
	$savemsg = get_std_save_message($retval);
	// If all the updates were successful, then we can delete the notification update
	if ($retval == 0) {
		updatenotify_delete("thebrig");
	}
} // end of $_POST

if (!isset($config['thebrig']['content']) || !is_array($config['thebrig']['content'])) {	$config['thebrig']['content'] = array(); }// declare list jails

array_sort_key($config['thebrig']['content'], "jailno");
$a_jail = &$config['thebrig']['content'];
// This is what we do when we return to this page from the "edit" page
if (isset($_GET['act']) && $_GET['act'] === "del") {
	// Prevent create archive for jail files into thebrig rootfolder with name <jailname>.tgz
	// If we want to delete the jail, set the notification
	updatenotify_set("thebrig", UPDATENOTIFY_MODE_DIRTY, $_GET['uuid']);
	header("Location: extensions_thebrig.php");
	exit;
}

function thebrig_process_updatenotification($mode, $data) {
	global $config;

	$retval = 0;

	switch ($mode) {
		case UPDATENOTIFY_MODE_NEW:
			
			$cnid = array_search_ex($data, $config['thebrig']['content'], "uuid");
			if (false !== $cnid) {
				$jail2add = $config['thebrig']['content'][$cnid];
				// I have these here because the tarballs take some time to get unpacked
				$commandresolv = "cp /etc/resolv.conf " . $jail2add['jailpath'] . "etc/";
				mwexec ($commandresolv);
			}
			// I have these commands here because it will take some time to untar the jail files
			break;
		case UPDATENOTIFY_MODE_MODIFIED:
			// I have these commands here because it will take some time to untar the jail files
			$cnid = array_search_ex($data, $config['thebrig']['content'], "uuid");
			if (false !== $cnid) {
				$jail2modify = $config['thebrig']['content'][$cnid];
				// Here we place any tasks that we want to be run after a jail has been modified.
				// Probably something to see if it was already running, and if so, restart it
			}
			break;
		case UPDATENOTIFY_MODE_DIRTY:
			// This indicates that we want to delete one or more of the jails
			$cnid = array_search_ex($data, $config['thebrig']['content'], "uuid");
			if (false !== $cnid) {
				$timestamp = date("Y-m-d_H:i:s");
				$jail2delete = $config['thebrig']['content'][$cnid];
				mwexec ( "/etc/rc.d/jail stop " . $jail2delete['jailname']);
				if ( $jail2delete['type'] === "slim") {
					mwexec("tar -cf " . $config['thebrig']['rootfolder'] . "work/backup_" . $jail2delete['jailname'] . "_" . $timestamp . ".tar -C " . $jail2delete['jailpath'] . " ./" );
					mwexec("tar -rf " . $config['thebrig']['rootfolder'] . "work/backup_" . $jail2delete['jailname'] . "_" . $timestamp . ".tar -X basejail/ -C " . $config['thebrig']['basejail']['folder'] . " ./" );
					mwexec("xz -S .txz " . $config['thebrig']['rootfolder'] . "work/backup_" . $jail2delete['jailname'] . "_" . $timestamp . ".tar" );
				}
				else 
					mwexec("tar -czf " . $config['thebrig']['rootfolder'] . "work/backup_" . $jail2delete['jailname'] . "_" . $timestamp . ".txz -C " . $jail2delete['jailpath'] . " ./" );
				mwexec ( "umount -a -F /etc/fstab." .  $jail2delete['jailname']);
				if ( $jail2delete['devfs_enable'] == true )
					mwexec ( "umount " . $jail2delete['jailpath'] . "dev");
				mwexec("chflags -R noschg {$jail2delete['jailpath']}");
				mwexec("rm -rf {$jail2delete['jailpath']}");
				mwexec( "rm /etc/fstab." . $jail2delete['jailname']);
				unset($config['thebrig']['content'][$cnid]);
				write_config();
			}
			break;
	}

	return $retval;
}



include("fbegin.inc");?>
<!----- This make "live table" ------>
<script language="JavaScript">
function disable_buttons() {
	document.iform.Submit.disabled = true;
	document.iform.submit();}
var auto_refresh = setInterval(
		function()
		{
		$('#loaddiv').load('extensions_thebrig_check.php');
		}, 5000);
</script>
<!--------  This is view ------->
<form action="extensions_thebrig.php" method="post" name="iform" id="iform" enctype="multipart/form-data">
<table width="100%" border="0" cellpadding="0" cellspacing="0" >
	<tr><td class="tabnavtbl">
		<ul id="tabnav">
			<li class="tabact"><a href="extensions_thebrig.php"><span><?=_THEBRIG_JAILS;?></span></a></li>
			<li class="tabinact"><a href="extensions_thebrig_update.php"><span><?=_THEBRIG_UPDATES;?></span></a></li>
			<li class="tabinact"><a href="extensions_thebrig_tarballs.php"><span><?=_THEBRIG_MAINTENANCE;?></span></a></li>
			<li class="tabinact"><a href="extensions_thebrig_log.php"><span><?=gettext("Log");?></span></a></li>
					</span> </a>
				</li>
		</ul>
	</td></tr>
	
	<tr>
		
						
		<td class="tabcont">
		<?php if ($input_errors) print_input_errors($input_errors);?>
		<?php if ($errormsg) print_error_box($errormsg);?>
		<?php if ($savemsg) print_info_box($savemsg);?>
		<?php if (updatenotify_exists("thebrig")) print_config_change_box();?>
			<table width="100%" border="0" cellpadding="0" cellspacing="0">
				<tr><?php html_titleline(gettext("On-line view"));?></tr>
				<tr> <!----  import table and check from another page --->
					<td class="shadow">
					<?php if ( !isset( $config['thebrig']['rootfolder']) ) : ?>
					<a title="<?=gettext("Configure TheBrig please first");?>
					<?php elseif ( !isset( $config['thebrig']['content']) ):?>
					<a title="<?=gettext("Configure at least one jail first"); ?>				
					<?php else:?>
					<div id="loaddiv" style="display: block;"><script>$('#loaddiv').load("extensions_thebrig_check.php");</script></div>
					<?php endif;?>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr><td class="tabcont">
		<table width="100%" border="0" cellpadding="6" cellspacing="0">
		<tr><td colspan="2" valign="top" class="optsect_t">
		<table border="0" cellspacing="0" cellpadding="0" width="100%">
		
		<tr><?php html_titleline(gettext("<strong>TheBrig config</strong>"));?>
	
		<td align="right" class="optsect_s"></td>
		</tr>
				</table></td></tr>
					<tr><td width="15%" valign="top" class="vncell"><?=gettext("Jails");?></td>
						<td width="85%" class="vtable">
							<table width="100%" border="0" cellpadding="0" cellspacing="0">
								<tr>
									<td width="4%" class="listhdrlr">&nbsp;</td>
									<td width="10%" class="listhdrr"><?=gettext("Name");?></td>
									<td width="5%" class="listhdrr"><?=gettext("Interface");?></td>
									<td width="10%" class="listhdrr"><?=gettext("Start on boot");?></td>
									<td width="10%" class="listhdrr"><?=gettext("IP");?></td>
									<td width="12%" class="listhdrr"><?=gettext("Hostname");?></td>
									<td width="15%" class="listhdrr"><?=htmlspecialchars(gettext("Path"));?></td>
									<td width="19%" class="listhdrr"><?=gettext("Description");?></td>
									<td width="15%" class="list"></td>
								</tr>
																<?php foreach ($a_jail as $jail):?>
								<?php $notificationmode = updatenotify_get_mode("thebrig", $jail['uuid']);?>
								<tr>
									<td class="<?=$enable?"listr":"listrd";?>"><?=htmlspecialchars(empty($jail['jailno']) ? "*" : $jail['jailno']);?>&nbsp;</td>
									<td class="<?=$enable?"listr":"listrd";?>"><?=htmlspecialchars($jail['jailname']);?>&nbsp;</td>
									<td class="<?=$enable?"listr":"listrd";?>"><?=htmlspecialchars(empty($jail['if']) ? "*" : $jail['if']);?>&nbsp;</td>
									<td class="<?=$enable?"listlr":"listlrd";?>"><?=htmlspecialchars(isset($jail['enable']) ? "YES" : "NO");?></td>
									<td class="<?=$enable?"listr":"listrd";?>"><?=htmlspecialchars($jail['ipaddr'] . " / " . $jail['subnet']) ;?>&nbsp;</td>
									<td class="<?=$enable?"listrc":"listrcd";?>"><?=htmlspecialchars($jail['jailname'] . "." . $config['system']['domain']);?>&nbsp;</td>
									<td class="<?=$enable?"listr":"listrd";?>"><?=htmlspecialchars($jail['jailpath']);?>&nbsp;</td>
									<td class="listbg"><?=htmlspecialchars($jail['desc']);?>&nbsp;</td>
									<?php if (UPDATENOTIFY_MODE_DIRTY != $notificationmode):?>
									<td valign="middle" nowrap="nowrap" class="list">
										<a href="extensions_thebrig_edit.php?uuid=<?=$jail['uuid'];?>"><img src="e.gif" title="<?=gettext("Edit jail");?>" border="0" alt="<?=gettext("Edit jail");?>" /></a>&nbsp;
										<a href="extensions_thebrig.php?act=del&amp;uuid=<?=$jail['uuid'];?>&amp;name=<?=$jail['jailname'];?>" onclick="return confirm('<?=gettext("Do you really want to delete this jail? I will archive file just in case. It can be removed later. ");?>')"><img src="x.gif" title="<?=gettext("Delete jail");?>" border="0" alt="<?=gettext("Delete jail");?>" /></a>&nbsp;
										<a href="extensions_thebrig_fstab.php?act=editor&amp;uuid=<?=$jail['uuid'];?>"><img src="ext/thebrig/fstab.png" title="<?=gettext("Edit fstab for this jail");?>" border="0" alt="<?=gettext("Edit jail's fstab");?>" /></a>
									</td>
									<?php else:?>
									<td valign="middle" nowrap="nowrap" class="list">
										<img src="del.gif" border="0" alt="" />
									</td>
									<?php endif;?>									
								</tr>
								<?php endforeach; ?>
								<tr>
									<td class="list" colspan="8"></td>
									<td class="list">
										<a href="extensions_thebrig_edit.php"><img src="plus.gif" title="<?=gettext("Add jail");?>" border="0" alt="<?=gettext("Add jail");?>" /></a>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td width="15%" valign="top" class="vncell"><?=gettext("Globals");?></td>
						<td width="85%" class="vtable">
							<input name="parastart" type="checkbox" id="parastart" value="yes" <?php if (!empty($pconfig['parastart'])) echo "checked=\"checked\""; ?> /><?=_THEBRIG_JAIL_PARALLEL?><br />
							<input name="sethostname" type="checkbox" id="sethostname" value="yes" <?php if (!empty($pconfig['sethostname'])) echo "checked=\"checked\""; ?> /><?=_THEBRIG_JAIL_ROOT_HOST?><br />
							<input name="unixiproute" type="checkbox" id="unixiproute" value="yes" <?php if (!empty($pconfig['unixiproute'])) echo "checked=\"checked\""; ?> /><?=_THEBRIG_JAIL_ROUTE?><br />
							<input name="systenv" type="checkbox" id="systenv" value="yes" <?php if (!empty($pconfig['systenv'])) echo "checked=\"checked\""; ?> /><?=_THEBRIG_JAIL_IPC?>
						</td>
					</tr>

				</table>
				<div id="submit">
					<input name="Submit" type="submit" class="formbtn" value="<?=gettext("Save ");?>" />
				</div>
	</table>
	
</td></tr>
<?php include("formend.inc");?>
</form>
</table>
<?php include("fend.inc"); ?>

