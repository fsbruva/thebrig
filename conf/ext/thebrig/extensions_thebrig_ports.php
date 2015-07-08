<?php
/*
	file: extensions_thebrig_ports.php
	
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

if ( !isset( $config['thebrig']['rootfolder']) || !is_dir( $config['thebrig']['rootfolder']."work" )) {
	$input_errors[] = _THEBRIG_NOT_CONFIRMED;
} // end of elseif


// Display the page title, based on the constants defined in lang.inc
$pgtitle = array(_THEBRIG_EXTN , _THEBRIG_TITLE, _THEBRIG_PORTS);

// we run the "prep" function to see if all the binaries we need are present in a jail (any jail). If they aren't we can't proceed
$brig_update_ready = thebrig_update_prep();

if ( $brig_update_ready == 0 ){
	// The operations carried out in thebrig_update_prep will only return 0 if there is at least one complete jail,
	// and the necessary binaries for update operations were able to be copied. If there are no jails present, then the function
	// will return 2

	// Slight redefinition to make life a little easier
	$brig_root = $config['thebrig']['rootfolder'] ;
	$brig_port_db = $brig_root . "conf/db/ports/";

	// See my above comments for why the if() that used to live here is no longer needed
	array_sort_key($config['thebrig']['content'], "jailno");
	$a_jail = &$config['thebrig']['content'];
	$pconfig['portscron'] = isset( $config['thebrig']['portscron'] ) ;
}

// User has clicked a button
if ($_POST) {
	unset($input_errors);
	$pconfig = $_POST;
	$config_changed = false;		// Keep track if we need to re-write the config
	$formjails = $_POST['formJails'];
	if ( !is_array($formjails) )
		$formjails=array();			// We need this so a subsequent foreach doesn't error out

	if ( isset($pconfig['save']) && $pconfig['save'] ){
		if ( isset ( $config['thebrig']['portscron'] ) && !isset($_POST['portscron'] )){
			//Cron is enabled in the existing config, but not on the form - we need to turn it off
			$config_changed=true;
			// This will search the existing cronjobs to find the appropriate index to place the portsnap command
			$i = 0;
			// Don't want to attempt array operations if there are no cronjobs.
			if ( is_array($config['cron'] ) && is_array( $config['cron']['job'] ) ) {
				for ($i; $i < count( $config['cron']['job'] ); $i++) {
					// This loops through all the cron job entries, and if it finds thebrig_ports_cron.php (placed by hand),
					// it will update the entry to reflect the new location by breaking out of the for loop at the correct index.
					if ( 1 === preg_match('/thebrig_ports_cron\.php/', $config['cron']['job'][$i]['command']))
						unset($config['cron']['job'][$i]);
				} // end of for loop
			} // end of array if statment
				
		}
		elseif ( !isset ( $config['thebrig']['portscron'] ) && isset($pconfig['portscron'] ) ) {
			// Cron is disabled in the existing config, but selected on the form - we need to turn it on
			$config_changed=true;
			$brig_cron_job = array();
			// Build the cronjob we want to insert.
			$brig_cron_job['enable']="";
			$brig_cron_job['uuid']=uuid();
			$brig_cron_job['desc']="TheBrig autofetch of the ports tree snapshot.";
			$brig_cron_job['minute']=37;
			$brig_cron_job['hour']=2;
			$brig_cron_job['day']="";
			$brig_cron_job['month']="";
			$brig_cron_job['weekday']="";
			$brig_cron_job['all_mins']=0;
			$brig_cron_job['all_hours']=0;
			$brig_cron_job['all_days']=1;
			$brig_cron_job['all_months']=1;
			$brig_cron_job['all_weekdays']=1;
			$brig_cron_job['who']="root";
			$brig_cron_job['command'] = "/usr/local/bin/php-cgi " . $brig_root . "conf/bin/thebrig_ports_cron.php";

			// This will search the existing cronjobs to find the appropriate index to place the portsnap command
			$i = 0;
			// Don't want to attempt array operations if there are no cronjobs.
			if ( is_array($config['cron'] ) && is_array( $config['cron']['job'] ) ) {
				for ($i; $i < count( $config['cron']['job'] ); $i++) {
					// This loops through all the cron job entries, and if it finds thebrig_ports_cron.php (placed by hand),
					// it will update the entry to reflect the new location by breaking out of the for loop at the correct index.
					if ( 1 === preg_match('/thebrig_ports_cron\.php/', $config['cron']['job'][$i]['command']))
						break;
				} // end of for loop
			} // end of array if statment
			// update the value of the cron.
			$config['cron']['job'][$i] = $brig_cron_job;
				
		}
		// Store the fact that we have the cronjob. If we haven't made a change, this won't matter, because the write won't occur.
		$config['thebrig']['portscron'] = isset( $pconfig['portscron'] );

		// We now need to check which jails are going to share the portstree
		foreach ( $a_jail as &$my_jail ){
			// Update the config by setting or unsetting the jail value
			// Remove the link & directory - or add it within each jail
			if ( isset( $my_jail['ports'] ) ) {
				// The jail is currently configured to have ports
				if  (FALSE === ($cnid = array_search($my_jail['uuid'], $formjails ))){
					// We didn't find the jail's uuid within the array of checked boxes, which means we need to "turn off" ports
					unset ( $my_jail['ports'] ) ;
					$config_changed=true;
					//$pconfig['ports'] = $my_jail['ports'];
					// Unmount the ports, and remove the directory
					exec ( "umount -f " . $my_jail['jailpath'] . "usr/ports" );
					exec ( "rm -r " . $my_jail['jailpath'] . "usr/ports");
					// Get rid of the non-standard make file
					exec ( "rm " . $my_jail['jailpath'] . "etc/make.conf");
					// Replace the backup we made (if we made one)
					if ( file_exists($my_jail['jailpath'] . "etc/make.conf.bak"))
						exec("mv " . $my_jail['jailpath'] . "etc/make.conf.bak " . $my_jail['jailpath'] . "etc/make.conf");
				} // end of "found no match"
			} // end of if this jail has ports
			else{
				// The jail we're looking at is not configured to have ports
				if  (FALSE !== ($cnid = array_search($my_jail['uuid'], $formjails ))){
					// We found a jail in the list of "clicked" jails, so we need to turn on ports
					$my_jail['ports'] = true;
					$config_changed=true;
					// Create directory, remove anything within the directory, and mount the ports
					exec ( "mkdir " . $my_jail['jailpath'] . "usr/ports");
					exec ( "rm -r " . $my_jail['jailpath'] . "usr/ports/*");
					exec ( "mount -t nullfs -r " . $brig_root . "conf/ports " . $my_jail['jailpath'] . "usr/ports");
					// Make backup of existing make file for later
					if ( file_exists($my_jail['jailpath'] . "etc/make.conf"))
						exec("mv " . $my_jail['jailpath'] . "etc/make.conf " . $my_jail['jailpath'] . "etc/make.conf.bak");
					// Move the non-standard make file
					exec ( "cp " . $brig_root . "conf/make.conf " . $my_jail['jailpath'] . "etc/");
				} // end of "found a match within the checkbox list"
			} // end of else
		} // end of foreach jail
	} // end of "clicked submit"


	if (isset($pconfig['port_op']) && $pconfig['port_op'] ){
		// We want to fetch AND extract the tree for the first time
		$response = thebrig_portsnap($brig_root . "conf/ports", $brig_port_db , $brig_root . "conf/portsnap.conf", $pconfig['port_op']);
		if ( $response == 1)
			$input_errors[] = _THEBRIG_NOPREPARE_UPDATE;
		elseif ( $response == 2 )
		$input_errors[] = _THEBRIG_NORETURN_UPDATE;
	}

	// There are no input errors detected.
	if ( !$input_errors ){
		// We have specified a new location for thebrig's installation, and it's valid, and we don't already have
		if ( $config_changed ) {
			write_config ();
			write_jailconf ();
		}
		// Whatever we did, we did it successfully
		$retval = 0;
		$savemsg = get_std_save_message($retval);
	} // end of no input errors
} // end of POST

// Uses the global fbegin include
include("fbegin.inc");

// This will evaluate if there were any input errors from prior to the user clicking "save"
if ( $input_errors ) {
	print_input_errors( $input_errors );
}
// This will alert the user to unsaved changes, and prompt the changes to be saved.
elseif ($savemsg) print_info_box($savemsg);

?>
<!-- This is the end of the first bit of html code -->

<!-- This function allows the pages to render the buttons impotent whilst carrying out various functions -->
<script language="JavaScript">
function checkBeforeSubmit() {
	if ( document.iform.beenSubmitted )
		return false;
	else {
		document.iform.beenSubmitted = true;
		return document.iform.beenSubmitted;
	}
}

function conf_handler() {
	if ( document.iform.beenSubmitted )
		alert('Please wait for the previous operation to complete!!');
	else{
		return confirm('The selected operation will be completed. Please do not click any other buttons.');
	}
}
</script>

<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td class="tabnavtbl">
			<ul id="tabnav">
				<li class="tabinact"><a href="extensions_thebrig.php"><span><?=_THEBRIG_JAILS;?>
					</span> </a>
				</li>
				<li class="tabact"><a href="extensions_thebrig_ports.php"><span><?=_THEBRIG_UPDATES;?>
					</span> </a>
				</li>
				<li class="tabinact"><a href="extensions_thebrig_tarballs.php"><span><?=_THEBRIG_MAINTENANCE;?>
					</span> </a>
				</li>
				<li class="tabinact"><a href="extensions_thebrig_log.php"><span><?=_THEBRIG_LOG;?></span></a></li>
			</ul>
		</td>
	</tr>
	<tr>
		<td class="tabnavtbl">
			<ul id="tabnav2">
				<li class="tabinact"><a href="extensions_thebrig_update.php"><span><?=_THEBRIG_UPDATER;?>
					</span> </a></li>
				<li class="tabact"><a href="extensions_thebrig_ports.php"
					title="<?=gettext("Reload page");?>"><span><?=_THEBRIG_PORTS;?> </span>
				</a></li>
				<li class="tabinact"><a href="extensions_thebrig_manager.php"><span><?=_THEBRIG_MANAGER;?></span></a></li>
			</ul>
		</td>
	</tr>

	<tr>
		<td class="tabcont">
			<form action="extensions_thebrig_ports.php" method="post"
				name="iform" id="iform" onsubmit="return checkBeforeSubmit();">
				<table width="100%" border="0" cellpadding="6" cellspacing="0">
					<?php if ( $brig_update_ready == 2 ) {
			// The necessary binaries for all the update tasks could not be found in any jail.
			html_titleline(_THEBRIG_ERROR);
			html_text($confconv, _THEBRIG_UNABLECONTINUE,_THEBRIG_UNABLECONTINUE_EXPL1);
		}
		elseif ( $brig_update_ready == 1 ){
			// The necessary binaries for all the update tasks could not be copied into thebrig's working directory.
			html_titleline(_THEBRIG_HUGEERROR);
			html_text($confconv, _THEBRIG_UNABLECONTINUE,_THEBRIG_UNABLECONTINUE_EXPL2);
		}
		else {
			// All the binaries were found, and able to be moved into thebrig's working directory.
			if ( file_exists ( $brig_port_db . "tag")) {
				// Extract the most recent tag's date, and convert from Unix epoch to a readable date
				$tagdate= exec( "date -j -r `cut -f 2 -d '|' < " . $brig_port_db . "tag`");
			}
			else {
				$tagdate = _THEBRIG_TAG_NEVER;
			}
			// Connectivity test
			$connected = @fsockopen("portsnap.freebsd.org", 80); 
			if ( $connected ) {
				fclose($connected);
				exec ( "fetch -o /tmp/portsnap_latest.ssl http://portsnap.freebsd.org/latest.ssl");
				exec ( "fetch -o /tmp/portsnap_pub.ssl http://portsnap.freebsd.org/pub.ssl");
			}	
			// Uses openssl to verify the "latest.ssl" snapshot using the portsnap public key, and then 
			// converts that from an epoch second to a usable date.
			if ( file_exists ("/tmp/portsnap_latest.ssl") && file_exists("/tmp/portsnap_pub.ssl") ) {
				$most_date= exec( "date -j -r `" . $brig_root . "conf/bin/openssl rsautl -pubin -inkey "
					. "/tmp/portsnap_pub.ssl -verify < "
					. "/tmp/portsnap_latest.ssl | cut -f 2 -d '|'`");
				exec ("rm /tmp/portsnap_latest.ssl"); 	// Get rid of the latest tag
				exec ("rm /tmp/portsnap_pub.ssl"); 	// Get rid of the latest tag
			}
			else {
				$most_date = "Unknown - Do we have Internet?";
			}
			// We need to check if we have ever extracted a snapshot successfully
			if ( file_exists ( $brig_root. "conf/ports/.portsnap.INDEX")) {
				$extractdate= date ("D M d H:i:s T Y" ,filemtime( $brig_root. "conf/ports/.portsnap.INDEX" )); // extract and convert the timestamp
			}
			else {
				$extractdate = _THEBRIG_TAG_NEVER;
			}
			
			html_titleline(_THEBRIG_PORTSTREE_TITLE); 
			html_text($confconv, _THEBRIG_PORTCURRENTSTATUS,_THEBRIG_PORTDOWNLOADABLE . $most_date . "<br /><br />" . _THEBRIG_PORTDOWNLOADED . $tagdate . "<br /><br />" . _THEBRIG_PORTAPPLIED . $extractdate );
			// We have never gotten a tag before - we need to fetch and extract a copy first
			if ( $tagdate === _THEBRIG_TAG_NEVER){ ?>
					<tr>
						<td width="22%" valign="top" class="vncell"><?=_THEBRIG_FETCH_FIRST1;?>&nbsp;</td>
						<td width="78%" class="vtable"><?=_THEBRIG_FETCH_FIRST2;?><br />
							<div id="submit_x">
								<input id="finstall" name="port_op" type="submit"
									class="formbtn" value="<?=_THEBRIG_FETCHEXTRACT_BUTTON;?>"
									onClick="return conf_handler();" /><br />
							</div></td>
					</tr>
					<?php }	
			else {
				// We have tag meaning we have downloaded & extracted a copy of the tree before - now we just want to update it.?>
					<tr>
						<td width="22%" valign="top" class="vncell"><?=_THEBRIG_FETCH_UPDATE1;?>&nbsp;</td>
						<td width="78%" class="vtable"><?=_THEBRIG_FETCH_UPDATE2; ?><br />
							<div id="submit_x">
								<input id="fupdate" name="port_op" type="submit" class="formbtn"
									value="<?=_THEBRIG_FETCHUPDATE_BUTTON;?>"
									onClick="return conf_handler();" /><br />
							</div>
						</td>
					</tr>

					<tr>
						<td width="22%" valign="top" class="vncell"><?=_THEBRIG_UPDATETREE1;?>&nbsp;</td>
						<td width="78%" class="vtable"><?=_THEBRIG_UPDATETREE2;?><br />
							<div id="submit_x">
								<input id="update" name="port_op" type="submit" class="formbtn"
									value="<?=_THEBRIG_UPDATE_BUTTON;?>"
									onClick="return conf_handler();" /><br />
							</div>
						</td>
					</tr>
					<?php 
			html_separator();
			html_titleline(_THEBRIG_JAILS);?>
					<tr>
						<td width="15%" valign="top" class="vncell"><?=_THEBRIG_ENABLE_PORTSTREE1;?></td>
						<td width="85%" class="vtable"><?=_THEBRIG_ENABLE_PORTSTREE2;?><br /><br />
							<table width="100%" border="0" cellpadding="0" cellspacing="0">
								<tr>
									<td width="4%" class="listhdrlr">&nbsp;</td>
									<td width="10%" class="listhdrr"><?=_THEBRIG_TABLE1_TITLE1;?></td>
									<td width="12%" class="listhdrr"><?=_THEBRIG_TABLE1_TITLE5;?></td>
									<td width="19%" class="listhdrr"><?=_THEBRIG_ONLINETABLE_TITLE4;?></td>
									<td width="19%" class="listhdrr"><?=_THEBRIG_TABLE1_TITLE7;?></td>
									<td width="5%" class="list"></td>
								</tr>
								<?php $k = 0; for( $k; $k < count ( $a_jail ) ; $k ++ ) { ?>
								<tr>
									<td class="<?=$enable?"listlr":"listlrd";?>"><input
										type="checkbox" name="formJails[]"
										value=<?php echo "{$a_jail[$k]['uuid']}";?>
										<?php  echo ( isset( $a_jail[$k]['ports'] ) ? "checked=\"checked\"" :  "" ) ; ?>>&nbsp;</td>
									<td class="<?=$enable?"listr":"listrd";?>"><?=htmlspecialchars($a_jail[$k]['jailname']);?>&nbsp;</td>
									<td class="<?=$enable?"listrc":"listrcd";?>"><?=htmlspecialchars($a_jail[$k]['jailname'] . "." . $config['system']['domain']);?>&nbsp;</td>
									<td class="<?=$enable?"listr":"listrd";?>"><?=htmlspecialchars($a_jail[$k]['jailpath']);?>&nbsp;</td>
									<td class="listbg"><?=htmlspecialchars($a_jail[$k]['desc']);?>&nbsp;</td>

								</tr>
								<?php } ?>
							</table> <br> <br> <?=_THEBRIG_PORTSNOTE;?>
					
					
					<tr>
						<td width="15%" valign="top" class="vncell"><?=_THEBRIG_PORTCRON;?></td>
						<td width="85%" class="vtable"><input name="portscron" type="checkbox" id="portscron" value="yes"
							<?php if (!empty($pconfig['portscron'])) echo "checked=\"checked\""; ?> />
							<?=_THEBRIG_PORT_CRON;?><br />
						</td>
					</tr>

				</table>
				<div id="submit_x">
					<input id="save" name="save" type="submit" class="formbtn"
						value="<?=gettext("Save");?>" onClick="return conf_handler();" />
				</div>
				<?php } // end of tagdate never
		} // end of brigready else?>

				<!-- This is the row beneath the title -->

				<?php include("formend.inc"); ?>
			</form>
		</td>
	</tr>
</table>
<?php include("fend.inc"); ?> 
