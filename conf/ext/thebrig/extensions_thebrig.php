<?php
/*
   File:  extensions_thebrig.php
  
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
require_once("ext/thebrig/functions.inc");


$pgtitle = array(_THEBRIG_EXTN,_THEBRIG_TITLE);

if ( !isset( $config['thebrig']['rootfolder']) || !is_dir( $config['thebrig']['rootfolder']."work" )) {
	$input_errors[] = _THEBRIG_NOT_CONFIRMED;
} 
else {
	//$pglocalheader=array( );
}
/*
if (is_ajax()) {
	$jailinfo = get_jailinfo();
	render_ajax($jailinfo);
} */

if (isset($_GET['apply']) && $_GET['apply']){
	mwexec('touch /tmp/in_get');
	}


if ($_POST) {
	// insert into pconfig changes
	$pconfig = $_POST;
	$config['thebrig']['thebrig_enable'] = isset ( $_POST['thebrig_enable'] ) ? true : false ;	
	$config['thebrig']['gl_statfs'] =  $_POST['gl_statfs'] ;
	If ($_POST['compress'] == "yes") { $config['thebrig']['compress'] = "yes"; }
	write_config();
	
	$retval = 0;
	// This checks to see if any webgui changes require a reboot, and create rc.conf.local
	if ( !file_exists($d_sysrebootreqd_path) ){
	if(isset($config['thebrig']['content']) ) {
		//write_rcconflocal();
		write_defs_rules();
		write_jailconf ();
		// OR the return value from the attempt to process the notification
		$retval |= updatenotify_process("thebrig", "thebrig_process_updatenotification");
		// Lock the config
		if ( isset($config['thebrig']['thebrig_enable']) ) { 
			file_put_contents($brig_test, "brig is enabled and retval is:{$retval} \n");
			$retval |= rc_update_rcconf("thebrig", "enable");
			} 
		else { 
			$retval |= rc_update_rcconf("thebrig", "disable"); 
		}
		//config_lock();
		//$retval |= rc_update_service("jail"); // This need be checked.  For jail this way no good
		 //$retval |= rc_update_rcconf($name,$state);
		// Unlock the config
		// config_unlock();
	}}
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
// sent to page data from config.xml
$rootfolder = $config['thebrig']['rootfolder'];
$pconfig['parastart'] = isset( $config['thebrig']['parastart'] ) ? true : false ;
$pconfig['thebrig_enable'] = isset($config['thebrig']['thebrig_enable']) ? true : false ; 
if ($config['thebrig']['compress']  == "yes" ) $pconfig['compress'] = "yes"; else unset(  $pconfig['compress']);
if (isset ($config['thebrig']['gl_statfs']) )  { $pconfig['gl_statfs']  =  $config['thebrig']['gl_statfs']; } else { $pconfig['gl_statfs']  = 2; }

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
				mwexec ( "/etc/rc.d/thebrig stop " . $jail2delete['jailname']);
				if ( $config['thebrig']['compress'] == "yes")  {
				if ( $jail2delete['type'] === "slim") {
					mwexec("tar -cf " . $config['thebrig']['rootfolder'] . "work/backup_" . $jail2delete['jailname'] . "_" . $timestamp . ".tar -C " . $jail2delete['jailpath'] . " ./" );
					mwexec("tar -rf " . $config['thebrig']['rootfolder'] . "work/backup_" . $jail2delete['jailname'] . "_" . $timestamp . ".tar -X basejail/ -C " . $config['thebrig']['basejail']['folder'] . " ./" );
					mwexec("xz -S .txz " . $config['thebrig']['rootfolder'] . "work/backup_" . $jail2delete['jailname'] . "_" . $timestamp . ".tar" );
				}
				else {
					mwexec("tar -czf " . $config['thebrig']['rootfolder'] . "work/backup_" . $jail2delete['jailname'] . "_" . $timestamp . ".txz -C " . $jail2delete['jailpath'] . " ./" );
				}
				}
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
	
	write_jailconf ();
	write_defs_rules ();
	return $retval;
}


