<?php
require("auth.inc");
require("guiconfig.inc");
require_once("ext/thebrig/lang.inc");
require_once("ext/thebrig/functions.inc");
	
// If thebrig array does not exist or is not a valid array within the global config, then create a blank one.
if ( !isset($config['thebrig']) || !is_array($config['thebrig'])) {
	$config['thebrig'] = array();
}

// attempt to extract the rootfolder from the global config
$pconfig['rootfolder'] = $config['thebrig']['rootfolder'];
$pconfig['template'] = $config['thebrig']['template'] ;
$pconfig['basejail'] = $config['thebrig']['basejail']['folder'] ;

// Display the page title, based on the constants defined in lang.inc
$pgtitle = array(_THEBRIG_EXTN , _THEBRIG_TITLE, isset($config['thebrig']['version']) ? "version:".$config['thebrig']['version'] : "First start");

// This determines if there are any thin jails (type = slim), which means we shouldn't
// relocate the basejail. We also need to check and make sure no jails currently live 
// within thebrig's root folder. 
$base_ro = false;
$brig_jails = false;
if ( !isset($_POST['remove'] ) && is_array(  $config['thebrig']['content'] ) ) {
	foreach ( $config['thebrig']['content'] as $jail ){
		if ( $jail['type'] === 'slim' )
			$base_ro = true;
		if ( preg_match ( "#" . $config['thebrig']['rootfolder'] . "#" , $jail['jailpath']) )
			$brig_jails=true;
	}
}


// This checks to see if the XML config has no rootfolder for thebrig, but does have the remnants of a 
// successful installation. The original installation script creates the /tmp/thebrig.tmp file, and puts the 
// path inside that tmp file. Thus, this if statement is entered when the original install was completed, but this
// is the first time the page has been loaded since then. 
if ( ( !isset( $config['thebrig']['rootfolder'] ) ) && file_exists( '/tmp/thebrig.tmp' ) ) {
	// This next line extracts the root folder from the install artifact (trimed to remove trailing CR/LF)
	$config['thebrig']['rootfolder'] = rtrim( file_get_contents('/tmp/thebrig.tmp') );
	// Ensure there is a / after the folder name
	if ( $config['thebrig']['rootfolder'][strlen($config['thebrig']['rootfolder'])-1] != "/")  {
		$config['thebrig']['rootfolder'] = $config['thebrig']['rootfolder'] . "/";
	}
	
	// The next line propagates the the page's config data (the text box) with the extracted value
	$pconfig['rootfolder'] = $config['thebrig']['rootfolder'];

	// If the thing pulled from the .tmp file is an actual directory, do some stuff
	if ( is_dir( $config['thebrig']['rootfolder'] ) ) {
		write_config();		// write the config so it survives reboot
		thebrig_populate( $config['thebrig']['rootfolder'] , $config['thebrig']['rootfolder'] );
		unlink_if_exists("/tmp/thebrig.tmp");  // deletes the .tmp file (if it was there)
	}
	else {
		// There was a .tmp file, but it didn't have a valid folder within it, which is tough to do, because
		// all of this php code has to come from someplace.... alert the user and ask them to re-run the install
		$input_errors[] = _THEBRIG_NOT_INSTALLED;
	} // end else
} // end if (no folder), but a tmp file
// This indicates that the xml config doesn't know where the folder is, and there is no temp file
// created as part of an installation. Alert the user and ask them to re-run the install
// The other issue is that somehow a bad root folder value got written to the XML config.
else if ( !isset( $config['thebrig']['rootfolder']) || !is_dir ( $config['thebrig']['rootfolder'])) {
	$input_errors[] = _THEBRIG_NOT_INSTALLED;
} // end of elseif

// Look in the existing folder to see if there are files we can't (or don't want to) move.
$base_search = glob( $config['thebrig']['rootfolder'] . "basejail/bin/*" );
$template_search = glob ( $config['thebrig']['rootfolder'] . "template/bin/*" );

