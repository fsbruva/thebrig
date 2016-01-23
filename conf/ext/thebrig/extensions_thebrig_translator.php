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
//require_once("ext/thebrig/functions.inc");
$phpdefine=get_defined_constants(true);
$thebrigdefine1 = $phpdefine['user'];
foreach ($thebrigdefine1 as $key => $entries) {	
// Unset not our values 
		if (0 == preg_match('/UPDATENOTIFY_/',$key)) {
			if (0 == preg_match('/_VERSION_NBR/',$key)) $thebrigdefine[$key] = $entries;
		}}
if (is_file( $config['thebrig']['rootfolder']."conf/ext/thebrig/lang.tmpl")) {
		$pconfig = unserialize (file_get_contents($config['thebrig']['rootfolder']."conf/ext/thebrig/lang.tmpl"));
		
}		
if (isset($_POST['savetmp']) && $_POST['savetmp'] == 'Save template') {
		$pconfig =$_POST;
		unlink_if_exists($config['thebrig']['rootfolder']."conf/ext/thebrig/lang.tmpl");
		file_put_contents( $config['thebrig']['rootfolder']."conf/ext/thebrig/lang.tmpl", serialize($pconfig));	
} elseif (isset($_POST['deltmp']) && $_POST['deltmp'] == 'Delete template') {
		unlink_if_exists($config['thebrig']['rootfolder']."conf/ext/thebrig/lang.tmpl");	
} elseif (isset($_POST['buildlang']) && strlen($_POST['buildlang']) > 10 ) {
		unlink_if_exists("ext/thebrig/lang.tmpl");
		$pconfig =$_POST;
		file_put_contents( $config['thebrig']['rootfolder']."conf/ext/thebrig/lang.tmpl", serialize($pconfig));
		$langfile = "<?php\n/* \n";

		foreach ($pconfig as $key => $value) {
			if ($key == "language") $langfilename = $value."_lang.inc"; 
			elseif ($key == "translatorname") $langfile= $langfile . "Translated by " .$value ."\n*/\n";
			else {$langfile =  $langfile . "define(".$key.", \"".$value."\");\n"; }
		}
		file_put_contents ( $config['thebrig']['rootfolder']."conf/ext/thebrig/".$langfilename, $langfile);	
}
			
		
 else { }	
	

$pgtitle = array(_THEBRIG_EXTN , _THEBRIG_TITLE, _THEBRIG_TOOLS, "translator");
include ("fbegin.inc"); ?>
<script type="text/javascript">
<!--
jQuery("document").ready(function($){
    
    var nav = $('.nav-container');
    
    $(window).scroll(function () {
        if ($(this).scrollTop() > 10) {
            nav.addClass("f-nav");
        } else {
            nav.removeClass("f-nav");
        }
    });
 
});

//-->
</script>
<style type="text/css">
.nav-container{ width: 50%;}
.nav-container ul {
	margin: 0;
	padding-left: 0;
	list-style: none;
}
.nav-container li {
	display: inline;	
}
.f-nav{  z-index: 9999; position: fixed; left: 0; top: 50;  padding: 15 0 15 25;} /* this make our menu fixed  */
.f-nav ul {
	margin: 0;
	padding-left: 20;
	
}
.f-nav li {display:table-row;

}

</style>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr><td class="tabnavtbl">
		<ul id="tabnav"><li class="tabinact"><a href="extensions_thebrig.php"><span><?=_THEBRIG_JAILS;?></span></a></li>
				<?php If (!empty($config['thebrig']['content'])) { 
			$thebrigupdates=_THEBRIG_UPDATES;
			echo "<li class=\"tabinact\"><a href=\"extensions_thebrig_update.php\"><span>{$thebrigupdates}</span></a></li>";
			} else {} ?>
			<li class="tabact"><a href="extensions_thebrig_tarballs.php"><span><?=_THEBRIG_MAINTENANCE;?></span></a></li>
			<li class="tabinact"><a href="extensions_thebrig_log.php"><span><?=_THEBRIG_LOG;?></span></a></li>

		</ul>
	    </td>
	</tr>
	<tr><td class="tabnavtbl">
		<ul id="tabnav1">
			<li class="tabinact"><a href="extensions_thebrig_tarballs.php"><span><?=_THEBRIG_TARBALL_MGMT;?></span></a></li>
			<li class="tabinact"><a href="extensions_thebrig_config.php"><span><?=_THEBRIG_BASIC_CONFIG;?></span></a></li>
			<li class="tabinact"><a href="extensions_thebrig_tools.php"> <span><?=_THEBRIG_TOOLS;?></span></a></li>
			<li class="tabact"><a href="extensions_thebrig_tools.php"  title="<?=gettext("Reload page");?>"><span>Translator</span></a></li>
		</ul>
	</td></tr>
	<tr><form action="extensions_thebrig_translator.php" method="post" name="iform" id="iform" enctype="multipart/form-data">
				<td class="tabcont">
				 <table width="100%" border="0" cellpadding="6" cellspacing="0">
					<?php html_inputbox("translatorname", "Name", $pconfig['translatorname'], "Please type your name", true, 25,false );?>
					<?php html_languagecombobox("language", gettext("Language"), $pconfig['language'], gettext("Select the language for translate"), "", false);?>
					<tr id="functions_tr">
					<td colspan="2" width="100%" valign="top">
					<table width="100%" border="0" cellpadding="0" cellspacing="0">
			 		 	<tr>
							<td width="16%" class="listhdrr"><?="Defined variable";?></td>
							<td width="42%" class="listhdrr">Original value</td>
							<td width="42%" class="listhdrr">Translated</td>
						</tr>
						<?php foreach ($thebrigdefine as $key => $value):?>
						<tr id="translate_tr">	
							<td class="listr"><?=htmlspecialchars($key);?></td>
							<td class="listr"><?=htmlspecialchars($value);?></td>
							<td class="listr"><input type = text size="150" maxlength="350" name="<?=htmlspecialchars($key);?>" value="<?=htmlspecialchars($pconfig[$key]);?>"></text></td>
						</tr>
						<?php endforeach; ?>
						</table>
			</td>
				<div class="nav-container">
					<ul>
						<li ><input name="savetmp" type="submit" class="formbtn" value="Save template" /></li>
						<li ><input name="deltmp" type="submit" class="formbtn" value="Delete template" /></li>
						<li ><input name="buildlang" type="submit" class="formbtn" value="Create lang file" /></li>
						
					</ul>	
				</div>
							
			 </table>
			 </td></tr>
			 </table>
		</td>
	<?php include("formend.inc");?>
	</form>
	</tr>
</table>
<?php include ("fend.inc"); ?>

?>