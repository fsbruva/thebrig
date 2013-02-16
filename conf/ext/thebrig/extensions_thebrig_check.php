<?php
require("auth.inc");
require("guiconfig.inc");
require_once("ext/thebrig/lang.inc");
require_once("ext/thebrig/functions.inc");
$pgtitle = array(_THEBRIG_EXTN , _THEBRIG_TITLE);

//=========================================================================================================================================================
// The entirety of this next section (all the way to the /head) is copied out of the fbegin.inc file
// normally used to construct the larger portion of the nas4free framing, including all the title bars and whatnot
//=========================================================================================================================================================
function gentitle($title) {
	$navlevelsep = "|"; // Navigation level separator string.
	return join($navlevelsep, $title);
}

function genhtmltitle($title) {
	return system_get_hostname() . " - " . gentitle($title);
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=system_get_language_code();?>" lang="<?=system_get_language_code();?>">
<head>
	<title><?=htmlspecialchars(genhtmltitle($pgtitle));?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?=system_get_language_codeset();?>" />
	<meta http-equiv="Content-Script-Type" content="text/javascript" />
	<meta http-equiv="Content-Style-Type" content="text/css" />
	<?php if (isset($pgrefresh) && $pgrefresh):?>
	<meta http-equiv="refresh" content="<?=$pgrefresh;?>" />
	<?php endif;?>
	<link href="gui.css" rel="stylesheet" type="text/css" />
	<link href="navbar.css" rel="stylesheet" type="text/css" />
	<link href="tabs.css" rel="stylesheet" type="text/css" />	
	<script type="text/javascript" src="js/jquery.min.js"></script>
	<script type="text/javascript" src="js/gui.js"></script>
<?php
	if (isset($pglocalheader) && !empty($pglocalheader)) {
		if (is_array($pglocalheader)) {
			foreach ($pglocalheader as $pglocalheaderv) {
		 		echo $pglocalheaderv;
				echo "\n";
			}
		} else {
			echo $pglocalheader;
			echo "\n";
		}
	}
	
	//=========================================================================================================================================================
	// nearly the end of the borrowed bits
	//=========================================================================================================================================================
	?>
</head>


<body>
<table width="100%" border="0" cellpadding="5" cellspacing="0">
						
							<tr><td width="10%" class="listhdrlr"><?=gettext("Jail");?></td>
								<td width="12%" class="listhdrc"><?=gettext("Built");?></td>
								<td width="5%" class="listhdrc"><?=gettext("Status");?></td>
								<td width="5%" class="listhdrc"><?=gettext("ID");?></td>
								<td width="15%" class="listhdrc"><?=gettext("Jail ip");?></td>
								<td width="15%" class="listhdrc"><?=gettext("Jail hostname");?></td>
								<td width="18%" class="listhdrc"><?=gettext("Path to jail");?></td>
								
								<td width="20%" class="listhdrc"><?=gettext("Action");?></td>
							</tr>
							
							
							<?php // this line need for analystic from host
$jail_root_dir = $config['thebrig']['rootfolder'];
$list = exec("ls -F {$jail_root_dir} | grep / | sed 's/\///g' | grep -v work | grep -v conf > /tmp/tempfile"); 
$jails =  file("/tmp/tempfile");  
$remtemp = exec ("rm /tmp/tempfile"); ?>
							<?php foreach ($jails as $n_jail):?>
							<tr><td width="10%" valign="top" class="vncellreq"><center><?php print $n_jail;?></center></td>
								<td width="12%" valign="top" class="vncellreq">
								<?php $n2_jail = rtrim($n_jail); if (!is_dir( (($jail_root_dir ."/" . $n2_jail . "/" ."var/run")))) {echo '<img src="'.'status_disabled.png'.'">';} 
								else {
								echo '<img src="'.'status_enabled.png'.'">';
								if (is_dir($jail_root_dir ."/" . $n2_jail . "/usr/ports/Mk")) {echo " + ports ";} else {echo "";}
								if (is_dir($jail_root_dir ."/" . $n2_jail . "/usr/src/sys")) {echo "+ src";} else {echo "";}
								}
								?>								
								</td>
								<td width="5%" valign="top" class="vncellreq"><center><?php $n1_jail = rtrim($n_jail); $file_id = "/var/run/jail_{$n1_jail}.id"; 
										If(is_file($file_id)): ?>
											<a title="<?=gettext("Running");?>"><img src="status_enabled.png" border="0" alt="" /></a>
											<?php else:?>
											<a title="<?=gettext("Stopped");?>"><img src="status_disabled.png" border="0" alt="" /></a>
										<?php endif;?></center>
								</td>
						
								<td width="5%" valign= "top" class="vncellreq"><center><?php $n2_jail = rtrim($n_jail); $file_id = "/var/run/jail_{$n2_jail}.id";
										If(is_file($file_id)) { $jail_id = file_get_contents($file_id); print $jail_id; } else {echo "stopped";}; ?></center></td>
								<td width="15%" valign="top" class="vncellreq"><center><?php $n2_jail = rtrim($n_jail); $file_id = "/var/run/jail_{$n2_jail}.id";
										If(is_file($file_id)) { $jail_ls = exec ("/usr/sbin/jls -j '{$n2_jail}'"); 
											$jail_ls1 = preg_replace("/(\s){2,}/",' ',$jail_ls); 
											$item = explode (" ",$jail_ls1); print $item[2]; } else {echo "stopped";}; ?> </center></td>
								<td width="15%" valign="top" class="vncellreq"><center><?php $n3_jail = rtrim($n_jail); $file_id = "/var/run/jail_{$n3_jail}.id";
										If(is_file($file_id)) { $jail_ls = exec ("/usr/sbin/jls -j '{$n2_jail}'"); 
											$jail_ls1 = preg_replace("/(\s){2,}/",' ',$jail_ls); 
											$item = explode (" ",$jail_ls1); print $item[3]; } else {echo "stopped";}; ?></center></td>
								<td width="18%" valign="top" class="vncellreq"><center><?php 
								echo $jail_root_dir ."/" . $n2_jail;
								 ?></center></td>
								
	<td width="20%" valign="top" class="vncellreq"><center><input type="submit" class="formbtn" <?php If(is_file($file_id)): ?><name="jailstop" value="stop" ><?php else:?><name="jailstart" value="start" ><?php endif;?> 
										</center>
								</td>
							</tr><?php endforeach;?>
													
						</table>
</body>
</html>