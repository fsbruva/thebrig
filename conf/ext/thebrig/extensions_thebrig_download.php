<?php
/*
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
<table width="100%" border="0" cellpadding="0" cellspacing="0">
<?php 
// This obtains a list of files that match the criteria (named anything *, excluding FreeBSD)
			// within the /work folder.
			$file_list = thebrig_tarball_list( "FreeBSD*partial*" );
			// This filelist is then used to generate html code with checkboxes
			$DLfiles = thebrig_dl_list( $file_list );
			if ( $DLfiles ) {  // If the array exists and has a size, then display that html code
				echo $DLfiles ;
			}
			else {	// the array does not exist or has no size, so inform user there are no tarballs found.
				echo '<tr><td width="100%" class="tabcont">';
				echo sprintf( _THEBRIG_NO_PART_TB );
				echo '</td></tr>';
			} // end of else (there are currently no valid install files
			?>
</table>
</body>
</html>
