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
						
							<tr><td width="7%" class="listhdrlr"><?=gettext("Jail");?></td>
								<td width="15%" class="listhdrc"><?=gettext("Built");?></td>
								<td width="24%" class="listhdrc"><?=gettext("Status");?></td>
								<td width="5%" class="listhdrc"><?=gettext("ID");?></td>
								<td width="22%" class="listhdrc"><?=gettext("Jail ip");?></td>
								<td width="12%" class="listhdrc"><?=gettext("Jail hostname");?></td>
								<td width="22%" class="listhdrc"><?=gettext("Path to jail");?></td>
								
								<td width="5%" class="listhdrc"><?=gettext("Action");?></td>
							</tr>
							
							
					<?php // this line need for analystic from host
					$jail_root_dir = $config['thebrig']['rootfolder'];
					$jails =  $config['thebrig']['content'];
					if (empty($config['thebrig']['content'])) {goto exit1;} else{
					foreach ($jails as $n_jail):
							$file_id = "/var/run/jail_{$n_jail['jailname']}.id";
							If(is_file($file_id)) {
								$jail_id_1 = rtrim(file_get_contents($file_id));
								$jail_id_2 = explode(" ",$jail_id_1);
								$jail_id_3 = preg_grep("/jid=/",$jail_id_2 );
								
								$jail_id_4 = explode ("=",$jail_id_3[0]);
								$jail_id = $jail_id_4[1];
								
								
								$jail_vnet = preg_grep("/vnet/", $jail_id_2 );
								if (!empty($jail_vnet)) {
									$item[2] = "epair" . $n_jail['jailno']."|".$n_jail['epair_a_ip'] ; 
									/** May be better way extract ip with command "jexec proto ifconfig epair1b | grep inet | awk '{print $2}'" ??
									*/
								} else {
								unset($jail_id_5);
								
								$jail_ips = preg_grep("/.addr=/",$jail_id_2 );
								foreach ($jail_ips as $jail_ip) { $jail_id_3 = explode ("=",$jail_ip); $jail_id_5[] = $jail_id_3[1] ;	}
								$item[2] = implode (" as ", $jail_id_5);
								}
								unset($jail_id_5);
								$jail_hostname = preg_grep("/.hostname=/", $jail_id_2 );
								foreach ($jail_hostname as $jail_hostname) {
								 $jail_id_3 = explode ("=",$jail_hostname); $jail_id_5[] = $jail_id_3[1] ;	}
								$item[3] = implode (" ", $jail_id_5);
								
								unset($jail_id_5);
								$jail_path = preg_grep("/path=/", $jail_id_2 );
								foreach ($jail_path as $jail_path) {
								 $jail_id_3 = explode ("=",$jail_path); $jail_id_5[] = $jail_id_3[1] ;	}
								$item[4] = implode (" ", $jail_id_5);
								
								$jail_id_3 = explode ("=",$jail_id_2[3]);
								//$item[4] = $jail_id_3[1] ;
								
								
								$jail_ls = exec ("/usr/sbin/jls -j {$jail_id}");
								$jail_ls1 = preg_replace("/(\s){2,}/",' ',$jail_ls);
								//$item = explode (" ",$jail_ls1);
								$sleep_cmd = "ps -o jid,stat -ax | awk 'BEGIN{c=0}\$1~\"{$jail_id}\"&&(\$2~\"S\"||\$2~\"I\")&&\$2!~\"S[\+]\"{++c}END{print c}'";
								$runn_cmd = "ps -o jid,stat -ax | awk 'BEGIN{c=0}\$1~\"{$jail_id}\"&&(\$2~\"R\"||\$2~\"S[\+]\"){++c}END{print c}'";
								$sleep_cnt = exec ( $sleep_cmd ); 
								$runn_cnt = exec ( $runn_cmd);
								$total = intval($sleep_cnt) + intval($runn_cnt);
																
							}
							else {
								$jail_id = "stopped";
								$item[2] = "stopped" ;
								$item[3] = "stopped";
								$item[4] = "stopped";
							}
							
							?>
							<tr><td width="7%" valign="top" class="vncell"><center><?php print $n_jail['jailname'];?></center></td>
								<td width="15%" valign="top" class="vncell">
								<?php if (!is_dir( $n_jail['jailpath'] ."var/run")) {echo '<img src="'.'status_disabled.png'.'">';}
								else {
								echo '<img src="'.'status_enabled.png'.'">';
								if (is_dir( $n_jail['jailpath'] . "usr/ports/Mk")) {echo " + ports ";} else {echo "";}
								if (is_dir( $n_jail['jailpath'] . "usr/src/sys")) {echo "+ src";} else {echo "";}
								}
								?>								
								</td>
								<td width="24%" valign="top" class="vncell"><center><?php  
										If(is_file($file_id)): ?>
											<a title="<?=gettext("Running");?>"><img src="status_enabled.png" border="0" alt="" /></a>
											<?php echo "{$total} processes: {$runn_cnt} running, {$sleep_cnt} sleeping"; else:?>
											<a title="<?=gettext("Stopped");?>"><img src="status_disabled.png" border="0" alt="" /></a>
										<?php endif;?></center>
								</td>
								<td width="5%" valign= "top" class="vncell"><center><?php print $jail_id;?></center></td>
								<td width="22%" valign="top" class="vncell"><center><?php print $item[2];?> </center></td>
								<td width="12%" valign="top" class="vncell"><center><?php print $item[3];?></center></td>
								<td width="22%" valign="top" class="vncell"><center><?php print $item[4];?></center></td>
								 								
	<td width="5%" valign="top" class="vncellreq"><?php  
	if (!is_file($file_id)) 
	{ echo '<center><a href="extensions_thebrig.php?name='.$n_jail['jailname'].'&action=start"><img src="ext/thebrig/on_small.png" title="Jail start" border="0" alt="Jail start" /></a></center>';} 
	else { echo '<center><a href="extensions_thebrig.php?name='.$n_jail['jailname'].'&action=stop"><img src="ext/thebrig/off_small.png" title="Jail stop" border="0" alt="Jail stop" /></a></center>';} 
?>

										
								</td>
							</tr>
							<?php endforeach; }  
							exit1: gettext( " Please define jail" ) ; ?>
													
						</table>
</body>
</html>
