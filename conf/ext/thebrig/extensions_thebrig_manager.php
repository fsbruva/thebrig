<?php
require("auth.inc");
require("guiconfig.inc");
require_once("ext/thebrig/lang.inc");
require_once("ext/thebrig/functions.inc");
/*
 * File name: 	extensions_thebrig_manager.php
 * Author: 		Matt Kempe, Alexey Kruglov
 * Modified:	Dec 2014
 * 
 * Purpose: 	This page is used to update all the files of TheBrig's
 * 				extension. 
 * 
 * Variables used:	
 * 
 * input_errors		Nas4Free array with error messages
 * pgtitle			Array to label page using lang.inc
 * pconfig			Nas4Free array containing $_POST data
 * config_changed	Boolean variable to track if the Nas4Free config.xml 
 * 					needs to have changes written to it
 * brig_ver			String (then floating point) version value stored in 
 * 					the installed copy of TheBrig's lang.inc
 * gitlangfile		File descriptor for accessing the github version of 
 * 					TheBrig's lang.inc (online version)
 * git_ver			String (then floating point) version value within the
 * 					github version of TheBrig's lang.inc. (Also displays
 * 					an error message when the fetch fails.
 * fetch_args		String of OS version dependent arguments to pass to
 * 					fetch so that it will work.
 * fetch_ret_val	Integer to receive the program return status from 
 * 					the call to fetch.
 */
	
// Display the page title, based on the constants defined in lang.inc
$pgtitle = array(_THEBRIG_EXTN , _THEBRIG_TITLE, _THEBRIG_MANAGER_TITLE);
	
	// This checks to see if we've finished installing TheBrig. If there
	// is no stored folder or created work folder, the install isn't done.
if ( !isset( $config['thebrig']['rootfolder']) || !is_dir( $config['thebrig']['rootfolder']."work" )) {
	$input_errors[] = _THEBRIG_NOT_CONFIRMED;
} // end of if
else { // TheBrig has been confirmed
	// Get the string version of the installed software
	$brig_ver = preg_split ( "/v/", _THEBRIG_VERSION_NBR);
	// Convert the string to a float so that it can be used in comparisons
	$brig_ver = 0 + substr($brig_ver[1],0,3);
	
	if ( !$_POST ) {
		// $_POST not being set means we haven't clicked a button - so 
		// we need to go and get the lastest version
		
		// First we get rid of the previously fetched file
		unlink_if_exists ("/tmp/lang.inc");
		// Foolish workaround because of the version of "fetch" included
		// in 9.1 compared to other FreeBSD versions
		$fetch_args = "";			// "default" arguments for 9.1
		mwexec2("uname -r | cut -d- -f1" , $rel ) ; 		// Obtain the current kernel release
		// If the string compare yields anything other than "0", we 
		// are not 9.1
		if ( strcmp($rel[0], "9.1") != 0 ) {
			// FreeBSD above 9.1 has issues fetching from GitHub, so 
			// we need to tell fetch to not verify certificates
			$fetch_args = "--no-verify-peer";	
		}
		mwexec2 ( "fetch {$fetch_args} -o /tmp/lang.inc https://raw.github.com/fsbruva/thebrig/alcatraz/conf/ext/thebrig/lang.inc" , $garbage , $fetch_ret_val ) ;
		// $result will be "1" if fetch didn't do something properly
		if ( $fetch_ret_val == 1 ) {
			// We couldn't get the file from GitHub. We might not have 
			// connectivity to Github, the file wasn't found, there was 
			// a DNS issue, or something else went wrong.
			$input_errors[] = _THEBRIG_CHECK_NETWORKING_GIT;
			$git_ver = _THEBRIG_ERROR;
		}	// end of fetch failed
		else {
			// We need to check to see the file exists, otherwise provide error
			// This should never happen, but you never know..
			if ( file_exists( "/tmp/lang.inc" ) ) {
				// Load the GitHub lang file into an array
				$gitlangfile = file("/tmp/lang.inc");
				// If reading the file is successful, do some operations
				if ( $gitlangfile ) {
					// Extract the version string from the file ("0.8", "0.9")
					$git_ver = preg_split ( "/VERSION_NBR, 'v/", $gitlangfile[1]);
					// Force the version to be a number for comparisons
					$git_ver = 0 + substr($git_ver[1],0,3);
				} // end if $gitlangfile
				else { // Something failed trying to access the GitHub lang file
					$input_errors[] = _THEBRIG_GIT_LANG_FAIL;
					$git_ver = _THEBRIG_ERROR;
				} // end else $gitlangfile
			} // end if langfile exists
			else { // The lang file we just downloaded is missing! HOW!
				$input_errors[] = _THEBRIG_GIT_LANG_FAIL;
				$git_ver = _THEBRIG_ERROR;
			} // end else langfile exists	
		} // end of successful fetch
	} // end of "Not Post"

} // end of "Brig Confirmed"