// User has clicked a button
if ($_POST) {
	unset($input_errors);
	$pconfig = $_POST;
	 
	if ( $pconfig['remove'] ) {
		// we want to remove thebrig
		thebrig_unregister();
		// Browse back to the main page
		header("Location: /");
		exit;
	}
	// Complete all root folder error checking.
	// Convert root folder after filechoicer
	if ( $pconfig['rootfolder'][strlen($pconfig['rootfolder'])-1] != "/")  {
		$pconfig['rootfolder'] = $pconfig['rootfolder'] . "/";
	}
	
	// This first check to make sure that the supplied folder actually exists. If it does not
	// then the user should be alerted. No changes will be made.
	if ( !is_dir( $pconfig['rootfolder'] ) && !isset($pconfig['remove']) ) {
		$input_errors[] = _THEBRIG_NONEXISTENT_FOLDER;
	}
	// We also need to be able to write to the folder, so lets check that. The webgui runs as root, so this 
	// condition is highly suspect, but needs to be covered.
	elseif ( !is_writable( $pconfig['rootfolder'] ) && !isset($pconfig['remove']) ){
		$input_errors[] = _THEBRIG_NOTWRITABLE_FOLDER;
	}
	// We also need to see if there is enough space on the target disk.
	elseif ( disk_free_space ( $pconfig['rootfolder'] ) < 200000000 && !isset($pconfig['remove']) ) {
		$input_errors[] = "There is not enough space on the target disk!";
	}
	
	// The folder supplied by the user is a valid folder, so we can continue our input validations
	elseif( ( strcmp ( realpath($old_location) , realpath($new_location) ) != 0 ) && 				
				(( count( $base_search ) > 0 ) || ( count( $template_search ) > 0) || $brig_jails )  ) {
		// If the user has selected a new installation folder, then we also must check that there are no existing
		// jails living there. This is a multiple step process. We need to see if there is anything in the basejail or in the
		// template jail, or if there are any jails defined that have their jailpath within thebrig's root. Since
		// the developers didn't want to mess with moving files that have the immutable flag set, they chose to dis-allow it.
			$input_errors[] = _THEBRIG_JAIL_ALREADY ;
		}
	else {
		// If they haven't set a path for the basejail, then we need to assume one
		if ( ! isset($pconfig['basejail']) || empty($pconfig['basejail']) ) 
			$pconfig['basejail']=$pconfig['rootfolder'] . "basejail" ;
		
		// Convert basejail to have trailing /
		if ( $pconfig['basejail'][strlen($pconfig['basejail'])-1] != "/")  
			$pconfig['basejail'] = $pconfig['basejail'] . "/";
		
		// If they haven't set a template path, then we need to assume one
		if ( ! isset($pconfig['template']) || empty($pconfig['template']) ) 
			$pconfig['template']=$pconfig['rootfolder'] . "template" ;
				
		// Convert template location to have trailing /
		if ( $pconfig['template'][strlen($pconfig['template'])-1] != "/")  
			$pconfig['template'] = $pconfig['template'] . "/";
		
	}
	
	
	
	// There are no input errors detected.
	if ( !$input_errors ){
			// We have specified a new location for thebrig's installation, and it's valid, and we don't already have
			// a jail at the old location. Call thebrig_populate, which will move all the web stuff and create the 
			// directory tree
			// Also add startup command when thebrig completly installed
			thebrig_populate( $pconfig['rootfolder'] , $config['thebrig']['rootfolder'] );
			$config['thebrig']['rootfolder'] = $pconfig['rootfolder']; // Store the newly specified folder in the XML config
			$config['thebrig']['template'] = $pconfig['template'];
			$config['thebrig']['basejail']['folder'] = $pconfig['basejail'];
			$langfile = file("ext/thebrig/lang.inc");
			$version_1 = preg_split ( "/VERSION_NBR, 'v/", $langfile[1]);
			$config['thebrig']['version'] = substr($version_1[1],0,3);
			write_config(); // Write the config to disk
		
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
function disable_buttons() {
	document.iform.Submit.disabled = true;
	document.iform.submit();}
</script>

<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr><td class="tabnavtbl">
		<ul id="tabnav">
			<li class="tabinact">
				<a href="extensions_thebrig.php"><span><?=_THEBRIG_JAILS;?></span></a>
			</li>
			<li class="tabinact"><a href="extensions_thebrig_update.php"><span><?=_THEBRIG_UPDATES;?>
					</span> </a>
				</li>
			<li class="tabact">
				<a href="extensions_thebrig_tarballs.php"><span><?=_THEBRIG_MAINTENANCE;?></span></a>
			</li>
			
		</ul>
	</td></tr>
	<tr><td class="tabnavtbl">
		<ul id="tabnav2">
			<li class="tabinact"><a href="extensions_thebrig_tarballs.php"><span><?=_THEBRIG_TARBALL_MGMT;?></span></a></li>
			<li class="tabact"><a href="extensions_thebrig_config.php" title="<?=gettext("Reload page");?>"><span><?=_THEBRIG_BASIC_CONFIG;?></span></a></li>
			<li class="tabinact">
				<a href="extensions_thebrig_tools.php"><span><?=_THEBRIG_TOOLS;?></span></a>
			</li>
		</ul>
	</td></tr>

	<tr><td class="tabcont">
		<form action="extensions_thebrig_config.php" method="post" name="iform" id="iform">
		<table width="100%" border="0" cellpadding="6" cellspacing="0">
		<?php html_titleline(gettext(_THEBRIG_SETTINGS_BASIC));?>
		<?php html_inputbox("rootfolder", gettext(_THEBRIG_ROOT), $pconfig['rootfolder'], gettext(_THEBRIG_ROOT_DESC), true, 50);?>
	 	<?php //html_filechooser("rootfolder", gettext("Media Directory"), $pconfig['rootfolder'], gettext("Directory that contains our jails (e.g /mnt/Mount_Point/Folder). We will create folder /mnt/Mount_Point/Folder/thebrig/"), $g['media_path'], true);?>
		<?php html_separator();?>		
		<?php html_titleline(gettext("Advanced Jail Locations"));?>
		<?php html_inputbox("basejail", gettext(_THEBRIG_BASE), $pconfig['basejail'], gettext(_THEBRIG_BASE_DESC), false, 50 , $base_ro );?>
	 	<?php //html_filechooser("rootfolder", gettext("Media Directory"), $pconfig['rootfolder'], gettext("Directory that contains our jails (e.g /mnt/Mount_Point/Folder). We will create folder /mnt/Mount_Point/Folder/thebrig/"), $g['media_path'], true);?>
		<?php html_inputbox("template", gettext("Template Location"), $pconfig['template'], gettext("Sets the alternate location for the buildworld jail template. Default is in a folder named template within TheBrig's installation folder."), false, 50);?>
	 	<?php //html_filechooser("rootfolder", gettext("Media Directory"), $pconfig['rootfolder'], gettext("Directory that contains our jails (e.g /mnt/Mount_Point/Folder). We will create folder /mnt/Mount_Point/Folder/thebrig/"), $g['media_path'], true);?>
		<?php html_separator();?>
		<?php html_titleline(gettext(_THEBRIG_CLEANUP));?>
		
		<!-- This is the row beneath the title -->
		<tr><td width="22%" valign="top" class="vncellreq">&nbsp;</td>
			<td width="78%" class="vtable">
				<input type="checkbox" name="remove" value="1"><?=_THEBRIG_CLEANUP_DESC;?>
			</td>
		</tr>
			
		<!-- This is the Save button -->
		<tr><td width="22%" valign="top">&nbsp;</td>
			<td width="78%">
			 	<input name="Submit" type="submit" class="formbtn" value="<?=_THEBRIG_SAVE;?>" onClick="disable_buttons();">
			</td>
		</tr>
	</table>
	<?php include("formend.inc");?>
</form>
</td></tr>
</table>
<?php 	include("fend.inc"); ?>