include("fbegin.inc");?>
<?php if ($input_errors) print_input_errors($input_errors);?>
<?php if ($errormsg) print_error_box($errormsg);?>
<?php if ($savemsg) print_info_box($savemsg);?>
<!----- This make "live table" ------>
<script language="JavaScript">
$(document).ready(function(){
	var gui = new GUI;
	gui.recall(500, 2000, 'extensions_thebrig_ajax.php', null, function(data) {
		if ( typeof(data) !== 'undefined' ) {
		for ( idx=1; idx<= data.rowcount;  idx++ ) {
			$('#ajaxjailname'+idx).text(data.name[idx] );
			if (typeof(data.built[idx]) !== 'undefined') {
				var value1 = data.built[idx];
				if (value1 !== 'ON') { 
					$('#ajaxjailbuiltimg'+ idx).attr('src', 'status_disabled.png'); 
					$('#ajaxjailbuiltimg'+ idx).attr('title', 'Template?'); 
				} else {
					$('#ajaxjailbuiltimg'+ idx).attr('src', 'status_enabled.png');
					$('#ajaxjailbuiltimg'+ idx).attr('title', 'Build');
				}			
			}
			if (typeof(data.builtports[idx]) !== 'undefined') {
				var value1 = data.builtports[idx];
				if (value1 == 'OFF') { 					
					$('#ajaxjailbuiltports'+ idx).attr('text', ''); 
				} else {
					
					$('#ajaxjailbuiltports'+ idx).text(' + ports');
				}
			}
			if (typeof(data.builtsrc[idx]) !== 'undefined') {
				var value1 = data.builtsrc[idx];				
				if (value1 == 'OFF') { 					
					$('#ajaxjailbuiltsrc'+ idx).attr('text', ''); 
				} else {
					$('#ajaxjailbuiltsrc'+ idx).text(' + src');
				}			
			}
			if (typeof(data.status[idx]) !== 'undefined') {
				var value1 = data.status[idx];				
				if (value1 != 'OFF') {
						$('#ajaxjailstatus'+ idx).text(data.status[idx]);
						$('#ajaxjailstatusimg'+ idx).attr('src', 'status_enabled.png');
						$('#ajaxjailstatusimg'+ idx).attr('title', 'Created'); 
					} else {
						$('#ajaxjailstatus'+ idx).text("");
						$('#ajaxjailstatusimg'+ idx).attr('src', 'status_disabled.png'); 
						//$('#ajaxjailstatusimg'+ idx).attr('title', 'Stopped'); 
					}
			}
			if (typeof(data.id[idx]) !== 'undefined') {
				var value1 = data.id[idx];
				
				if (value1 != 'OFF') {
						$('#ajaxjailid'+ idx).text(data.id[idx]);
					} else {
						$('#ajaxjailid'+ idx).text("");
					}
			}
			if (typeof(data.id[idx]) !== 'undefined') {
				var value1 = data.ip[idx];
				
				if (value1 != 'OFF') {
						$('#ajaxjailip'+ idx).text(data.ip[idx]);
					} else {
						$('#ajaxjailip'+ idx).text("Stopped");
					}
			}
			if (typeof(data.hostname[idx]) !== 'undefined') {
				var value1 = data.hostname[idx];
				
				if (value1 != 'OFF') {
						$('#ajaxjailhostname'+ idx).text(data.hostname[idx]);
					} else {
						//$('#ajaxjailhostnameimg'+ idx).attr('src', 'status_disabled.png'); 
						$('#ajaxjailhostname'+ idx).text("Stopped");
						$('#ajaxjailhostnameimg'+ idx).attr('title', 'Stopped'); 
					}
			}
			if (typeof(data.path[idx]) !== 'undefined') {
				var value1 = data.path[idx];
				
				if (value1 != 'OFF') {
						$('#ajaxjailpath'+ idx).text(data.path[idx]);
					} else {
						//$('#ajaxjailpathimg'+ idx).attr('src', 'status_disabled.png'); 
						$('#ajaxjailpathimg'+ idx).attr('title', 'Stopped'); 
						$('#ajaxjailpath'+ idx).text("Stopped");
					}
			}
			if (typeof(data.id[idx]) !== 'undefined') {
				var value1 = data.id[idx];				
				if (value1 != 'OFF') {
						$('#ajaxjailcmdimg'+ idx).attr('src', 'ext/thebrig/off_small.png');
						$('#ajaxjailcmdimg'+ idx).attr('title', 'Stop Jail'); 
						$('#ajaxjailcmdimg'+ idx).attr('class', 'jail_stop'); 
					} else {
						$('#ajaxjailcmdimg'+ idx).attr('src', 'ext/thebrig/on_small.png'); 
						$('#ajaxjailcmdimg'+ idx).attr('title', 'Start Jail'); 
						$('#ajaxjailcmdimg'+ idx).attr('class', 'jail_start'); 
					}
			}
		}


	}});
});
jQuery.fn.extend({
  live: function( types, data, fn ) {
          if( window.console && console.warn ) {
           console.warn( "jQuery.live is deprecated. Use jQuery.on instead." );
          }

          jQuery( this.context ).on( types, this.selector, data, fn );
          return this;
        }
});
$(".jail_stop").live( 'click' , function(){
	var $tr = $(this).closest('tr');
	var name = $tr.children(':eq(0)').text();
	brig_action(name , 'onestop');
});	
	
