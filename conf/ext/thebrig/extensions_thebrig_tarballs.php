<?php
/*
	file: extensions_thebrig_tarballs.php
	
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
require_once("ext/thebrig/lang.inc");
require_once("ext/thebrig/functions.inc");
	
// Display the page title, based on the constants defined in lang.inc
$pgtitle = array(_THEBRIG_TITLE, _THEBRIG_MAINTENANCE, _THEBRIG_TARBALL_MGMT) ;
// add array Freebsd ftp servers 
$ftp_servers = array( "ftp.freebsd.org", "ftp1.freebsd.org", "ftp2.freebsd.org", "ftp3.freebsd.org", "ftp6.freebsd.org","ftp7.freebsd.org","ftp10.FreeBSD.org","ftp11.FreeBSD.org","ftp13.FreeBSD.org");
// This checks if we have successfully contacted a ftp server - the existence of /tmp/ftpsen gives us a clue about that. If the file
// exists, then we should read the number stored there - it will tell us which server to use. If it doesn't exist, start at 0.
if (!is_file("/tmp/ftpsen") ) {file_put_contents ("/tmp/ftpsen", "0" );}


if ( !isset( $config['thebrig']['rootfolder']) || !is_dir( $config['thebrig']['rootfolder']."work" )) {
	$input_errors[] = _THEBRIG_NOT_CONFIRMED;
} // end of elseif

if ($_POST) {
	$cmd = "touch ".$config['thebrig']['rootfolder']."thebrigerror.txt";
	mwexec ($cmd);
	unset( $input_errors ) ; // clear out the input errors array
	$pconfig = $_POST;
	mwexec2("uname -m" , $arch ) ;		// Obtain the machine architecture
	$arch = $arch[0] ;					// Extract the first string from the array
	mwexec2("uname -r | cut -d- -f1-2" , $rel ) ; 		// Obtain the current kernel release
	$rel = $rel[0] ;					// Extract the first string from the array

	// This first error check is verifying that at least one file was selected for deletion.
	// If the "Delete" button was pressed, then we need to check for that, and then grab
	// the list of files selected, and see how big that array is (count). If the size is less than
	// one (implying that it is 0), then nothing has been selected, and we need to let the user know.
	if ( isset ($_POST['delete'] ) && count ( $_POST['formFiles'] ) < 1 ){
		$input_errors[] = _THEBRIG_DELETE_ERROR ;
	}

	// This first error check is verifying that at least one file was selected for fetching.
	// If the "Fetch" button was pressed, then we need to check for that, and then grab
	// the list of files selected, and see how big that array is (count). If the size is less than
	// one (implying that it is 0), then nothing has been selected, and we need to let the user know.
	if (isset($_POST['fetch']) && count ( $_POST['formPackages'] ) < 1 ){
		$input_errors[] = _THEBRIG_FETCH_ERROR ;
	}

	// This error check is attempting to contact the ftp server. If it is successful, the data regarding the
	// base version is downloaded. This includes the manifest(s), as well as data about the versions available.
	// This data is needed to populate the tarball selection section of the page. For example, for N4F that is based
	// on 9.1-RC3, the data about amd64 & i386 for 9.0 release AND 9.1-RC3 is downloaded.
	if ( isset( $_POST['ftpquery'] ) && $_POST['ftpquery'] ){
		// read what index we should look at (based on the file)
		$ftp_n = file_get_contents("/tmp/ftpsen");
		do {
			// Specifies the FTP server to contact
			$ftp_server = $ftp_servers[$ftp_n] ;
			// Specifies the folder to access
			$ftp_path = "/pub/FreeBSD/releases/".$arch."/".$arch."/" ;
			
			$raw_document = file_get_contents("http://" . $ftp_server . $ftp_path );
			// This regex ensures we don't have any 8.x versions. Before
			// version 9, FreeBSD was not distributed using tarballs.
			preg_match_all( '/\d?[0-7,9]\.\d-R[A-Z]+\d?(?=<)/',$raw_document , $matches);
			$matches = $matches[0];

			if ( $matches ){
				// We need the numeric portion of the release version
				$rel_numeric = explode ( '-', $rel );
				// Due to the formatting, we may have duplicates
				$matches = array_unique ( $matches ) ;
				// This puts the array in order
				rsort($matches, SORT_NATURAL);
				$k = 0;
				foreach ( $matches as $match_test ) {
					$matches_numeric = explode ( '-', $match_test );
					// This checks to make sure we are not trying to download 
					// a tarball that is newer than our kernel.
					if ( $matches_numeric[0] <= $rel_numeric[0] ){
						$result[$k++] = $match_test;
					}
				}
 												
				// Keep track of the fact that we have successfully queried the FTP server.
				$config['thebrig']['ftpquery'] = array() ;
				// This if statement evaluates whether or not the list of releases contains the same version
				// kernel of the host. If it's not, then we need to alert the user.
				if  ( array_search( $rel , $result )  === false ) {
					//$input_errors[] = _THEBRIG_MATCH_ERROR ;
					$input_errors[] = $result[0];
				} // end of if to check for version mismatch
			} // end of if to check if we got a result
			else {
			// This server didn't provide any results - lets try another!
				$ftp_n++;
				file_put_contents ("/tmp/ftpsen", $ftp_n );
			}
			
			
		// much better coding - we continue this loop while we've failed and we still have servers yet to try.
		} while ( !$result && ($ftp_n < count ($ftp_servers)) );
			
		//$input_errs[] = "outside while and result is ";
		if ( !$result ){
			// The query existed, but nothing was obtained into result - thus, there was no data to read
			// from the ftp server. This is an indication of:
			// 1. There is no WAN connection
			// 2. DNS is misconfigured
			// Try another server
			// We didn't get a reults AND we tried all the servers we could
			unlink ("/tmp/ftpsen");
			$input_errors[] = _THEBRIG_CHECK_NETWORKING ;
		} // end of if to check if we exhausted all servers and found nothing
	} // end of if for the user pressed "query"
	
	// There are no input errors detected, so we can attempt the actual work
	if ( !$input_errors )
	{
		// In this case, the actual work is to delete the selected tarballs from the upper section of the page
		if ( isset( $_POST['delete'] ) && $_POST['delete'] ) {
			// The files_remove array is a list of the files selected for deletion
			$files_remove = $_POST['formFiles'] ;
			// For each of the files in the array
			foreach ( $files_remove as $file ) {
				// Delete the selected file from the "work" directory
				if (is_file($config['thebrig']['rootfolder'] . "work/" . $file)) $check = unlink ( $config['thebrig']['rootfolder'] . "work/" . $file);
				// If the unlink (deletion) operation is unsuccessful, alert user
				if ( ! $check ) {
					$input_errors[] = _THEBRIG_DELETE_FAIL . "$file" ;
					break;
				}
				// The unlink operation was successful
				else {
					// Set the reval to 0 and pass that to the save message function.
					$reval = 0;
					$savemsg = get_std_save_message( $retval );
				}
			} // end of for loop through list of files slated for deletion
		} // end of if for "delete"
				
		elseif ( isset( $_POST['fetch'] ) && $_POST['fetch'] ) {
		$ftp_n = file_get_contents("/tmp/ftpsen");
			$pack_get = $_POST['formPackages'] ;
			$rel_get = $_POST['formRelease'] ;
			// This loop runs for each of the selected pacakages
			foreach ( $pack_get as $pack_name ) {
				// This code builds the command string with the appropriate architecture, release & package name.
				$c_string = "/bin/sh "."{$config['thebrig']['rootfolder']}"."conf/bin/thebrig_fetch.sh "."{$arch} {$rel_get} {$pack_name} {$config['thebrig']['rootfolder']}"."work ".$ftp_servers[$ftp_n]." >/dev/null &";
				// Carries out the fetching operation in the background
				exec( $c_string , $output, $return);
			}// end of for loop
					
		} // end of elseif for "fetch"
	}
//output to thebrig.log
	$a_tolog1 = file($config['thebrig']['rootfolder'] . "thebrigerror.txt");
	$filelog = $config['thebrig']['rootfolder']."thebrig.log";
	$handle1 = fopen($filelog, "a+");
	foreach ($a_tolog1 as $tolog1 ) { fwrite ($handle1, "[".date("Y/m/d H:i:s")."]: TheBrig error!: ".trim($tolog1)."\n" ); }
	fclose ($handle1);

	unlink ($config['thebrig']['rootfolder'] . "thebrigerror.txt");
}

// Uses the global fbegin include
include("fbegin.inc");

// This will evaluate if there were any input errors from prior to the user clicking "save"
if ($input_errors) { 
	print_input_errors($input_errors);
}
// This will alert the user to unsaved changes, and prompt the changes to be saved.
elseif ($savemsg) print_info_box($savemsg);

?> <!-- This is the end of the first bit of html code -->
<!-- This function allows the pages to render the buttons impotent whilst carrying out various functions -->
<script language="JavaScript">
function disable_buttons() {
	document.iform.Submit.disabled = true;
	document.iform.submit();}
var auto_refresh = setInterval(
		function()
		{
		$('#loaddiv').load('extensions_thebrig_download.php');
		}, 2000);
</script>

<table width="100%" border="0" cellpadding="0" cellspacing="0" >
	<tr><td class="tabnavtbl">
		<ul id="tabnav">
			<li class="tabinact"><a href="extensions_thebrig.php"><span><?=_THEBRIG_JAILS;?></span></a></li>
			<?php If (!empty($config['thebrig']['content'])) { 
			$thebrigupdates=_THEBRIG_UPDATES;
			echo "<li class=\"tabinact\"><a href=\"extensions_thebrig_update.php\"><span>{$thebrigupdates}</span></a></li>";
			} else {} ?>	<li class="tabact"><a href="extensions_thebrig_tarballs.php"><span><?=_THEBRIG_MAINTENANCE;?></span></a></li>
			<li class="tabinact"><a href="extensions_thebrig_log.php"><span><?=gettext("Log");?></span></a></li>
		</ul>
	</td></tr>
	<tr><td class="tabnavtbl">
		<ul id="tabnav2">
			<li class="tabact"><a href="extensions_thebrig_tarballs.php"  title="<?=gettext("Reload page");?>"><span><?=_THEBRIG_TARBALL_MGMT;?></span></a></li>
			
			<li class="tabinact"><a href="extensions_thebrig_config.php"><span><?=_THEBRIG_BASIC_CONFIG;?></span></a></li>
			<li class="tabinact"><a href="extensions_thebrig_tools.php"><span><?=_THEBRIG_TOOLS;?></span></a></li>
		</ul>
	</td></tr>

	<tr><td class="tabcont">
		<form action="extensions_thebrig_tarballs.php" method="post" name="iform" id="iform">
		<table width="100%" border="0" cellpadding="6" cellspacing="0">

		<?php html_titleline(gettext(_THEBRIG_CURRENT_TB));?>
		<!-- The first td of this row is the box in the top row, far left. -->
		<tr><td width="22%" valign="top" class="vncellreq"><?=_THEBRIG_OFFICIAL_TB; ?></td>
		<!-- The next td is the larger box to the right, which contains the text box and info --> 
		<td width="78%" class="vtable">
			<?php
			// This obtains a list of files that match the criteria (named anything FreeBSD*)
			// within the /work folder.
			$file_list = thebrig_tarball_list("FreeBSD*");
			// This filelist is then used to generate html code with checkboxes
			$installLib = thebrig_checkbox_list($file_list);
			if ( $installLib ) { // If the array exists and has a size, then display that html code
				echo $installLib;
			}
			else { // the array does not exist or has no size, so inform user there are no tarballs found.
				echo sprintf(_THEBRIG_NO_TB);
			} // end of else (there are currently no valid install files
			?>
			</td></tr>

			<!-- The first td of this row is the box in the top row, far left. -->
		<tr><td width="22%" valign="top" class="vncellreq"><?=_THEBRIG_CUSTOM_TB; ?></td>
		<!-- The next td is the larger box to the right, which contains the text box and info --> 
		<td width="78%" class="vtable">
			<?php
			// This obtains a list of files that match the criteria (named anything *, excluding FreeBSD)
			// within the /work folder.
			$file_list = thebrig_tarball_list( "*" , array( "FreeBSD"  ) );
			// This filelist is then used to generate html code with checkboxes
			$installLib = thebrig_checkbox_list( $file_list );
			if ( $installLib ) {  // If the array exists and has a size, then display that html code
				echo $installLib;
			}
			else {	// the array does not exist or has no size, so inform user there are no tarballs found.
				echo sprintf( _THEBRIG_NO_CUST_TB );
			} // end of else (there are currently no valid install files
			?>
			</td></tr>
				<!-- This is the Delete button -->
		<tr><td width="22%" valign="top">&nbsp;</td>
			<td width="78%">
			 	<input name="delete" type="submit" class="formbtn" value="<?=_THEBRIG_DELETE_TB;?>" onClick="return confirm('<?=_THEBRIG_CONF_TB;?>')">
			</td>
		</tr>

		<?php html_separator();?>	
		<?php html_titleline(gettext(_THEBRIG_REMOTE_TB));?>
					
		<!-- This is the row beneath the title -->
		<tr><td width="22%" valign="top" class="vncellreq"><?=_THEBRIG_REMOTE_AVAIL ?></td>
			<td width="78%" class="vtable">
			
			<?php 
			if ( isset($config['thebrig']['ftpquery']) ){
				// This means we have  successfully queried the ftp server, and so can thus display some
				// info to the user about their download options.
				echo "Release: " ;
				// This calls the menu list creation function to build the html object (dropdown box). This object is named formRelease, and is
				// populated with the listing of available releases ( in array $result ). The default selected item is the release that matches
				// the current release of Nas4free
				$rel_menu = thebrig_menu_list( $result , "formRelease" , $rel ) ;
				echo $rel_menu ; // Output the menu as html text
				echo "<br/>" ;
				// Builds the checkboxes of available tarballs. There is no real reason for games or any of the other tarballs to be downloaded, 
				// so they are not even an option.
				$availFiles = "<input type=\"checkbox\" name=\"formPackages[]\" value= \"base\"> base.txz" ;
				$availFiles .= "<input type=\"checkbox\" name=\"formPackages[]\" value= \"src\"> src.txz" ;
				$availFiles .= "<input type=\"checkbox\" name=\"formPackages[]\" value= \"doc\"> doc.txz" ;
				if ( $arch == "amd64") {
					$availFiles .=  "<input type=\"checkbox\" name=\"formPackages[]\" value= \"lib32\" id=\"lib32_box\">lib32.txz" ;
				}
				$availFiles .=  "" ;
				$availFiles .=  "<br/>" ;
				echo $availFiles;
			} // end of the if to check that the query button was clicked
			else {
				// This means we haven't talked to the FTP server, so we should display some informative message to the 
				// user.
				echo sprintf( _THEBRIG_REMOTE_INST );
				 }?>			
			</td>
		</tr>
		<tr>
			<!-- This is the empty left column-->
			<td width="22%" valign="top"></td>
			<td width="78%">
			<!-- This is the Fetch button, which is dependent upon a successful ftp server query -->
			<?php 
			if ( isset($config['thebrig']['ftpquery']) ){ ?>
				<input name="fetch" type="submit" class="formbtn" value="<?=_THEBRIG_FETCH;?>" onClick="return confirm('<?=_THEBRIG_INFO_TB;?>')">
			<?php
			} else { ?>
				<!-- This is the Query button, which is displayed if we haven't yet queried the ftp server -->
				<input name="ftpquery" type="submit" class="formbtn" value="<?=_THEBRIG_QUERYBTN;?>" onClick="disable_buttons();">
				<?php
			} ?>
			</td>
		</tr>
		<?php html_separator();?>	
		<?php html_titleline(gettext(_THEBRIG_REMOTE_ACTIVE));?>
		
				<!-- The first td of this row is the box in the top row, far left. -->
		<tr><td width="22%" valign="top" class="vncellreq"><?=_THEBRIG_PARTIAL_TB; ?></td>
		<!-- The next td is the larger box to the right, which contains the text box and info --> 
		<td width="78%" class="vtable">
		<!-- This creates a div named loaddiv, which is dynamically update by an ajax, jquery function -->
		<div id="loaddiv" style="display: block;"><script>$('#loaddiv').load("extensions_thebrig_download.php");</script></div>
			</td></tr>
		
	</table>
	<?php include("formend.inc");?>
</form>
</td></tr>
</table>
<?php include("fend.inc"); ?>
