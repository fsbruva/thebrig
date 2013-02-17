<?php
/*
   File:  extensions_thebrig.php
*/
require("auth.inc");
require("guiconfig.inc");
require_once("ext/thebrig/lang.inc");
require_once("ext/thebrig/functions.inc");
// require_once("XML/Serializer.php");
// require_once("XML/Unserializer.php");
if (isset($_GET['name'])) {

	$actjailname = $_GET['name'];	
$jailnameexec=$_GET['name'];
$jailnamecmd=$_GET['action'];
// I use command touch and rm for simulation only.  When we make jail working, we replace command on /etc/rc.d/jail $action $name.
// #################!!!!!!!!!!!!!!!!!!!!!!!!!!!!
if ($jailnamecmd == "start") { $jailcommand="touch /var/run/jail_".$jailnameexec.".id";}
else { $jailcommand="rm /var/run/jail_".$jailnameexec.".id";}
mwexec($jailcommand);
}
$pgtitle = array(_THEBRIG_EXTN,_THEBRIG_TITLE);

if ( !isset( $config['thebrig']['rootfolder']) ) {
	$input_errors[] = _THEBRIG_NOT_CONFIRMED;
} // end of elseif


// sent to page data from config.xml
$pconfig['glenable'] = isset( $config['thebrig']['glenable'] ); 
$rootfolder = $config['thebrig']['rootfolder'];
$pconfig['parastart'] = isset( $config['thebrig']['parastart'] ) ;
$pconfig['sethostname'] = isset($config['thebrig']['sethostname']); 
$pconfig['unixiproute'] = isset($config['thebrig']['unixiproute']); 
$pconfig['systenv'] = isset($config['thebrig']['systenv']); 


if ($_POST) {
	// insert into pconfig changes
	$pconfig['glenable'] = isset( $_POST['glenable'] ); 
	$rootfolder = $config['thebrig']['rootfolder'];
	$pconfig['parastart'] = isset( $_POST['parastart'] ) ;
	$pconfig['sethostname'] = isset($_POST['sethostname']); 
	$pconfig['unixiproute'] = isset($_POST['unixiproute']); 
	$pconfig['systenv'] = isset($_POST['systenv']);
	
	$config['thebrig']['glenable'] = isset( $_POST['glenable'] );
	$config['thebrig']['parastart'] = isset( $_POST['parastart'] );
	$config['thebrig']['sethostname'] = isset ( $_POST['sethostname'] );
	$config['thebrig']['unixiproute'] = isset ( $_POST['unixiproute'] );
	$config['thebrig']['systenv'] = isset ( $_POST['systenv'] );
	$config['thebrig']['rootfolder'] = $rootfolder;
	write_config();

	$retval = 0;
	// This checks to see if any webgui changes require a reboot 
	if ( !file_exists($d_sysrebootreqd_path) ) {
		// OR the return value from the attempt to process the notification
		$retval |= updatenotify_process("thebrig", "thebrig_process_updatenotification");
		// Lock the config
		config_lock();
		// OR the return value from the attempt to restart the firewall
		$retval |= rc_update_service("ipfw");
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

if (!isset($config['thebrig']['jail']) || !is_array($config['thebrig']['jail'])) {	$config['thebrig']['jail'] = array(); }// declare list jails

array_sort_key($config['thebrig']['jail'], "jailno");
$a_jail = &$config['thebrig']['jail'];
// This is what we do when we return to this page from the "edit" page
if (isset($_GET['act']) && $_GET['act'] === "del") {
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
		case UPDATENOTIFY_MODE_MODIFIED:
			break;
		case UPDATENOTIFY_MODE_DIRTY:
			// This indicates that we want to delete one or more of the jails
			$cnid = array_search_ex($data, $config['thebrig']['jail'], "uuid");
			if (false !== $cnid) {
				unset($config['thebrig']['jail'][$cnid]);
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
			<li class="tabact">
				<a href="extensions_thebrig.php"><span><?=_THEBRIG_JAILS;?></span></a>
			</li>
			<li class="tabinact">
				<a href="extensions_thebrig_config.php"><span><?=_THEBRIG_MAINTENANCE;?></span></a>
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
					<a title="<?=gettext("Configure TheBrig please in first");?>
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
		
		<tr><?php html_titleline_checkbox("glenable", gettext("<strong>TheBrig config</strong>"), !empty($pconfig['glenable']) ? true : false, gettext("Enable") );?>
	
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
									<?php $enable = isset($jail['enable']);
									switch ($jail['action']) {
										case "allow":
											$actionimg = "fw_action_allow.gif";
											break;
										case "deny":
											$actionimg = "fw_action_deny.gif";
											break;
										case "unreach host":
											$actionimg = "fw_action_reject.gif";
											break;
									}
									?>
									<td class="<?=$enable?"listr":"listrd";?>"><?=htmlspecialchars(empty($jail['jailno']) ? "*" : $jail['jailno']);?>&nbsp;</td>
									
									<td class="<?=$enable?"listr":"listrd";?>"><?=htmlspecialchars($jail['jailname']);?>&nbsp;</td>
									<td class="<?=$enable?"listr":"listrd";?>"><?=htmlspecialchars(empty($jail['if']) ? "*" : $jail['if']);?>&nbsp;</td>
									<td class="<?=$enable?"listlr":"listlrd";?>"><?=htmlspecialchars(empty($jail['enable']) ? "YES" : "NO");?></td>
									<td class="<?=$enable?"listr":"listrd";?>"><?=htmlspecialchars($jail['ipaddr'] . " / " . $jail['subnet']) ;?>&nbsp;</td>
									<td class="<?=$enable?"listrc":"listrcd";?>"><?=htmlspecialchars($jail['jailname'] . "." . $config['system']['domain']);?>&nbsp;</td>
									<td class="<?=$enable?"listr":"listrd";?>"><?=htmlspecialchars($config['thebrig']['rootfolder'] . "/" . $jail['jailname']);?>&nbsp;</td>
									
									<td class="listbg"><?=htmlspecialchars($jail['desc']);?>&nbsp;</td>
									<?php if (UPDATENOTIFY_MODE_DIRTY != $notificationmode):?>
									<td valign="middle" nowrap="nowrap" class="list">
										<a href="extensions_thebrig_edit.php?uuid=<?=$jail['uuid'];?>"><img src="e.gif" title="<?=gettext("Edit jail");?>" border="0" alt="<?=gettext("Edit jail");?>" /></a>
										<a href="extensions_thebrig.php?act=del&amp;uuid=<?=$jail['uuid'];?>" onclick="return confirm('<?=gettext("Do you really want to delete this jail?");?>')"><img src="x.gif" title="<?=gettext("Delete jail");?>" border="0" alt="<?=gettext("Delete jail");?>" /></a>
									</td>
									<?php else:?>
									<td valign="middle" nowrap="nowrap" class="list">
										<img src="del.gif" border="0" alt="" />
									</td>
									<?php endif;?>
								</tr>
								<?php endforeach;?>
								<tr>
									<td class="list" colspan="8"></td>
									<td class="list">
										<a href="extensions_thebrig_edit.php"><img src="plus.gif" title="<?=gettext("Add jail");?>" border="0" alt="<?=gettext("Add jail");?>" /></a>
										<?php if (!empty($a_jail)):?>
											<a href="extensions_thebrig.php?act=del&amp;uuid=all" onclick="return confirm('<?=gettext("Do you really want to delete all jails?");?>')"><img src="x.gif" title="<?=gettext("Delete all jails");?>" border="0" alt="<?=gettext("Delete all jails");?>" /></a>
										<?php endif;?>
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