$(".jail_start").live( 'click', function(){
	var $tr = $(this).closest('tr');
	var name = $tr.children(':eq(0)').text();
	brig_action(name , 'onestart');
});	

function brig_action(name, act) {
    return $.ajax({
        url : 'extensions_thebrig_ajax.php',
        type: 'GET',
        data: {
			id: name,
			action: act,
            }
    });
}

function jail_delete() {
	var compress = $("[name='compress']").val();
	if ( compress === "yes" ){
		return confirm('<?=gettext("Do you really want to delete this jail? An archive will be created just in case. It can be removed later.");?>');
	}
	else {
		return confirm('<?=gettext("The selected jail will be removed, with no backup created. If you want to keep a backup, please visit the Rudimentary Config Page.");?>');
	}	
}
function disable_buttons() {
	document.iform.Submit.disabled = true;
	document.iform.submit();
	}
</script>
<!--------  This is view ------->

<table width="100%" border="0" cellpadding="0" cellspacing="0" >
	<tr><td class="tabnavtbl">
		<ul id="tabnav">
			<li class="tabact"><a href="extensions_thebrig.php"><span><?=_THEBRIG_JAILS;?></span></a></li>
			<?php If (!empty($config['thebrig']['content'])) { 
			$thebrigupdates=_THEBRIG_UPDATES;
			echo "<li class=\"tabinact\"><a href=\"extensions_thebrig_update.php\"><span>{$thebrigupdates}</span></a></li>";
			} else {} ?>
			<li class="tabinact"><a href="extensions_thebrig_tarballs.php"><span><?=_THEBRIG_MAINTENANCE;?></span></a></li>
			<li class="tabinact"><a href="extensions_thebrig_log.php"><span><?=_THEBRIG_LOG;?></span></a></li>
					</span> </a>
				</li>
		</ul>
	</td></tr>
	<tr>
		<td class="tabcont">
		<form action="extensions_thebrig.php" method="post" name="iform" id="iform" enctype="multipart/form-data">
			<?php if (updatenotify_exists("thebrig")) print_config_change_box();?>

			<table width="100%" border="0" cellpadding="6" cellspacing="0">
			      <?php html_titleline(_THEBRIG_ONLINETABLE_TITLE);?>
				<tr><td colspan='2' valign='top' >
					<?php if( isset( $config['thebrig']['rootfolder'])==false): ?>
							<a title=<?=gettext("Configure TheBrig please first");?>
					<?php elseif( isset( $config['thebrig']['content'])==false): ?>
							<a title=<?=gettext("Configure at least one jail first");?>				
					<?php else: ?>
								<table id = 'onlinetable' width="100%" border="0" cellpadding="5" cellspacing="0">
						
									<tr><td width="7%"  class="listhdrlr" ><?=_THEBRIG_TABLE1_TITLE1;?></td>
										<td width="15%" class="listhdrc"><?=_THEBRIG_ONLINETABLE_TITLE1;?></td>
										<td width="24%" class="listhdrc"><?=_THEBRIG_ONLINETABLE_TITLE5;?></td>
										<td width="5%" class="listhdrc"><?=_THEBRIG_ONLINETABLE_TITLE6;?></td>
										<td width="22%" class="listhdrc"><?=_THEBRIG_ONLINETABLE_TITLE2;?></td>
										<td width="12%" class="listhdrc"><?=_THEBRIG_ONLINETABLE_TITLE3;?></td>
										<td width="22%" class="listhdrc"><?=_THEBRIG_ONLINETABLE_TITLE4;?></td>
										<td width="5%" class="listhdrc"><?=_THEBRIG_ONLINETABLE_TITLE7;?></td>
									</tr>
						<?php 
						//foreach( $config['thebrig']['content'] as $n_jail): 
						for ($k=1; $k <= count($config['thebrig']['content']); $k++) : ?>					
									<tr name='myjail<?=$n_jail['jailno']; ?>' id='myjail<?=$n_jail['jailno']; ?>'>
										<td width="7%" valign="top" class="listlr" name="ajaxjailname<?=$k; ?>"  id="ajaxjailname<?=$k; ?>" >  </td>
									    <td width="15%" valign="top" class="listr" name="ajaxjailbuilt<?=$k; ?>" ><span><img id="ajaxjailbuiltimg<?=$k; ?>" src="status_disabled.png" border="0" alt="template?" /> </span><span id="ajaxjailbuiltports<?=$k; ?>"></span><span id="ajaxjailbuiltsrc<?=$k; ?>"></span> </td>
									    <td width="24%" valign="top" class="listrc" name="ajaxjailstatus<?=$k; ?>"  > <span><img id="ajaxjailstatusimg<?=$k; ?>" src="status_disabled.png" border="0" alt="Stopped" /> </span><span id="ajaxjailstatus<?=$k; ?>"></span></td>
									    <td width="5%" valign= "top" class="listrc" name="ajaxjailid<?=$k; ?>" id="ajaxjailid<?=$k; ?>"></td>
									    <td width="22%" valign="top" class="listrc" name="ajaxjailip<?=$k; ?>" id="ajaxjailip<?=$k; ?>">  <img id="ajaxjailipimg<?=$k; ?>" src="status_disabled.png" border="0" alt="Stopped" /></td>
									    <td width="12%" valign="top" class="listrc" name="ajaxjailhostname<?=$k; ?>" id="ajaxjailhostname<?=$k; ?>"> <img id="ajaxjailhostnameimg<?=$k; ?>" src="status_disabled.png" border="0" alt="Stopped" /></td>
									    <td width="22%" valign="top" class="listrc" name="ajaxjailpath<?=$k; ?>" id="ajaxjailpath<?=$k; ?>"><img id="ajaxjailpathimg<?=$k; ?>" src="status_disabled.png" border="0" alt="Stopped" /> </td>
										<td width="5%" valign="top" class="listrd" name="ajaxjailcmd<?=$k; ?>" id="ajaxjailcmd<?=$k; ?>"><span><img id="ajaxjailcmdimg<?=$k; ?>" class="jail_start" src="ext/thebrig/on_small.png" border="0" alt="Jail start" /> </span></td>	
										</td>
									 </tr>
			<?php endfor; ?>
								</table>
					<?php endif;?>
				</td></tr>
			<?php html_separator();  ?>
			<?php html_titleline(_THEBRIG_TABLE1_TITLE);?>
					<tr><td width="15%" valign="top" class="vncell"><?=_THEBRIG_JAILS;?></td>
						<td width="85%" class="vtable">
							<table width="100%" border="0" cellpadding="0" cellspacing="0">
								<tr>
									<td width="3%" class="listhdrlr">&nbsp;</td>
									<td width="5%" class="listhdrr"><?=_THEBRIG_TABLE1_TITLE1;?></td>
									<td width="12%" class="listhdrr"><?=_THEBRIG_TABLE1_TITLE2;?></td>
									<td width="9%" class="listhdrr"><?=_THEBRIG_TABLE1_TITLE3;?></td>
									<td width="16%" class="listhdrr"><?=_THEBRIG_TABLE1_TITLE4;?></td>
									<td width="10%" class="listhdrr"><?=_THEBRIG_TABLE1_TITLE5;?></td>
									<td width="15%" class="listhdrr"><?=_THEBRIG_TABLE1_TITLE6;?></td>
									<td width="14%" class="listhdrr"><?=_THEBRIG_TABLE1_TITLE7;?></td>
									<td width="10%" class="list"></td>
								</tr>
									<?php foreach ($a_jail as $jail):?>
								<?php $notificationmode = updatenotify_get_mode("thebrig", $jail['uuid']);?>
								<tr>
									<td class="<?=$enable?"listr":"listlrd";?>"><?=htmlspecialchars(empty($jail['jailno']) ? "*" : $jail['jailno']);?>&nbsp;</td>
									<td class="<?=$enable?"listr":"listrd";?>"><?=htmlspecialchars($jail['jailname']);?>&nbsp;</td>
									<?php if (is_array($jail['allowedip'])) $networks = implode(",", $jail['allowedip']); ?>
									<td class="<?=$enable?"listr":"listrd";?>"><?=htmlspecialchars(empty($jail['allowedip']) ? "*" : $networks);?>&nbsp;</td>  
									<td class="<?=$enable?"listlr":"listlrd";?>"><?=htmlspecialchars(isset($jail['enable']) ? "YES" : "NO");?></td>
									<td class="<?=$enable?"listr":"listrd";?>"><?=htmlspecialchars(isset($jail['zfs_datasets']) ? "USED" : "NO") ;?>&nbsp;</td>
									<td class="<?=$enable?"listrc":"listrcd";?>"><?=htmlspecialchars($jail['jailname'] . "." . $config['system']['domain']);?>&nbsp;</td>
									<td class="<?=$enable?"listr":"listrd";?>"><?=htmlspecialchars($jail['jailpath']);?>&nbsp;</td>
									<td class="listbg"><?=htmlspecialchars($jail['desc']);?>&nbsp;</td>
									<?php if (UPDATENOTIFY_MODE_DIRTY != $notificationmode):?>
									<td valign="middle" nowrap="nowrap" class="list">
										<a href="extensions_thebrig_edit.php?uuid=<?=$jail['uuid'];?>"><img src="e.gif" title="<?=gettext("Edit jail");?>" border="0" alt="<?=gettext("Edit jail");?>" /></a>&nbsp;
										<a href="extensions_thebrig.php?act=del&amp;uuid=<?=$jail['uuid'];?>&amp;name=<?=$jail['jailname'];?>" onclick="return jail_delete();"><img src="x.gif" title="<?=gettext("Delete jail");?>" border="0" alt="<?=gettext("Delete jail");?>" /></a>&nbsp;
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
						<td width="15%" valign="top" class="vncell"><?=_THEBRIG_GLOBALS_TITLE;?></td>
						<td width="85%" class="vtable">
		<input name='thebrig_enable' type='checkbox' class='formfld' id='thebrig_enable' value="" <?php if (!empty($pconfig['thebrig_enable'])) echo "checked=\"checked\""; ?>  />&nbsp;<?=_THEBRIG_GLOBALS_ENABLE?><br />
		<select name='gl_statfs' class='formfld' id='gl_statfs' ><option value='2' <?php if (2 == $pconfig['gl_statfs']) echo "selected"; ?> >2</option><option value='1' <?php if (1 == $pconfig['gl_statfs']) echo "selected"; ?> >1</option><option value='0' <?php if (0 == $pconfig['gl_statfs']) echo "selected"; ?> >0</option></select>
		<span class='vexpl'><?=_THEBRIG_GLOBALS_STATFS?> </span>
	</td></tr></table>
				<div id="submit">
					<input name="Submit" type="submit" class="formbtn" value="<?=_THEBRIG_SAVE_BUTTON;?>" />
					 <input name="compress" type="hidden" value="<?if ($pconfig['compress'] == "yes") echo "yes"; ?>" />
				</div>
				
	</table>
	
</td></tr>
<?php include("formend.inc");?>
</form>
</table>
<?php include("fend.inc"); ?>

