<?php
require("auth.inc");
require("guiconfig.inc");
require_once("ext/thebrig/lang.inc");
require_once("ext/thebrig/functions.inc");
	
// Display the page title, based on the constants defined in lang.inc
$pgtitle = array(_THEBRIG_EXTN , _THEBRIG_TITLE) ;

if ( !isset( $config['thebrig']['rootfolder']) ) {
	$input_errors[] = _THEBRIG_NOT_CONFIRMED;
} // end of elseif

if ($_POST) {
	unset( $input_errors ) ; // clear out the input errors array
	$pconfig = $_POST;		
	mwexec2("uname -m" , $arch ) ;		// Obtain the machine architecture
	$arch = $arch[0] ;					// Extract the first string from the array
	mwexec2("uname -r" , $rel ) ; 		// Obtain the current kernel release
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
		// Specifies the FTP server to contact
		$ftp_server = "ftp.freebsd.org" ;
		// Specifies the folder to access
		$ftp_path = "/pub/FreeBSD/releases/$arch/" ;
		// Method is used from: http://camposer-techie.blogspot.com/2010/08/ejecutando-comandos-sobre-un-programa.html
		// Creates an array of streams to deal with stdin, stdout and error file.
		$descriptorspec = array(
				0 => array( "pipe" , "r" ),  // stdin is a pipe that STDIN will read from
				1 => array( "pipe" , "w" ),  // stdout is a pipe that STDOUT will write to (write from the process)
				2 => array( "file" , $config['thebrig']['rootfolder'] . "/thebrigerror.txt", "a") // stderr is a file to write to
			) ;
		
		//Define the command string used to open an ftp connection based on the specified parameters
		$cmd_str = $config['thebrig']['rootfolder'] . "/bin/ftp -a ftp://" . $ftp_server . $ftp_path ;
		
		// Define an ftp resource stream by running the specified command, using the descriptor spec and
		// placing the process's IO within pipes. The environment and other_options parameter are NULL.		
		$ftp_proc = proc_open ( $cmd_str , $descriptorspec, $pipes, NULL, NULL) ;

		// Declare the variables needed for the stream_select operation 
		$read = array( $pipes[1] ) ;    // renames the pipe
		$write = null ;
		$except = null ;
		$readTimeout = 5 ;
		
		//  If the connection cannot be established, then $ftp_proc will be false, and not a resource
		//  However, this check is mostly uneeded, even if the ftp binary doesn't exist.
		if ( is_resource( $ftp_proc ) ){
			// This line is needed to prevent the write operation from setting a lock on the entire process. We need this
			// process to be written to multiple times. Thus, we take responsibility for managing the inpput and output streams.
			// In order to do this, we unset the stream block. 
			stream_set_blocking( $pipes[1] , 0 ) ;
			// The first command we will send is to retreive the directory listing from the ftp server 
			fwrite( $pipes[0] , "ls\n" ) ;
			// We then need the output to be actually written out of the buffer (send the command)
			fflush( $pipes[0] ) ;
			// We then tell PHP that we would like to wait for the change in status of the read pipe (that is, there is data to be
			// read from the console
			stream_select( $read , $write , $except , $readTimeout ) ;
			$k = 0 ;		// Set the index counter to 0
			// The fgets command extracts the line until the EOL character (CR/LF) is obtained, and stores is as a string
			// in $raw. Then, due to older versions of "ls" being used on the FreeBSD ftp servers, we need to be careful
			// that the response we get back is sanitized from a line containing the total bytes (which doesn't have any
			// meaningful data about the release directory contents.
			while ( $raw = fgets( $pipes[1]) ) {
				// Omit the ISO_IMAGES, and README, etc.
				if ( strpos( $raw , "total" ) === false && strpos( $raw , "-R") !== false ) {
					// Use a regular expression method to parse the columns, based on white space
					$line = preg_split( "/[\s]+/" , $raw ) ;
					// Due to a major configuration change starting with FreeBSD version 9, we need to make sure
					// that the version (the first character of the directory name) is greater than or equal to 9.
					// Since the name of the directory is the 8th column, the index is [8][0], and we need to cast it
					// to an integer.
					if ( intval( $line[8][0] >= 9 ) ) {
						// The name of the directory is the 8th (starting at 0) column
						$result[$k] = $line[8];
					} // end of test for verion 9 or higher
				} // end of if statement for ISO_IMAGES, README
				$k++ ;	// Increment the counter				
			} // end of while loop
			// Now we need to get data about release candidates. We do this by moving into the arch directory,
			// and listing the contents. 
			fwrite( $pipes[0] , "cd $arch\nls\n" );
			// We then need the output to be actually written out of the buffer (send the command)
			fflush( $pipes[0] );
			// We then tell PHP that we would like to wait for the change in status of the read pipe (that is, there is data to be
			// read from the console
			stream_select( $read , $write , $except , $readTimeout );
			// Read in the responses as before, but append them to the existing array of directory names (thus we don't reset k)
			while ( $raw = fgets( $pipes[1] )){
				// Omit the ISO_IMAGES, and README, etc. by checking for the line "total" and -R, 
				// which is present in both -RC and -Release versions.
				if ( strpos($raw, "total") === false && strpos( $raw , "-R") !== false ) {
					// Use a regular expression method to parse the columns, based on white space
					$line = preg_split( "/[\s]+/" , $raw ) ;
					if ( intval( $line[8][0] >= 9 ) ) {
						// The name of the directory is the 8th (starting at 0) column
						$result[$k] = $line[8];
					} // end of test for verion 9 or higher
				}
				$k++ ; // Increment the counter
			} // end while loop used to extract stuff from the stream
			
			// The process exists, but nothing was obtained into result - thus, there was no data to read
			// from the ftp server, which is weird because the query is properly formatted. This is an indication of:
			// 1. There is no WAN connection
			// 2. DNS is misconfigured
			// 3. The ftp binary that is bundled with theBrig is missing
			if ( !$result ) {
				$input_errors[] = _THEBRIG_CHECK_NETWORKING ;
			}
			else {
				// A valid response was obtained, so we can finish grabbing the other items (manifests, mostly) we need.
				// Since the latest major release is listed in both releases and arch, we need to sanitize the listing
				$result = array_unique ( $result ) ;	
				// Keep track of the fact that we have successfully queried the FTP server.
				$config['thebrig']['ftpquery'] = array() ;
				// This if statement evaluates whether or not the list of releases contains the same version 
				// kernel of the host. If it's not, then we need to alert the user.
				if  ( !array_search( $rel , $result ) ) {
					$input_errors[] = _THEBRIG_MATCH_ERROR ;
				} // end of 
			} // end of else ( there was a result ) 
			fclose( $pipes[0] ) ; 					// Close the output pipe
			$exit_code = proc_close( $ftp_proc) ;	// Close the ftp process
		} // end of if for the successful creation of the ftp resource
		else {
			$input_errors[] = "not a resource!";
		}
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
				$check = unlink ( $config['thebrig']['rootfolder'] . "/work/" . $file);
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
			$pack_get = $_POST['formPackages'] ;
			$arch_get = $_POST['formArch'] ;
			$rel_get = $_POST['formRelease'] ;
			// This loop runs for each of the selected pacakages
			foreach ( $pack_get as $pack_name ) {
				// This code builds the command string with the appropriate architecture, release & package name.
				$c_string = "/bin/sh {$config['thebrig']['rootfolder']}/bin/thebrig_fetch.sh {$arch} {$rel_get} {$pack_name} {$config['thebrig']['rootfolder']}/work >/dev/null &";
				// Carries out the fetching operation in the background
				exec( $c_string , $output, $return);
			}// end of for loop
					
		} // end of elseif for "fetch"
	}
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
		}, 5000);
