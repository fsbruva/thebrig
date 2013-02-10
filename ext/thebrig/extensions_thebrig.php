<?php
/*
*/
require("auth.inc");
require("guiconfig.inc");
require_once("ext/thebrig/lang.inc");
require_once("ext/thebrig/functions.inc");
equire_once("XML/Serializer.php");
require_once("XML/Unserializer.php");

$pgtitle = array(_THEBRIG_EXTN,_THEBRIG_TITLE);

$today = date("d.m.Y.G:i:s");
$mess = "clear";

if ( !isset($config['thebrig']) || !is_array($config['thebrig'])) { $config['thebrig'] = array(); } // declare thebrig tag
if (!isset($config['thebrig']['jails']) || !is_array($config['thebrig']['jails'])) 	$config['thebrig']['jails'] = array(); // declare list jails

// sent to page data from config.xml
$pconfig['enable'] = isset($config['thebrig']['enable']);
$pconfig['rootdir'] = $config['thebrig']['rootdir'];
$pconfig['parastart'] = isset($config['thebrig']['parastart']);
$pconfig['sethostname'] = isset($config['thebrig']['sethostname']);
$pconfig['unixiproute'] = isset($config['thebrig']['unixiproute']);
$pconfig['systenv'] = isset($config['thebrig']['systenv']);

if ($_POST) {
	$pconfig = $_POST;

	$config['thebrig']['enable'] = isset($_POST['enable']) ? true : false;
	$config['thebrig']['parastart'] = isset($_POST['parastart']);
	$config['thebrig']['sethostname'] = isset($_POST['sethostname']);
	$config['thebrig']['unixiproute'] = isset($_POST['unixiproute']);
	$config['thebrig']['systenv'] = isset($_POST['systenv']);

	//write_config();

	$retval = 0;
	if (!file_exists($d_sysrebootreqd_path)) {
		$retval |= updatenotify_process("thebrig", "thebrig_process_updatenotification");
		config_lock();
		$retval |= rc_update_service("ipfw");
		config_unlock();
	}
	$savemsg = get_std_save_message($retval);
	if ($retval == 0) {
		updatenotify_delete("thebrig");
	}
}
array_sort_key($config['thebrig']['jail'], "jailno");
$a_jail = &$config['thebrig']['jail'];
if (isset($_GET['act']) && $_GET['act'] === "del") {
	if ($_GET['uuid'] === "all") {
		foreach ($a_jail as $jailk => $jailv) {
			updatenotify_set("thebrig", UPDATENOTIFY_MODE_DIRTY, $a_jail[$jailk]['uuid']);
		}
	} else {
		updatenotify_set("thebrig", UPDATENOTIFY_MODE_DIRTY, $_GET['uuid']);
	}
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
<script type="text/javascript">
<!--
function enable_change(enable_change) {
	var endis = !(document.iform.enable.checked || enable_change);
}
//-->
</script>
<!--------  This is view ------->
<form action="extensions_thebrig.php" method="post" name="iform" id="iform" enctype="multipart/form-data">
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
		<tr>
			<td class="tabcont">
				<?php if ($input_errors) print_input_errors($input_errors);?>
				<?php if ($errormsg) print_error_box($errormsg);?>
				<?php if ($savemsg) print_info_box($savemsg);?>
				<?php if (updatenotify_exists("thebrig")) print_config_change_box();?>
				<table width="100%" border="0" cellpadding="6" cellspacing="0">
					<?php html_titleline_checkbox("enable", gettext("TheBrigs"), !empty($pconfig['enable']) ? true : false, gettext("Enable"), "enable_change(false)");?>
					<tr>
						<td width="15%" valign="top" class="vncell"><?=gettext("Jails");?></td>
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
									<td class="<?=$enable?"listlr":"listlrd";?>"><img src="<?=$actionimg;?>" alt="" /></td>
									<td class="<?=$enable?"listr":"listrd";?>"><?=strtoupper($jail['protocol']);?>&nbsp;</td>
									<td class="<?=$enable?"listr":"listrd";?>"><?=htmlspecialchars(empty($jail['src']) ? "*" : $jail['src']);?>&nbsp;</td>
									<td class="<?=$enable?"listr":"listrd";?>"><?=htmlspecialchars(empty($jail['srcport']) ? "*" : $jail['srcport']);?>&nbsp;</td>
									<td class="<?=$enable?"listr":"listrd";?>"><?=htmlspecialchars(empty($jail['dst']) ? "*" : $jail['dst']);?>&nbsp;</td>
									<td class="<?=$enable?"listr":"listrd";?>"><?=htmlspecialchars(empty($jail['dstport']) ? "*" : $jail['dstport']);?>&nbsp;</td>
									<td class="<?=$enable?"listrc":"listrcd";?>"><?=empty($jail['direction']) ? "*" : strtoupper($jail['direction']);?>&nbsp;</td>
									<td class="listbg"><?=htmlspecialchars($jail['desc']);?>&nbsp;</td>
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
			</td>
		</tr>
	</table>
	<?php include("formend.inc");?>
</form>
<?php include("fend.inc");?>
<?php print ($mess);