// We have returned to this page via a POST
if ($_POST) {
	unset($input_errors);
	$pconfig = $_POST;			// move $_POST into the $post config
	$config_changed = false;	// Keep track if we need to re-write the config

	// There are no input errors detected - we checked:
	// 1. Networking
	// 2. Availability of Github servers
	// We know we got here via "POST" - but we want to make sure the user
	// clicked the "Update" button.
	if ( !$input_errors && isset($pconfig['update']) && $pconfig['update'] == "Update"){
		
		// I moved the version check code to thebrig_start.php

		if ( $git_ver > $brig_ver ) {
			// We want to make sure we can't let the user revert - the code we need to update thebrig will go here.
			mkdir("/tmp/thebrig000",0777);
			cmd_exec ("fetch -o /tmp/thebrig000/thebrig.zip https://github.com/fsbruva/thebrig/archive/alcatraz.zip", $output, $tolog );
			chdir("/tmp/thebrig000");
			mwexec ("tar -xvf thebrig.zip --exclude='.git*' --strip-components 1");
			mwexec("rm thebrig.zip");
			updatenotify_set("thebrig", UPDATENOTIFY_MODE_MODIFIED, "update");
		}
	} // end of no input errors

// these posts never get reached... because there is no agree or cancel button that is initiating
// the post. 
	If (isset($_POST['cancel']) && $_POST['cancel'] == "Cancel" ) {
		//mwexec ("rm -rf /tmp/thebrig000"); Not needed - this folder never gets created
		updatenotify_delete("thebrig");
		$savemsg = "Update process aborted";	
	}
	If (isset($_POST['agree']) && $_POST['agree'] == "Agree" ) {
		$cmd = "cp -r /tmp/thebrig000/* ".$config['thebrig']['rootfolder'];
		file_put_contents ("/tmp/cmdbrig","#!/bin/sh\n" . $cmd);
		
		updatenotify_delete("thebrig");
		mwexec ("sh /tmp/cmdbrig");
		$savemsg = "Updated";
		mwexec ("rm -rf /tmp/thebrig000");
		mwexec ("rm /tmp/cmdbrig");
		header("Location: extensions_thebrig.php");

	}
} // end of POST

// Uses the global fbegin include
include("fbegin.inc");

// This will evaluate if there were any input errors from prior to the user clicking "save"
if ( $input_errors ) { 
	print_input_errors( $input_errors );
}

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
			<li class="tabinact">
				<a href="extensions_thebrig_update.php"><span><?=_THEBRIG_UPDATES;?></span></a>
			</li>
			<li class="tabact">
				<a href="extensions_thebrig_tarballs.php"><span><?=_THEBRIG_MAINTENANCE;?></span></a>
			</li>
			<li class="tabinact"><a href="extensions_thebrig_log.php"><span><?=gettext("Log");?></span></a></li>
		</ul>
	</td></tr>
	<tr><td class="tabnavtbl">
		<ul id="tabnav2">
			<li class="tabinact"><a href="extensions_thebrig_tarballs.php"><span><?=_THEBRIG_TARBALL_MGMT;?></span></a></li>
			<li class="tabact"><a href="extensions_thebrig_manager.php" title="<?=gettext("Reload page");?>"><span><?=_THEBRIG_MANAGER;?></span></a></li>
			<li class="tabinact"><a href="extensions_thebrig_config.php"><span><?=_THEBRIG_BASIC_CONFIG;?></span></a></li>
			<li class="tabinact"><a href="extensions_thebrig_tools.php"><span><?=_THEBRIG_TOOLS;?></span></a></li>
		</ul>
	</td></tr>

	<tr><td class="tabcont">
		<form action="extensions_thebrig_manager.php" method="post" name="iform" id="iform" onsubmit="return checkBeforeSubmit();">
		<?php if (updatenotify_exists_mode("thebrig", 1 )) print_thebrig_confirm_box();?>
		<?php $msg =  _THEBRIG_NOT_CONFIRMED; if (is_file("/tmp/thebrig.tmp")) print_warning_box( $msg); ?>
		<table width="100%" border="0" cellpadding="6" cellspacing="0">
		<?php 
			html_titleline(gettext("Update Availability")); 
			html_text($confconv, gettext("Current Status"),"The latest version on GitHub is: " . $git_ver . "<br /><br />Your version is: " . $brig_ver ); ?> 
			<tr>
			<?php if (! $input_errors ) { ?>
			<td width="22%" valign="top" class="vncell">Update your installation&nbsp;</td>
			<td width="78%" class="vtable">
			<?=gettext("Click below to download and install the latest version.");?><br />
				<div id="submit_x">
					<input id="update" name="update" type="submit" class="formbtn" value="<?=gettext("Update");?>" onClick="return conf_handler();" /><br />
				</div>
			</td>
			</tr> <?php } ?>
		<?php html_separator(); ?>
	</table><?php include("formend.inc");?>
</form>
</td></tr>
</table>
<?php include("fend.inc"); ?>