</script>

<table width="100%" border="0" cellpadding="0" cellspacing="0" >
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
			<li class="tabact"><a href="extensions_thebrig_tarballs.php"  title="<?=gettext("Reload page");?>"><span><?=_THEBRIG_TARBALL_MGMT;?></span></a></li>
			<li class="tabinact"><a href="extensions_thebrig_config.php"><span><?=_THEBRIG_BASIC_CONFIG;?></span></a></li>
			
		</ul>
	</td></tr>

	<tr><td class="tabcont">
		<form action="extensions_thebrig_tarballs.php" method="post" name="iform" id="iform">
		<table width="100%" border="0" cellpadding="6" cellspacing="0">
		<tr><td colspan="2" valign="top" class="optsect_t">
			<table border="0" cellspacing="0" cellpadding="0" width="100%">
				<tr><td class="optsect_s"><strong><?=_THEBRIG_CURRENT_TB; ?></strong></td>
					<td align="right" class="optsect_s">
					</td>
				</tr>
			</table>
		</td></tr>

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

		<!--  These next two rows merely output some space between the upper and middle tables -->
		<tr><td colspan="2" valign="top" class="tblnk"></td></tr>
		<tr><td colspan="2" valign="top" class="tblnk"></td></tr>
			
		<!-- This is the table to allow the user to download remote tarballs -->
		<tr><td colspan="2" valign="top" class="optsect_t">
			<div class="optsect_s"><strong><?=_THEBRIG_REMOTE_TB;?></strong></div></td></tr>
			
		<!-- This is the row beneath the title -->
		<tr><td width="22%" valign="top" class="vncellreq"><?=_THEBRIG_REMOTE_AVAIL ?></td>
			<td width="78%" class="vtable">
			
			<?php 
			if ( isset($config['thebrig']['ftpquery']) ){
				// This means we have successfully queried the ftp server, and so can thus display some
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
				$availFiles = "<input type=\"checkbox\" name=\"formPackages[]\" value= \"base\"> base.tbz" ;
				$availFiles .= "<input type=\"checkbox\" name=\"formPackages[]\" value= \"src\"> src.tbz" ;
				$availFiles .= "<input type=\"checkbox\" name=\"formPackages[]\" value= \"doc\"> doc.tbz" ;
				if ( $arch == "amd64") {
					$availFiles .=  "<input type=\"checkbox\" name=\"formPackages[]\" value= \"lib32\" id=\"lib32_box\">lib32.tbz" ;
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
			<td width="22%" valign="top"> </td>
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
				<!--  These next two rows merely output some space between the middle and lower tables -->
		<tr><td colspan="2" valign="top" class="tblnk"></td></tr>
		<tr><td colspan="2" valign="top" class="tblnk"></td></tr>
		
		<!-- This is the table to allow the user to download remote tarballs -->
		<tr><td colspan="2" valign="top" class="optsect_t">
			<div class="optsect_s"><strong><?=_THEBRIG_REMOTE_ACTIVE;?></strong></div></td></tr>
			
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

<?php
	include("fend.inc");
?>