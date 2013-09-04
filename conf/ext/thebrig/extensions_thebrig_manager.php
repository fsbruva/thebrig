<?php
require("auth.inc");
require("guiconfig.inc");
require_once("ext/thebrig/lang.inc");
require_once("ext/thebrig/functions.inc");
	
if ( !isset( $config['thebrig']['rootfolder']) || !is_dir( $config['thebrig']['rootfolder']."work" )) {
	$input_errors[] = _THEBRIG_NOT_CONFIRMED;
} // end of elseif


// Display the page title, based on the constants defined in lang.inc
$pgtitle = array(_THEBRIG_EXTN , _THEBRIG_TITLE, "Manager");

// User has clicked a button
if ($_POST) {
	unset($input_errors);
	$pconfig = $_POST;
	$config_changed = false;		// Keep track if we need to re-write the config

	if ( isset($pconfig['update']) && $pconfig['update'] ){ 
		
		$langfile = file("/tmp/lang.inc");
		$version = preg_split ( "/VERSION_NBR, 'v/", $langfile[1]);
		$gitversion = 0 + substr($version[1],0,3);
		
		// This extracts the actual version from the lang.inc file
		$version = preg_split ( "/v/", _THEBRIG_VERSION_NBR);
		$myversion = 0 + substr($version[1],0,3); // Forces version to be a float
		// This checks to make sure the XML config concurs with the version of lang.inc, even if we already
		// have the most recent version
		if ( ($config['thebrig']['version'] != $myversion ) && ($gitversion == $myversion)){
			// We need to update the XML config to reflect reality
			$config['thebrig']['version'] = $myversion;
			$config_changed = true;
		} 
		elseif ( $gitversion > $myversion ) {
			// We want to make sure we can't let the user revert
			
		}
		
		
	} // end of "clicked update"
	
		
	// There are no input errors detected.
	if ( !$input_errors ){
		// User has selected to carry out the update
		if ( isset($pconfig['update'])) {

			//if ($gitversion == $myversion) {  $savemsg = " Your TheBrig run on current ".$myversion." version"; goto menu; }
		}
			// We have specified a new location for thebrig's installation, and it's valid, and we don't already have
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

?> <!-- This is the end of the first bit of html code -->

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
	<tr><td class="tabnavtbl">
		<ul id="tabnav">
			<li class="tabinact">
				<a href="extensions_thebrig.php"><span><?=_THEBRIG_JAILS;?></span></a>
			</li>
			<li class="tabact">
				<a href="extensions_thebrig_update.php"><span><?=_THEBRIG_UPDATES;?></span></a>
			</li>
			<li class="tabinact">
				<a href="extensions_thebrig_tarballs.php"><span><?=_THEBRIG_MAINTENANCE;?></span></a>
			</li>
			
		</ul>
	</td></tr>
	<tr><td class="tabnavtbl">
		<ul id="tabnav2">
			<li class="tabinact"><a href="extensions_thebrig_update.php"><span><?=_THEBRIG_UPDATER;?></span></a></li>
			<li class="tabinact"><a href="extensions_thebrig_ports.php"><span><?=_THEBRIG_PORTS;?></span></a></li>
			<li class="tabact">
				<a href="extensions_thebrig_manager.php" title="<?=gettext("Reload page");?>"><span><?=_THEBRIG_MANAGER;?></span></a>
			</li>
		</ul>
	</td></tr>

	<tr><td class="tabcont">
		<form action="extensions_thebrig_manager.php" method="post" name="iform" id="iform" onsubmit="return checkBeforeSubmit();">
		<table width="100%" border="0" cellpadding="6" cellspacing="0">
		<?php 
			// Download the most recent lang.inc, to see the version
			mwexec ( "fetch -o /tmp/lang.inc https://raw.github.com/fsbruva/thebrig/working/conf/ext/thebrig/lang.inc" ) ;
			$version = preg_split ( "/v/", _THEBRIG_VERSION_NBR);
			$myversion = 0 + substr($version[1],0,3);
			$langfile = file("/tmp/lang.inc");
			$version = preg_split ( "/VERSION_NBR, 'v/", $langfile[1]);
			$gitversion = 0 + substr($version[1],0,3);
			
			
			html_titleline(gettext("Update Availability")); 
			html_text($confconv, gettext("Current Status"),"The latest version on GitHub is: " . $gitversion . "<br /><br />Your version is: " . $myversion );
				// We have tag meaning we have downloaded & extracted a copy of the tree before - now we just want to update it.?>
			<tr>
			<td width="22%" valign="top" class="vncell">Update your installation&nbsp;</td>
			<td width="78%" class="vtable">
			<?=gettext("Click below to download and install the latest version.");?><br />
				<div id="submit_x">
					<input id="update" name="update" type="submit" class="formbtn" value="<?=gettext("Update");?>" onClick="return conf_handler();" /><br />
				</div>
			</td>
			</tr>
		<?php html_separator(); ?>

	</table><?php include("formend.inc");?>
</form>
</td></tr>
</table>
<?php 	include("fend.inc"); ?>