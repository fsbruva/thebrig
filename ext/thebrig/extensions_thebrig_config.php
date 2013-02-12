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

// Display the page title, based on the constants defined in lang.inc
$pgtitle = array(_THEBRIG_EXTN , _THEBRIG_TITLE);

// This checks to see if the XML config has no rootfolder for thebrig, but does have the remnants of a 
// successful installation. The original installation script creates the /tmp/thebrig.tmp file, and puts the 
// path inside that tmp file. Thus, this if statement is entered when the original install was completed, but this
// is the first time the page has been loaded since then. 
if ( ( !isset( $config['thebrig']['rootfolder'] ) ) && file_exists( '/tmp/thebrig.tmp' ) ) {
	// This next line extracts the root folder from the install artifact (trimed to remove trailing CR/LF)
	$config['thebrig']['rootfolder'] = rtrim( file_get_contents('/tmp/thebrig.tmp') );
	// The next line propagates the the page's config data (the text box) with the extracted value
	$pconfig['rootfolder'] = $config['thebrig']['rootfolder'];

	// If the thing pulled from the .tmp file is an actual directory, do some stuff
	if ( is_dir( $config['thebrig']['rootfolder'] ) ) {
		write_config();		// write the config so it survives reboot
		thebrig_populate( $config['rootfolder'] , $config['thebrig']['rootfolder'] );
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
else if ( !isset( $config['thebrig']['rootfolder']) ) {
	$input_errors[] = _THEBRIG_NOT_INSTALLED;
} // end of elseif

// User has clicked a button
if ($_POST) {
	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	$reqdfields = array();
	$reqdfieldsn = array();
	$reqdfields = array_merge($reqdfields, explode(" ", "rootfolder"));
	$reqdfieldsn = array_merge($reqdfieldsn, array( _THEBRIG_ROOT ));

	//do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
	
	// This first check to make sure that the supplied folder actually exists. If it does not
	// then the user should be alerted. No changes will be made.
	if ( !is_dir( $pconfig['rootfolder'] ) ) {
		$input_errors[] = _THEBRIG_NONEXISTENT_FOLDER;
	}
	// We also need to be able to write to the folder, so lets check that. The webgui runs as root, so this 
	// condition is highly suspect, but needs to be covered.
	elseif ( !is_writable( $pconfig['rootfolder'] ) ){
		$input_errors[] = _THEBRIG_NOTWRITABLE_FOLDER;
	}
	// The folder supplied by the user is a valid folder, so we can continue our input validations
	else {
		// brig_search is an array containing all the files within the root/conf that start with fstab.
		$brig_search = glob( $config['thebrig']['rootfolder'] . "/conf/fstab." . "*" );
		// If the user has selected a new installation folder, then we also must check that there are no existing 
		// jails living there. This is a two step process. The first step is to check and see how many elements (files)
		// are contained in brig_search. This is effective because the presence of any fstab files implies a bootable jail.
		// The second step is to see if a basejail has been created. This jail is likely to be mounted read-only, but since
		// the developer didn't want to mess with moving files that have the immutable flag set, he chose to dis-allow it.
		if ( ( $pconfig['rootfolder'] != $config['thebrig']['rootfolder'] ) && 
				( count( $brig_search ) > 0 ) || is_dir ($config['thebrig']['rootfolder'] . "/basejail" ) )   {
			$input_errors[] = _THEBRIG_JAIL_ALREADY ;
		}
	}
	
	// There are no input errors detected.
	if ( !$input_errors ){
		// The user wants to unregister the extension
		if ( $pconfig['remove'] ) {
			// we want to remove thebrig
			thebrig_unregister();
			// Browse back to the main page
			header("Location: /");
			exit;
		}
		else {
			// We have specified a new location for thebrig's installation, and it's valid, and we don't already have
			// a jail at the old location. Call thebrig_populate, which will move all the web stuff and create the 
			// directory tree
			thebrig_populate( $pconfig['rootfolder'] , $config['thebrig']['rootfolder'] );
			// Store the newly specified folder in the XML config
			$config['thebrig']['rootfolder'] = $pconfig['rootfolder'];
			// Write the config to disk
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
			<li class="tabact">
				<a href="extensions_thebrig_config.php"><span><?=_THEBRIG_MAINTENANCE;?></span></a>
			</li>
		</ul>
	</td></tr>
	<tr><td class="tabnavtbl">
		<ul id="tabnav2">
			<li class="tabinact"><a href="extensions_thebrig_tarballs.php"><span><?=_THEBRIG_TARBALL_MGMT;?></span></a></li>
			<li class="tabact"><a href="extensions_thebrig_config.php" title="<?=gettext("Reload page");?>"><span><?=_THEBRIG_BASIC_CONFIG;?></span></a></li>
			
		</ul>
	</td></tr>

	<tr><td class="tabcont">
		<form action="extensions_thebrig_config.php" method="post" name="iform" id="iform">
		<table width="100%" border="0" cellpadding="6" cellspacing="0">
		<tr><td colspan="2" valign="top" class="optsect_t">
			<table border="0" cellspacing="0" cellpadding="0" width="100%">
				<tr><td class="optsect_s"><strong><?=_THEBRIG_SETTINGS_BASIC; ?></strong></td>
					<td align="right" class="optsect_s">
					</td>
				</tr>
			</table>
		</td></tr>

		<!-- The first td of this row is the box in the top row, far left. -->
		<tr><td width="22%" valign="top" class="vncellreq"><?=_THEBRIG_ROOT; ?></td>
		<!-- The next td is the larger box to the right, which contains the text box and info --> 
		<td width="78%" class="vtable">
			<input name="rootfolder" type="text" class="formfld" id="rootfolder" size="50" value="<?=htmlspecialchars($pconfig['rootfolder']);?>"><br/>
			<span class="vexpl"><?=_THEBRIG_ROOT_DESC ;?></span>
		</td></tr>


		<!--  These next two rows merely output some space between the upper and lower tables -->
		<tr><td colspan="2" valign="top" class="tblnk"></td></tr>
		<tr><td colspan="2" valign="top" class="tblnk"></td></tr>
			
		<!-- This is the table to allow the user to uninstall TheBrig from N4F -->
		<tr><td colspan="2" valign="top" class="optsect_t">
			<div class="optsect_s"><strong><?=_THEBRIG_CLEANUP;?></strong></div></td></tr>
			
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

<?php
	include("fend.inc");
?>