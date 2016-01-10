<?php
require("auth.inc");
require("guiconfig.inc");
require_once("ext/thebrig/lang.inc");
require_once("ext/thebrig/functions.inc");
/*
  File name: 	extensions_thebrig_manager.php
  Author: 		Matt Kempe, Alexey Kruglov
  Modified:		Dec 2014
  
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
  
  Purpose: 	This page is used to update all the files of TheBrig's
  				extension. 
  
  Variables used:	
  
  input_errors		Nas4Free array with error messages
  pgtitle			Array to label page using lang.inc
  pconfig			Nas4Free array containing $_POST data
  config_changed	Boolean variable to track if the Nas4Free config.xml 
  					needs to have changes written to it
  brig_ver			String (then floating point) version value stored in 
  					the installed copy of TheBrig's lang.inc
  gitlangfile		File descriptor for accessing the github version of 
  					TheBrig's lang.inc (online version)
  git_ver			String (then floating point) version value within the
  					github version of TheBrig's lang.inc. (Also displays
  					an error message when the fetch fails.
  fetch_args		String of OS version dependent arguments to pass to
  					fetch so that it will work.
  fetch_ret_val		Integer to receive the program return status from 
  					the call to fetch.
  
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
	$brig_ver = 0 + substr($brig_ver[1],0,4);
	
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
		if ( floatval($rel[0]) >= 9.1)  {
			// FreeBSD above 9.1 has issues fetching from GitHub, so 
			// we need to tell fetch to not verify certificates
			$fetch_args = "--no-verify-peer";	
		}
		// Before we attempt to download anything, we need to see if we 
		// even have DNS/internet
		$connected = @fsockopen("www.github.com", 80); 
		if ( $connected ) {
			fclose($connected);
			// Go get the lang.inc file
			mwexec2 ( "fetch {$fetch_args} -o /tmp/lang.inc https://raw.github.com/fsbruva/thebrig/alcatraz/conf/ext/thebrig/lang.inc" , $garbage , $fetch_ret_val ) ;
			// Needless error checking
			if ( is_file("/tmp/lang.inc") ) {
				$gitlangfile = file("/tmp/lang.inc");
				// Extract the version string from the file ("0.8", "0.9")
				$git_ver = preg_split ( "/VERSION_NBR, 'v/", $gitlangfile[18]);
				// Force the version to be a number for comparisons
				$git_ver = 0 + substr($git_ver[1],0,4);
				// Go get the install file and make it executable - only if we can successfully get the files we need.
				mwexec2 ( "fetch {$fetch_args} -o /tmp/thebrig_install.sh https://raw.github.com/fsbruva/thebrig/alcatraz/thebrig_install.sh" , $garbage , $fetch_ret_val ) ;
				if ( is_file("/tmp/thebrig_install.sh" ) ) {
					// Fetch of install.sh succeeded
					mwexec ("chmod a+x /tmp/thebrig_install.sh");
				}	// end of install.sh fetch success
				else {
					// We couldn't get the file from GitHub. We might not have 
					// connectivity to Github, the file wasn't found, there was 
					// a DNS issue, or something else went wrong.
					$savemsg = _THEBRIG_CHECK_NETWORKING_GIT;
					$input_errors[]=_THEBRIG_CHECK_NETWORKING_GIT;
				}  // end of failed install.sh fetch	
			} // end of successful lang.inc fetch
		} // end of successful internet connectivity test
		else { 
			// We couldn't get the lang file from GitHub. We might not have 
			// connectivity to Github, the file wasn't found, there was 
			// a DNS issue, or something else went wrong.
			$input_errors[] = _THEBRIG_CHECK_NETWORKING_GIT;
			$git_ver = _THEBRIG_ERROR;
		}	// end of lang.inc fetch failed
	} // end of "Not Post"

} // end of "Brig Confirmed"

// Uses the global fbegin include
include("fbegin.inc");

// This will evaluate if there were any input errors from prior to the user clicking "save"
if ( $input_errors ) { 
	print_input_errors( $input_errors );
}

?> <!-- This is the end of the first bit of html code -->

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
				<li class="tabinact"><a href="extensions_thebrig_update.php"><span><?=_THEBRIG_UPDATER;?>
					</span> </a></li>
				<li class="tabinact"><a href="extensions_thebrig_ports.php"
					title="<?=gettext("Reload page");?>"><span><?=_THEBRIG_PORTS;?> </span>
				</a></li>
				<li class="tabact"><a href="extensions_thebrig_manager.php"><span><?=_THEBRIG_MANAGER;?></span></a></li>
			</ul>
		</td>
	</tr>

	<tr><td class="tabcont">
		<form action="exec.php" method="post" name="iform" id="iform" >
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
					<input id="thebrig_update" name="thebrig_update" type="submit" class="formbtn" value="<?=_THEBRIG_UPDATE_BUTTON;?>" onClick="return confirm('<?=_THEBRIG_INFO_MGR;?>');" /><br />
				</div>
				<input name="txtCommand" type="hidden" value="<?="sh /tmp/thebrig_install.sh {$config['thebrig']['rootfolder']} 3 &";?>" />
			</td>
			</tr> <?php } ?>
		<?php html_separator(); ?>
	</table><?php include("formend.inc");?>
</form>
</td></tr>
</table>
<?php include("fend.inc"); ?>
