<?php
/*
*/
require("auth.inc");
require("guiconfig.inc");
require_once("ext/thebrig/lang.inc");
require_once("ext/thebrig/functions.inc");
//require_once("XML/Serializer.php");
//require_once("XML/Unserializer.php");

$pgtitle = array(_THEBRIG_EXTN,_THEBRIG_TITLE);

$today = date("d.m.Y.G:i:s");
$mess = "clear";

// sent to page data from config.xml
$pconfig['enable'] = isset( $config['thebrig']['enable'] ); 
$pconfig['rootdir'] = $config['thebrig']['rootdir'];
$pconfig['parastart'] = isset( $config['thebrig']['parastart'] ) ;
$pconfig['sethostname'] = isset($config['thebrig']['sethostname']); 
$pconfig['unixiproute'] = isset($config['thebrig']['unixiproute']); 
$pconfig['systenv'] = isset($config['thebrig']['systenv']); 


if ($_POST) {
	
	$config['thebrig']['parastart'] = isset( $_POST['parastart'] );
	$config['thebrig']['sethostname'] = isset ( $_POST['sethostname'] );
	$config['thebrig']['unixiproute'] = isset ( $_POST['unixiproute'] );
	$config['thebrig']['systenv'] = isset ( $_POST['systenv'] );

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
			// This indicates that we want to delete one of the jails
			$cnid = array_search_ex($data, $config['thebrig']['jail'], "uuid");
			if (false !== $cnid) {
				unset($config['thebrig']['jail'][$cnid]);
				write_config();
			}
			break;
	}

	return $retval;
}
?> 

<?php include("fbegin.inc");?>
<!--------  This is view ------->

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
	<form action="extensions_thebrig.php" method="post" name="iform" id="iform" enctype="multipart/form-data">
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr><td class="tabcont">
		<?php if ($input_errors) print_input_errors($input_errors);?>
		<?php if ($errormsg) print_error_box($errormsg);?>
		<?php if ($savemsg) print_info_box($savemsg);?>
		<?php if (updatenotify_exists("thebrig")) print_config_change_box();?>
		<table width="100%" border="0" cellpadding="6" cellspacing="0">
		<tr><td colspan="2" valign="top" class="optsect_t">
		<table border="0" cellspacing="0" cellpadding="0" width="100%">
		<tr><td class="optsect_s"><strong><?=_THEBRIG_TITLE; ?></strong></td>
		<td align="right" class="optsect_s"></td>
		</tr>
				</table></td></tr>
					<tr><td width="15%" valign="top" class="vncell"><?=gettext("Jails");?></td>
						<td width="85%" class="vtable">
							<table width="100%" border="0" cellpadding="0" cellspacing="0">
								<tr>
									<td width="4%" class="listhdrlr">&nbsp;</td>
									<td width="15%" class="listhdrr"><?=gettext("Name");?></td>
									<td width="5%" class="listhdrr"><?=gettext("Status");?></td>
									<td width="5%" class="listhdrr"><?=gettext("ID");?></td>
									<td width="10%" class="listhdrr"><?=gettext("IP");?></td>
									<td width="15%" class="listhdrr"><?=gettext("Hostname");?></td>
									<td width="20%" class="listhdrr"><?=htmlspecialchars(gettext("Path"));?></td>
									<td width="16%" class="listhdrr"><?=gettext("Start on boot");?></td>
									<td width="10%" class="list"></td>
								</tr>
								<?php foreach ($a_jail as $jail):?>
								<?php $notificationmode = updatenotify_get_mode("thebrig", $jail['uuid']);?>
								<tr>
									<?php if (UPDATENOTIFY_MODE_DIRTY != $notificationmode):?>
									<td valign="middle" nowrap="nowrap" class="list">
										<a href="extensions_thebrig_edit.php.php?uuid=<?=$jail['uuid'];?>"><img src="e.gif" title="<?=gettext("Edit jail");?>" border="0" alt="<?=gettext("Edit jail");?>" /></a>
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
							<input name="parastart" type="checkbox" id="parastart" value="yes" <?php if (!empty($pconfig['parastart'])) echo "checked=\"checked\""; ?> /><?=gettext(" Start jail in the background");?><br />
							<input name="sethostname" type="checkbox" id="sethostname" value="yes" <?php if (!empty($pconfig['sethostname'])) echo "checked=\"checked\""; ?> /><?=gettext(" Allow root user in a jail to change its hostname");?><br />
							<input name="unixiproute" type="checkbox" id="unixiproute" value="yes" <?php if (!empty($pconfig['unixiproute'])) echo "checked=\"checked\""; ?> /><?=gettext(" Route only TCP/IP within a jail");?><br />
							<input name="systenv" type="checkbox" id="systenv" value="yes" <?php if (!empty($pconfig['systenv'])) echo "checked=\"checked\""; ?> /><?=gettext(" Allow SystemV IPC use from within a jail");?>
						</td>
					</tr>
				</table>
				<div id="submit">
					<input name="Submit" type="submit" class="formbtn" value="<?=gettext("Save ");?>" />
				</div>
	</table>
	<?php include("formend.inc");?>
</form>
</td></tr>
</table>
<?php include("fend.inc"); ?>

