<?php
require("auth.inc");
require("guiconfig.inc");
require_once("ext/thebrig/lang.inc");
require_once("ext/thebrig/functions.inc");

if ( !isset( $config['thebrig']['rootfolder']) || !is_dir( $config['thebrig']['rootfolder']."work" )) {
	$input_errors[] = _THEBRIG_NOT_CONFIRMED;
} // end of elseif


// Display the page title, based on the constants defined in lang.inc
$pgtitle = array(_THEBRIG_EXTN , _THEBRIG_TITLE, _THEBRIG_UPDATER);

// we run the "prep" function to see if all the binaries we need are present in a jail (any jail). If they aren't we can't proceed
$brig_update_ready = thebrig_update_prep();
// Slight redefinition to make life a little easier
$brig_root = $config['thebrig']['rootfolder'] ;
$brig_update_db = $brig_root . "conf/db/freebsd-update/";

if (is_array ($config['thebrig']['content'])) { array_sort_key($config['thebrig']['content'], "jailno");
$a_jail = &$config['thebrig']['content'];}
$pconfig['updatecron'] = isset( $config['thebrig']['updatecron'] ) ;


$basedir_hash = exec ( "echo " . $a_jail[0]['jailpath'] . " | sha256 -q" );
if ( is_link ( $a_jail[0]['jailpath'] . "var/db/freebsd-update/" . $basedir_hash . "-rollback" ) ) {
	//$input_errors[]=$a_jail[0]['jailpath'] . "var/db/freebsd-update/" . $basedir_hash . "-rollback";
}
// User has clicked a button
if ($_POST) {
	unset($input_errors);
	$pconfig = $_POST;
	$config_changed = false;		// Keep track if we need to re-write the config
	$formjails = $_POST['formJails'];

	if ( isset($pconfig['save']) && $pconfig['save'] ){
		if ( isset ( $config['thebrig']['updatecron'] ) && !isset($_POST['updatecron'] )){
			//Cron is enabled in the existing config, but not on the form - we need to turn it off
			$config_changed=true;
			// This will search the existing cronjobs to find the appropriate index to place the portsnap command
			$i = 0;
			// Don't want to attempt array operations if there are no cronjobs.
			if ( is_array($config['cron'] ) && is_array( $config['cron']['job'] ) ) {
				for ($i; $i < count( $config['cron']['job'] ); $i++) {
					// This loops through all the cron job entries, and if it finds thebrig_ports_cron.php (placed by hand),
					// it will update the entry to reflect the new location by breaking out of the for loop at the correct index.
					if ( preg_match('/thebrig_update_cron\.php/', $config['cron']['job'][$i]['command']))
						unset($config['cron']['job'][$i]);
				} // end of for loop
			} // end of array if statment
		} // end of turning off cron
		elseif ( !isset ( $config['thebrig']['updatecron'] ) && isset($pconfig['updatecron'] ) ) {
			// Cron is disabled in the existing config, but selected on the form - we need to turn it on
			$config_changed=true;
			$brig_cron_job = array();
			// Build the cronjob we want to insert.
			$brig_cron_job['enable']="";
			$brig_cron_job['uuid']=uuid();
			$brig_cron_job['desc']="TheBrig autofetch of security updates.";
			$brig_cron_job['minute']=28;
			$brig_cron_job['hour']=1;
			$brig_cron_job['day']="";
			$brig_cron_job['month']="";
			$brig_cron_job['weekday']="";
			$brig_cron_job['all_mins']=0;
			$brig_cron_job['all_hours']=0;
			$brig_cron_job['all_days']=1;
			$brig_cron_job['all_months']=1;
			$brig_cron_job['all_weekdays']=1;
			$brig_cron_job['who']="root";
			$brig_cron_job['command'] = "/usr/local/bin/php-cgi " . $brig_root . "conf/bin/thebrig_update_cron.php";

			// This will search the existing cronjobs to find the appropriate index to place the portsnap command
			$i = 0;
			// Don't want to attempt array operations if there are no cronjobs.
			if ( is_array($config['cron'] ) && is_array( $config['cron']['job'] ) ) {
				for ($i; $i < count( $config['cron']['job'] ); $i++) {
					// This loops through all the cron job entries, and if it finds thebrig_ports_cron.php (placed by hand),
					// it will update the entry to reflect the new location by breaking out of the for loop at the correct index.
					if ( preg_match('/thebrig_update_cron\.php/', $config['cron']['job'][$i]['command']))
						break;
				} // end of for loop
			} // end of array if statment
			// update the value of the cron.
			$config['cron']['job'][$i] = $brig_cron_job;
		} // end of "turning on" cron
		// Store the fact that we have the cronjob. If we haven't made a change, this won't matter, because the write won't occur.
		$config['thebrig']['updatecron'] = isset( $pconfig['updatecron'] );
	} // end of "clicked submit"

	$base_selected = false;
	$base_activated = false;
	$template_selected = false;
	if (isset($pconfig['update_op']) && $pconfig['update_op'] ){
		if ( ! is_array( $formjails) )
			$input_errors[] = gettext("You need to select a jail to carry out operations on!");
		else {
			// We now need to check which jails were selected for update operations
			foreach ( $formjails as $job_jail ){
				// we need to search through the array of listed jails to see if any of them are thin jails
				$jid = array_search_ex($job_jail, $a_jail , 'uuid' );
				
				if ( $a_jail[$jid]['type'] == 'slim' && $job_jail != "00000000-0000-0000-0000-000000000000" ){
					$base_selected=true;
				}
				if ( $job_jail === "00000000-0000-0000-0000-000000000000")
					$template_selected=true;
			} // end of foreach jail
				
			foreach ( $a_jail as $my_jail ) {
				// now we need to prep for the actual updating
				// Perform the input validations!
				$basedir_hash = exec ( "echo " . $my_jail['jailpath'] . " | sha256 -q" );
				
				if  (FALSE === ($cnid = array_search($my_jail['uuid'], $formjails ))){
					// We didn't find the jail's uuid within the array of checked boxes, so we can exit this for loop
					continue;
				}
				// We are attempting to install updates that don't exist
				// Check for the existence of the -install link 
				if ( ! is_link ( $my_jail['jailpath'] . "var/db/freebsd-update/" . $basedir_hash . "-install" ) && $pconfig['update_op'] == "Install") {
				// We are attempting to rollback a jail that can't be
					$input_errors[] = "The jail named " . $my_jail['name'] . " does not have any updates ready for installation. <br>Please run 'fetch' for this jail.";
					break;
				}
				
				// Check for a rollback
				if ( ! is_link ( $my_jail['jailpath'] . "var/db/freebsd-update/" . $basedir_hash . "-rollback" ) && $pconfig['update_op'] == "Rollback") {
					// We are attempting to rollback a jail that can't be
					$input_errors[] = "The jail named " . $my_jail['name'] . " cannot have its installation rolled back. <br>Sorry.";
					break;
				}
				
				
				// This if gets entered the jail is checked and is fullsized
				if  ( (FALSE !== ($cnid = array_search($my_jail['uuid'], $formjails ))) && $my_jail['type'] == 'full' ){
					$basedir_list[]=$my_jail['jailpath'];
					$workdir_list[]=$my_jail['jailpath'] . "var/db/freebsd-update/";
					$conffile_list[] = $brig_root . "conf/freebsd-update.conf";
				} // end of selected full sized jail

				if ( $my_jail['type'] == 'slim' && $base_selected ){
					// We are looking at a slim jail, and we are supposed to upgrade all of them
					$basedir_list[]=$my_jail['jailpath'];
					$workdir_list[]=$my_jail['jailpath'] . "var/db/freebsd-update/";
					$conffile_list[] = $brig_root . "conf/freebsd-update_thin.conf";
				} // end of slim + basejail
			} // end of all jails foreach

			// We need to take care of the basejail!
			if ( $base_selected ){
				// First calculate the unique hash for the install/rollback links				
				$basedir_hash = exec ( "echo " . $basejail . " | sha256 -q" );
				// Check for the existence of the -install link - if it's there, and we want to install, then it's valid. Likewise,
				// if there is a rollback link, and we want to do that, then we should allow the action to take place.
				if (( is_link ( $brig_update_db . $basedir_hash . "-install" ) && $pconfig['update_op'] == "Install") 
					|| (is_link ( $my_jail['jailpath'] . "var/db/freebsd-update/" . $basedir_hash . "-rollback" ) && $pconfig['update_op'] == "Rollback") ){
					// We are attempting to rollback a jail that can't be
					$basejail = $config['thebrig']['basejail'];
					$basedir_list[]=$basejail['folder'];
					$workdir_list[]=$brig_update_db;
					$conffile_list[] = $brig_root . "conf/freebsd-update.conf";
					$base_activated = true;
				} // end of valid basejail checks
				
			} // end of basejail selected
			
			// We need to take care of the template jail!
			if ( $template_selected ){
				
				$template_dir = $config['thebrig']['template'];
				$basedir_list[]=$template_dir;
				$workdir_list[]=$template_dir . "var/db/freebsd-update/";
				$conffile_list[] = $brig_root . "conf/freebsd-update.conf";
				
				// First calculate the unique hash for the install/rollback links
				$basedir_hash = exec ( "echo " . $template_dir . " | sha256 -q" );
				// Check for the existence of the -install link
				if ( ! is_link ( $template_dir . "var/db/freebsd-update/" . $basedir_hash . "-install" ) && $pconfig['update_op'] == "Install") {
					// We are attempting to install a jail that doesn't have any updates pending
					$input_errors[] = "The TEMPLATE jail does not have any updates ready for installation. <br>Please run 'fetch' for this jail.";
				}
				
				// Check for a rollback
				if ( ! is_link ( $template_dir . "var/db/freebsd-update/" . $basedir_hash . "-rollback" ) && $pconfig['update_op'] == "Rollback") {
					// We are attempting to rollback a jail that can't be
					$input_errors[] = "The TEMPLATE jail cannot have its installation rolled back. <br>Sorry.";
				}
			}
			
			$response = 0;
			if ( count ( $input_errors ) == 0)
				$response = thebrig_update($basedir_list, $workdir_list , $conffile_list, $pconfig['update_op']);
			else 
				$input_errors[] = "No action was taken because of the above errors.";
			
			if ( $response == 1)
				$input_errors[] = "Something bad happened while attempting to prep for the update operation";
			elseif ( $response == 2 )
			$input_errors[] = "Something bad happened while attempting to return Nas4Free to its previous state";
		} // enf of else
	} // end of update_op

	// There are no input errors detected.
	if ( !$input_errors ){

		// Now we have to do the accounting to make sure the config reflects all we know about the installation, if we carried out a
		// "install" or "fetch & install" operation. We do this by trusting the tag if the "rollback" is present in the working directory,
		// indicating installation success
		if ( $pconfig['update_op'] == "Install" ) {
			// Need to cycle through all the jails (again)
			foreach ( $a_jail as &$my_jail ) {
				// This if gets entered the jail is checked and is fullsized, OR is thin and we selected to upgrade them all
				if  ( (FALSE !== ($cnid = array_search($my_jail['uuid'], $formjails )) && $my_jail['type'] == 'full' )  ||
						( $my_jail['type'] == 'slim' && $base_selected ) ){
					$config_changed = true;
					$basedir_hash = exec ( "echo " . $my_jail['jailpath'] . " | sha256 -q" );
					if ( is_link ( $my_jail['jailpath'] . "var/db/freebsd-update/" . $basedir_hash . "-rollback" )) {
						$my_tag_full = explode( "|", file_get_contents($my_jail['jailpath'] . "var/db/freebsd-update/tag")) ;
						$my_jail['base_ver'] = $my_tag_full[2] . "-p" . $my_tag_full[3];
						$my_jail['lib_ver'] = $my_tag_full[2] . "-p" . $my_tag_full[3];
						if ( is_dir( $my_jail['jailpath'] . "usr/src/sys") )
							$my_jail['src_ver'] = $my_tag_full[2] . "-p" . $my_tag_full[3];
						if ( file_exists( $my_jail['jailpath'] . "usr/share/doc/usd/contents.ascii.gz") )
							$my_jail['doc_ver'] = $my_tag_full[2] . "-p" . $my_tag_full[3];
						exec( "rm " . $my_jail['jailpath'] . "var/db/freebsd-update/files.*" );
					}
				} // end of updated jail
			} // end of all jails foreach

			// We need to take care of the basejail - but only if there were any actual updates/changes
			if ( $base_activated ){
				$basedir_hash = exec ( "echo " . $basejail['folder'] . " | sha256 -q" );
				if ( is_link ( $brig_update_db . $basedir_hash . "-rollback" )) {
					$config_changed = true;
					$my_tag_full = explode( "|", file_get_contents( $brig_update_db . "tag")) ;
					$config['thebrig']['basejail']['base_ver'] = $my_tag_full[2] . "-p" . $my_tag_full[3];
					$config['thebrig']['basejail']['lib_ver'] = $my_tag_full[2] . "-p" . $my_tag_full[3];
					if ( is_dir( $basejail['jailpath'] . "usr/src/sys") )
						$config['thebrig']['basejail']['src_ver'] = $my_tag_full[2] . "-p" . $my_tag_full[3];
					if ( file_exists( $basejail['jailpath'] . "usr/share/doc/usd/contents.ascii.gz") )
						$config['thebrig']['basejail']['doc_ver'] = $my_tag_full[2] . "-p" . $my_tag_full[3];
					exec( "rm " . $brig_update_db . "files.*" );
				}
			} // end base is selected
			
			if ( $template_selected ){
				$brig_temp_db = $config['thebrig']['template'] . "var/db/freebsd-update/";
				$basedir_hash = exec ( "echo " . $config['thebrig']['template'] . " | sha256 -q" );
				if ( is_link ( $brig_temp_db . $basedir_hash . "-rollback" )) {
					$config_changed = true;
					$my_tag_full = explode( "|", file_get_contents( $brig_temp_db . "tag")) ;
					$config['thebrig']['template_ver'] = $my_tag_full[2] . "-p" . $my_tag_full[3];
					exec( "rm " . $brig_temp_db . "files.*" );
				}
			}
			
		} // end of (install or fetch&install)

		if ( $config_changed ) {
			write_config();
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
$(document).ready(function() {
    $('#jail_name').bind('change', function() {
        //alert( "in here");
        var elements = $('tr.container').hide(); // hide all the elements
        var value = $(this).val();

        if (value.length) { // if somethings' selected
            elements.filter('.' + value).show(); // show the ones we want
            $('#' + value).show();
        }
    }).trigger('change');
});

function checkBeforeSubmit() {
	if ( document.iform.beenSubmitted )
		return false;
	else {
		document.iform.beenSubmitted = true;
		return document.iform.beenSubmitted;
	}
}

function jail_change() {
	var x=document.iform.jail_name.selectedIndex;
	var y=document.iform.jail_name.options;

	switch (document.iform.jail_name.selectedIndex) {
		case 0:
			showElementById('official_tr','show');
			showElementById('homegrown_tr','show');
			break;
		case 1:
			showElementById('official_tr','hide');
			showElementById('homegrown_tr','hide');
			break;
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
				<li class="tabinact"><a href="extensions_thebrig_log.php"><span><?=gettext("Log");?></span></a></li>
			</ul>
		</td>
	</tr>
	<tr>
		<td class="tabnavtbl">
			<ul id="tabnav2">
				<li class="tabact"><a href="extensions_thebrig_update.php"
					title="<?=gettext("Reload page");?>"><span><?=_THEBRIG_UPDATER;?> </span>
				</a></li>
				<li class="tabinact"><a href="extensions_thebrig_ports.php"><span><?=_THEBRIG_PORTS;?>
					</span> </a></li>
				<li class="tabinact"><a href="extensions_thebrig_manager.php"><span><?=_THEBRIG_MANAGER;?>
					</span> </a>
				</li>
			</ul>
		</td>
	</tr>

	<tr>
		<td class="tabcont">
			<form action="extensions_thebrig_update.php" method="post"
				name="iform" id="iform" onsubmit="return checkBeforeSubmit();">
				<table width="100%" border="0" cellpadding="6" cellspacing="0">
		<?php if ( $brig_update_ready == 2 ) {
			// The necessary binaries for all the update tasks could not be found in any jail.
			html_titleline(gettext("ERROR!"));
			html_text($confconv, gettext("Unable to Continue"),"In order to begin configuration and update tasks for freebsd-update, you need to have at least one configured and filled jail!");
		}
		elseif ( $brig_update_ready == 1 ){
		// The necessary binaries for all the update tasks could not be copied into thebrig's working directory.
			html_titleline(gettext("ERROR! ... Like, a HUGE error!"));
			html_text($confconv, gettext("Unable to Continue"),"For some reason, Nas4Free cannot be prepared for update tasks. This is highly suspect, as the copying was attempted by root.");
		}
		else {
			// All the binaries were found, and able to be moved into thebrig's working directory.

			// Obtain information about the latest update, if we can
			$my_arch = exec ( "uname -m");
			$my_rel = exec ( "uname -r");
			$my_rel_cut = exec ("uname -r | cut -d- -f1-2" ) ;     // Obtain the current kernel release
			exec ( "fetch -o /tmp/latest.ssl http://update.freebsd.org/" . $my_rel_cut . "/". $my_arch ."/latest.ssl");
			exec ( "fetch -o /tmp/pub.ssl http://update.freebsd.org/" . $my_rel_cut . "/". $my_arch ."/pub.ssl");
			// Uses openssl to verify the "latest.ssl" snapshot using the portsnap public key, and then
			// converts that from an epoch second to a usable date.
			exec (  $brig_root . "conf/bin/openssl rsautl -pubin -inkey "
			. "/tmp/pub.ssl -verify < "
			. "/tmp/latest.ssl  > /tmp/update.tag" );
			$EOL_date= exec( "date -j -r `cat /tmp/update.tag  | cut -f 6 -d '|'`");
			$tag_rel = exec ( "cat /tmp/update.tag | cut -f 3 -d '|'");
			$tag_patch = exec ( "cat /tmp/update.tag | cut -f 4 -d '|'");
			//exec ("rm /tmp/latest.ssl"); 	// Get rid of the latest tag
			//exec ("rm /tmp/pub.ssl"); 	// Get rid of the latest tag
			//exec ( "rm /tmp/update.tag");


			html_titleline(gettext("Update"));
			html_text($confconv, gettext("Current Status"),gettext("The latest version on the FTP server is: ") . $tag_rel . "-p" . $tag_patch . "<br /><br />" . gettext("The update on the FTP server is valid until: ") . $EOL_date );
			?>
					<tr>
						<td width="15%" valign="top" class="vncell"><?=gettext("Cronjob");?>
						</td>
						<td width="85%" class="vtable"><input name="updatecron"
							type="checkbox" id="updatecron" value="yes"
							<?php if (!empty($pconfig['updatecron'])) echo "checked=\"checked\""; ?> />
							<?=_THEBRIG_UPDATE_CRON?> <input id="save" name="save"
							type="submit" class="formbtn" value="<?=gettext("Save");?>"
							onClick="return conf_handler();" />
						</td>
					</tr>

	
			<?php 	// We have tag meaning we have downloaded & extracted a copy of the tree before - now we just want to update it.
			html_separator();
			html_titleline(gettext("Jails"));?>
					<tr>
						<td width="5%" valign="top" class="vncell"><?=gettext("Update Operations");?>
						</td>
						<td width="95%" class="vtable"><?=gettext( "Please choose which of the following jails should have the update action carried out:" )?><br />
							<br />
							<table width="100%" border="0" cellpadding="0" cellspacing="0">
								<tr>
									<td width="4%" class="listhdrlr">&nbsp;</td>
									<td width="10%" class="listhdrc"><?=gettext("Name");?></td>
									<td width="12%" class="listhdrr"><?=gettext("Last Check for Updates");?></td>
									<td width="19%" class="listhdrc"><?=gettext("Downloaded Version");?>
									</td>
									<td width="30%" class="listhdrc"><?=gettext("Files Affected by Pending Update");?>
									</td>
									<td width="12%" class="listhdrr"><?=gettext("Installed Version");?>
									</td>
								</tr>
			<?php $k = 0; for( $k; $k < count ( $a_jail ) ; $k ++ ):
				if ( file_exists ( $a_jail[$k]['jailpath'] . "var/db/freebsd-update/tag")){
				// Extract the most recent tag's date, and convert from Unix epoch to a readable date
					$tag_full = explode( "|", file_get_contents($a_jail[$k]['jailpath'] . "var/db/freebsd-update/tag")) ;
					$tag_version = $tag_full[2] . "-p" . $tag_full[3];
					$tag_date = date ( "M d Y" ,filemtime( $a_jail[$k]['jailpath'] . "var/db/freebsd-update/tag" ));
					// Check if there are any pending updates (file lists have been created)
					if ( file_exists ( $a_jail[$k]['jailpath'] . "var/db/freebsd-update/files.updated" ) ) {
						$updated_contents[$k] = rtrim ( file_get_contents($a_jail[$k]['jailpath'] . "var/db/freebsd-update/files.updated"));
						$added_contents[$k] = rtrim(file_get_contents($a_jail[$k]['jailpath'] . "var/db/freebsd-update/files.added"));
						$removed_contents[$k] = rtrim(file_get_contents($a_jail[$k]['jailpath'] . "var/db/freebsd-update/files.removed"));
						if ( $a_jail[$k]['type'] == 'slim' ){
						// we're talking about a slim jail here, we should check if this is the first slim we've prepped for
						// If so, we need ot define a few "constant" values for the duration of this for loop
							if ( !isset( $base_update_contents) && file_exists( $brig_update_db . "files.updated" ) ) {
								// Pull in the lists of files to be added, updated and removed for the base jail
								$base_updated_contents =  rtrim(file_get_contents($brig_update_db . "files.updated"));
								$base_added_contents =  rtrim(file_get_contents($brig_update_db . "files.added"));
								$base_removed_contents =  rtrim(file_get_contents($brig_update_db . "files.removed"));
							}
							// Concatenate the list for the base jail to the list for the slim jail. Only use a "\n" to glue them together
							// if there is an actual list of files for both the thin jail and the basejail
							$updated_contents[$k] .= ((strlen($updated_contents[$k]) >= 1 && strlen($base_updated_contents) >=1 ) ? "\n" : "" ). $base_updated_contents;
							$deleted_contents[$k] .= ((strlen($deleted_contents[$k]) >= 1 && strlen($base_deleted_contents) >=1 ) ? "\n" : "" ). $base_deleted_contents;
							$added_contents[$k] .= ((strlen($added_contents[$k]) >= 1 && strlen($base_added_contents) >=1 ) ? "\n" : "" ). $base_added_contents;
						} // End of slim jail shenanigans
						// Count how many files by exploding the list and counting the arrays. If the list has only a "\n", then we should
						// say that list has no files.
						$added_count = ( strlen ($added_contents[$k]) <= 1 ) ? 0 : count( explode( "\n" , $added_contents[$k]));
						$updated_count = ( strlen ($updated_contents[$k]) <= 1 ) ? 0 : count( explode( "\n" , $updated_contents[$k]) );
						$removed_count = ( strlen ($removed_contents[$k]) <= 1 ) ? 0 :count( explode( "\n" , $removed_contents[$k]) );
						$file_summary = $added_count . " files added, " . $updated_count. " files updated and  " . $removed_count . " files removed."  ;
					} // End of there exists file lists
					else
						// No file lists exist
						$file_summary ="No updates pending";
				} // end of there is a tag
				else {
					$tag_version = "No Tag";
					$tag_date = "N/A";
					$file_summary ="No updates pending";
				} ?>
								<tr>
									<td class="<?=$enable?"listlr":"listlrd";?>"><input
										type="checkbox" name="formJails[]"
										value=<?php echo "{$a_jail[$k]['uuid']}";?>>&nbsp;</td>
									<td class="<?=$enable?"listr":"listrd";?>"><?=htmlspecialchars($a_jail[$k]['jailname']);?>&nbsp;</td>
									<td class="<?=$enable?"listrc":"listrcd";?>"><?=htmlspecialchars($tag_date);?>&nbsp;</td>
									<td class="<?=$enable?"listrc":"listrcd";?>"><?=htmlspecialchars($tag_version );?>&nbsp;</td>
									<td class="<?=$enable?"listr":"listrd";?>"><?=htmlspecialchars( $file_summary);?>&nbsp;</td>
									<td class="listbg"><?=htmlspecialchars($a_jail[$k]['base_ver']);?>&nbsp;</td>
								</tr>
				<?php endfor; 
				if ( file_exists ( $config['thebrig']['template'] . "/var/db/freebsd-update/tag")){
				// Extract the most recent tag's date, and convert from Unix epoch to a readable date
					$tag_full = explode( "|", file_get_contents($config['thebrig']['template'] . "var/db/freebsd-update/tag")) ;
					$tag_version = $tag_full[2] . "-p" . $tag_full[3];
					$tag_date = date ( "M d Y" ,filemtime( $config['thebrig']['template'] . "var/db/freebsd-update/tag" ));
					// Check if there are any pending updates (file lists have been created)
					if ( file_exists ( $config['thebrig']['template'] . "var/db/freebsd-update/files.updated" ) ) {
						$updated_contents[$k] = rtrim ( file_get_contents($config['thebrig']['template'] . "var/db/freebsd-update/files.updated"));
						$added_contents[$k] = rtrim(file_get_contents($config['thebrig']['template'] . "var/db/freebsd-update/files.added"));
						$removed_contents[$k] = rtrim(file_get_contents($config['thebrig']['template'] . "var/db/freebsd-update/files.removed"));
						// Count how many files by exploding the list and counting the arrays. If the list has only a "\n", then we should
						// say that list has no files.
						$added_count = ( strlen ($added_contents[$k]) <= 1 ) ? 0 : count( explode( "\n" , $added_contents[$k]));
						$updated_count = ( strlen ($updated_contents[$k]) <= 1 ) ? 0 : count( explode( "\n" , $updated_contents[$k]) );
						$removed_count = ( strlen ($removed_contents[$k]) <= 1 ) ? 0 :count( explode( "\n" , $removed_contents[$k]) );
						$file_summary = $added_count . " files added, " . $updated_count. " files updated and  " . $removed_count . " files removed."  ;
					} // End of there exists file lists
					else
						// No file lists exist
						$file_summary ="No updates pending";
				} // end of there is a tag
				else {
					$tag_version = "No Tag";
					$tag_date = "N/A";
					$file_summary ="No updates pending";
				}?>
							<tr>
									<td class="<?=$enable?"listlr":"listlrd";?>"><input
										type="checkbox" name="formJails[]"
										value=<?php echo "00000000-0000-0000-0000-000000000000";?>>&nbsp;</td>
									<td class="<?=$enable?"listr":"listrd";?>"><?=htmlspecialchars("TEMPLATE");?>&nbsp;</td>
									<td class="<?=$enable?"listrc":"listrcd";?>"><?=htmlspecialchars($tag_date);?>&nbsp;</td>
									<td class="<?=$enable?"listrc":"listrcd";?>"><?=htmlspecialchars($tag_version );?>&nbsp;</td>
									<td class="<?=$enable?"listr":"listrd";?>"><?=htmlspecialchars( $file_summary);?>&nbsp;</td>
									<td class="listbg"><?=htmlspecialchars($config['thebrig']['template_ver']);?>&nbsp;</td>
								</tr>
							</table> <br> <b><?=gettext("Please note: ")?> </b> <?=gettext("Selecting a thin jail for any update operation will mandate that all other thin jails be updated as well.")?>
					
					</tr>
					<tr>
						<td width="22%" valign="top" class="vncell">Fetch the
							Updates&nbsp;</td>
						<td width="78%" class="vtable"><?=gettext("Click below to perform an 'on-demand' fetch for the selected jails.");?><br>
							<div id="submit_x">
								<input id="fupdate" name="update_op" type="submit"
									class="formbtn" value="<?=gettext("Fetch");?>"
									onClick="return conf_handler();" /><br />
							</div>
						</td>
					</tr>

					<tr>
						<td width="22%" valign="top" class="vncell">Install the
							Updates&nbsp;</td>
						<td width="78%" class="vtable"><?=gettext("Click below to install the pending update to the selected jail(s).");?><br />
							<div id="submit_x">
								<input id="update" name="update_op" type="submit"
									class="formbtn" value="<?=gettext("Install");?>"
									onClick="return conf_handler();" /><br />
							</div>
						</td>
				<?php html_separator();
				html_titleline(gettext("Update Details"));
				// Build an array with the keys as the jail uuid, and with the value as the jail's name
				$jail_names = array(); if (is_array($a_jail)) {
				foreach ( $a_jail as $one_jail){
					$jail_names[$one_jail['uuid']]=$one_jail['jailname'];} }
				$jail_names['00000000-0000-0000-0000-000000000000']="TEMPLATE";
				html_combobox("jail_name", gettext("Jail Name"), $pconfig['type'], $jail_names, gettext("Choose jail to view more information regarding a pending update."), "","","");?>
				<?php $i = 0; for( $i; $i < count ( $a_jail ) ; $i ++ ): 
					$added_list = $added_contents; ?>
					<tr id="<?echo $a_jail[$i]['uuid']; ?>" class="container">
						<td width="5%" valign="top" class="vncell"><?=gettext("Impact of Update");?>
						</td>
						<td width="95%" class="vtable">
							<table width="100%" border="0" cellpadding="0" cellspacing="0">
								<tr>
									<td width="28%" class="listhdrlr"><?=gettext("Added");?></td>
									<td width="28%" class="listhdrr"><?=gettext("Updated");?></td>
									<td width="28%" class="listhdrr"><?=gettext("Removed");?></td>
								</tr>

								<tr>
									<td class="<?=$enable?"listlr":"listlrd";?>"><?=nl2br($added_contents[$i]);?>&nbsp;</td>
									<td class="<?=$enable?"listr":"listrd";?>"><?=nl2br($updated_contents[$i]);?>&nbsp;</td>
									<td class="<?=$enable?"listr":"listrd";?>"><?=nl2br($removed_contents[$i]);?>&nbsp;</td>
								</tr>

							</table>
						</td>
					</tr>
					<?php endfor;?>

					<tr id="<?echo '00000000-0000-0000-0000-000000000000'; ?>" class="container">
						<td width="5%" valign="top" class="vncell"><?=gettext("Impact of Update");?>
						</td>
						<td width="95%" class="vtable">
							<table width="100%" border="0" cellpadding="0" cellspacing="0">
								<tr>
									<td width="28%" class="listhdrlr"><?=gettext("Added");?></td>
									<td width="28%" class="listhdrr"><?=gettext("Updated");?></td>
									<td width="28%" class="listhdrr"><?=gettext("Removed");?></td>
								</tr>

								<tr>
									<td class="<?=$enable?"listlr":"listlrd";?>"><?=nl2br($added_contents[$i]);?>&nbsp;</td>
									<td class="<?=$enable?"listr":"listrd";?>"><?=nl2br($updated_contents[$i]);?>&nbsp;</td>
									<td class="<?=$enable?"listr":"listrd";?>"><?=nl2br($removed_contents[$i]);?>&nbsp;</td>
								</tr>

							</table>
						</td>
					</tr>
				</table>

				<?php } // end of brigready else?>

				<!-- This is the row beneath the title -->



				<?php include("formend.inc");?>
			</form>
		</td>
	</tr>
</table>
<?php 	include("fend.inc"); ?>
