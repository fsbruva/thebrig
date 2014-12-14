<?php
/*
 * extensions_thebrig_tools.php
 Autor Alexey Kruglov
*/
require("auth.inc");
require("guiconfig.inc");
require_once("ext/thebrig/lang.inc");
require_once("ext/thebrig/functions.inc");
require_once("XML/Serializer.php");
require_once("XML/Unserializer.php");
if ( !is_dir ( $config['thebrig']['rootfolder']."/work") ) { $input_errors[] = _THEBRIG_NOT_CONFIRMED; }
$swich_converted = "0";

if (isset($_POST['export']) && $_POST['export']) {
	$options = array(
			XML_SERIALIZER_OPTION_XML_DECL_ENABLED => true,
			XML_SERIALIZER_OPTION_INDENT           => "\t",
			XML_SERIALIZER_OPTION_LINEBREAKS       => "\n",
			XML_SERIALIZER_OPTION_XML_ENCODING     => "UTF-8",
			XML_SERIALIZER_OPTION_ROOT_NAME        => get_product_name(),
			XML_SERIALIZER_OPTION_ROOT_ATTRIBS     => array("version" => get_product_version(), "revision" => get_product_revision()),
			XML_SERIALIZER_OPTION_DEFAULT_TAG      => "content",
			XML_SERIALIZER_OPTION_MODE             => XML_SERIALIZER_MODE_DEFAULT,
			XML_SERIALIZER_OPTION_IGNORE_FALSE     => true,
			XML_SERIALIZER_OPTION_CONDENSE_BOOLS   => true,
	);

	$serializer = new XML_Serializer($options);
	$status = $serializer->serialize($config['thebrig']['content']);

	if (@PEAR::isError($status)) {
		$errormsg = $status->getMessage();
	} else {
		$ts = date("YmdHis");
		$fn = "thebrig-{$config['system']['hostname']}.{$config['system']['domain']}-{$ts}.jails";
		$data = $serializer->getSerializedData();
		$fs = strlen($data);

		header("Content-Type: application/octet-stream");
		header("Content-Disposition: attachment; filename={$fn}");
		header("Content-Length: {$fs}");
		header("Pragma: hack");
		echo $data;

		exit;
	}
} else if (isset($_POST['import']) && $_POST['import']) {
	if (is_uploaded_file($_FILES['jailsfile']['tmp_name'])) {
		$options = array(
				XML_UNSERIALIZER_OPTION_COMPLEXTYPE => 'array',
				XML_UNSERIALIZER_OPTION_ATTRIBUTES_PARSE => true,
				XML_UNSERIALIZER_OPTION_FORCE_ENUM  => $listtags,
		);

		$unserializer = new XML_Unserializer($options);
		$status = $unserializer->unserialize($_FILES['jailsfile']['tmp_name'], true);

		if (@PEAR::isError($status)) {
			$errormsg = $status->getMessage();
		} else {
			// Take care array already exists.
			if (!isset($config['thebrig']['content']) || !is_array($config['thebrig']['content']))
				$config['thebrig']['content'] = array();

			$data = $unserializer->getUnserializedData();

			// Import jails.
			foreach ($data['content'] as $jail) {
				// Check if jail already exists.
				$index = array_search_ex($jail['uuid'], $config['thebrig']['content'], "uuid");
				if (false !== $index) {
					// Create new uuid and mark jail as duplicate (modify description).
					$content['uuid'] = uuid();
					$content['desc'] = gettext("*** Imported duplicate ***") . " {$jail['desc']}";
				}
				$config['thebrig']['content'][] = $jail;

				updatenotify_set("thebrig", UPDATENOTIFY_MODE_NEW, $jail['uuid']);
			}

			write_config();

			header("Location: extensions_thebrig.php");
			exit;
		}
	} else {
		$errormsg = sprintf("%s %s", gettext("Failed to upload file."),
				$g_file_upload_error[$_FILES['jailsfile']['error']]);
	}
}


if ($_POST['submit'] === "Convert") {
	
		if ( empty ( $_POST['oldconfig']) || !is_file($_POST['oldconfig']) ) { $input_errors[] = gettext("No valid file"); goto out1;}
	$pconfig=$_POST;
	$oldconfig = file($_POST['oldconfig']);
	$matches  = preg_grep ('/^jail_\S+".+"/', $oldconfig); //  remove no valid lines
	$matches = array_values($matches); // sanitize key numbers
		$j=0; // do sanitize from online comments
		do $matches1[$j]= rtrim(preg_replace ('/"\W+\#.*$/', '"', $matches[$j]));
		while ($j++<(count($matches)-1));  // end sanitize from line comments
		$matches1 =array_values($matches1);
		$prsconfig['jailnames'] = array(); // Define output array
		$jailnames = preg_grep ( '/^.*_hostname=.*/i', $matches1 );
		$jailnames =array_values($jailnames);
		foreach ($jailnames  as $item) { $parts = explode('=', $item); 	$jailnames1[]=str_replace('"', '', $parts[1]);}
		foreach ($jailnames1  as $item) { $parts = explode('.', $item); 	$prsconfig['jailnames'][]=$parts[0];}
		
		// Now I have array with jail names
		
		// I begin extract globals variable and compose as name => value I want to extract only 4 values, because I not need jail_enable and jail_list
		$glpattern = array( "jail_parallel_start", "jail_set_hostname_allow", "jail_socket_unixiproute_only", "jail_sysvipc_allow");
		$tmp = array();
		for ($i=0; $i<4;){
		$patt = $glpattern[$i];
		for ($j=0; $j<(count($matches1));){  $item = ($matches1[$j]); 	
				$parts = explode('=', $item);
					// I use strlen for value "YES".  strlen "YES" =5, strlen "NO" = 4
					if ($parts[0] == $patt) { if( strlen($parts[1]) == 5)  { 
							$tmp[$i] = str_replace('"', '', $parts[1]); $tmp1[$i] = $parts[0]; } 
							} 
						else{} 					
			++$j;	}

		++$i;} 

		// alljails  parsing
		// $etalon is array with standart FreeBSD names for rc.conf
	$etalon = array("3"=>"_hostname", "4"=>"_interface", "5"=>"_ip", "7"=>"_rootdir", "9"=>"_mount_enable", "10"=>"_devfs_enable", "11"=>"_procfs_enable", "12"=>"_fdescfs_enable", "14"=>"_exec_start", "15"=>"_exec_afterstart0", "16"=>"_exec_afterstart1", 
		"17"=>"_exec_stop", "18"=>"_flags" );
		// parsing....
	for ($i=0; $i<(count($prsconfig['jailnames']));){
		for ($j=0; $j<20;){
				$patt1 = "jail_".$prsconfig['jailnames'][$i].$etalon[$j];
					for ($k=0; $k<(count($matches1));){  
						$item = ($matches1[$k]); 	
						$parts = explode('=', $item);
						if ($parts[0] == $patt1) { $prsconfig['values'][$i][$j] = str_replace('"', '', $parts[1]); $prsconfig['names'][$i][$j] = $parts[0]; }  else{}
						++$k;		}
				++$j; 	}
		++$i;	}
	// sanitize from domain name
	for ($i=0; $i<(count($prsconfig['jailnames']));) {$prsconfig['values'][$i][3] = $prsconfig['jailnames'][$i]; ++$i; } 
	
		// Prsconfig['thebrig'] is array with thebrig variables, but it have some keys numbers as $prsconfig['names']
		$prsconfig['thebrig'] = array ("0" =>"uuid", "1" => "enable", "2"=>"jailno", "3"=>"jailname", "4"=>"if", "5"=>"ipaddr",	"6"=>"subnet", "7"=>"jailpath", "8"=>"dst",	"9"=>"jail_mount",
		"10"=> "devfs_enable",	"11"=> "proc_enable", 	"12"=> "fdescfs_enable", "13"=>"fstab",	"14"=>"exec_start", "15"=>"afterstart0",	"16"=>"afterstart1", "17"=>"exec_stop",	"18"=>"extraoptions",
		"19"=>"desc", "20"=>"base_ver",	"21"=>"lib_ver", "22"=>"src_ver",	"23"=>"doc_ver", "24"=>"image",	"25"=>"image_type",	"26"=>"attach_params",	"27"=>"attach_blocking",
		"28"=>"force_blocking",		"29"=>"zfs_datasets",	"30"=>"fib", "31"=>"type", );

		// add array startonboot
	for ($j=0; $j<(count($matches1));){  
				$item = ($matches1[$j]); 	
				$parts = explode('=', $item);
				if ($parts[0] == "jail_list")  { $jaillist = str_replace('"', '', $parts[1]); } 					
			++$j;	
		}
	$prsconfig['startonboot']= explode(' ', $jaillist);

		//Add values jailno, uuid, startonboot  to jails  values 
	for ($i=0; $i<(count($prsconfig['jailnames'] ) ) ; ) {
		$prsconfig['values'][$i]['2'] = ($i+1);
		$prsconfig['values'][$i]['31']="full";
		$prsconfig['values'][$i]['0'] = uuid();
			for ($j=0; $j<(count($prsconfig['startonboot']));){ 
				if ($prsconfig['startonboot'][$j] == $prsconfig['values'][$i][3]) { 
					$prsconfig['values'][$i][1] = "Yes";} 
				else {}
				++$j;	}
		++$i;	
	}

//
// I have output array $prsconfig with
// $prsconfig['thebrig']  - thebrig variables --------------------------------| this arrays have some keys!!
// $prsconfig['names']  -  standart FreeBSD names, used in rc.conf |local|----|
// $prsconfig['values']  - values from rc.config |local| but ready to apply for Thebrig.
//
// I prepare Array for xml writer
	for ($i=0; $i<(count($prsconfig['jailnames'] ) ) ; ) {
			for ($j=0; $j<(count($prsconfig['thebrig'] ) ) ; ) {
					if( ! empty ( $prsconfig['values'][$i][$j]) ) { $testmulti[$i]["{$prsconfig['thebrig'][$j]}"] = $prsconfig['values'][$i][$j];} 
					else {unset($testmulti[$i]["{$prsconfig['thebrig'][$j]}"] ) ; }
					++$j;	}
			++$i;	} 
			// and try

		$options = array(
		XML_SERIALIZER_OPTION_XML_DECL_ENABLED => true,
		XML_SERIALIZER_OPTION_INDENT           => "\t",
		XML_SERIALIZER_OPTION_LINEBREAKS       => "\n",
		XML_SERIALIZER_OPTION_XML_ENCODING     => "UTF-8",
		XML_SERIALIZER_OPTION_ROOT_NAME        => get_product_name(),
		XML_SERIALIZER_OPTION_ROOT_ATTRIBS     => array("version" => get_product_version(), "revision" => get_product_revision()),
		XML_SERIALIZER_OPTION_DEFAULT_TAG      => "content",
		XML_SERIALIZER_OPTION_MODE             => XML_SERIALIZER_MODE_DEFAULT,
		XML_SERIALIZER_OPTION_IGNORE_FALSE     => true,
		XML_SERIALIZER_OPTION_CONDENSE_BOOLS   => true,
	);

	$serializer = new XML_Serializer($options);
	$status = $serializer->serialize($testmulti);

	if (@PEAR::isError($status)) {
		$errormsg = $status->getMessage();
		} else {
		$ts = date("YmdHis");
		$fn = "thebrig-{$config['system']['hostname']}.{$config['system']['domain']}-{$ts}.jails";
		$data = $serializer->getSerializedData();
		$fs = strlen($data);

		header("Content-Type: application/octet-stream");
		header("Content-Disposition: attachment; filename={$fn}");
		header("Content-Length: {$fs}");
		header("Pragma: hack");
		echo $data;

		exit;
		} 
} 
if ($_POST['submit'] === "Compress") {
		if ( empty ( $_POST['fname']) || !is_dir($_POST['fname']) ) { $input_errors[] = gettext("No valid folder"); goto out1;}
		$pconfig=$_POST;
		$foldername = $_POST['fname'];
		if (strlen($foldername) > 6 && $path[strlen($foldername)-1] == "/") { $foldername = substr($foldername, 0, strlen($foldername)-1); } 
		$path_parts = pathinfo($foldername);
		$timestamp = date("Y-m-d_H:i:s");
		chdir($foldername);
		mwexec("tar -c -z -f " . $config['thebrig']['rootfolder'] . "work/snached_" . $timestamp . ".txz -C" . $path_parts['dirname'] . "/".$path_parts['basename']. " " . "*");
		// This tool make archive any folder and place archive into thebrig/work.  Usefull for easy migrate old jails.
}
out1: 
$pgtitle = array(_THEBRIG_EXTN , _THEBRIG_TITLE, "Tools");
include("fbegin.inc");?>

<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr><td class="tabnavtbl">
		<ul id="tabnav"><li class="tabinact"><a href="extensions_thebrig.php"><span><?=_THEBRIG_JAILS;?></span></a></li>
				<?php If (!empty($config['thebrig']['content'])) { 
			$thebrigupdates=_THEBRIG_UPDATES;
			echo "<li class=\"tabinact\"><a href=\"extensions_thebrig_update.php\"><span>{$thebrigupdates}</span></a></li>";
			} else {} ?>
			<li class="tabact"><a href="extensions_thebrig_tarballs.php"><span><?=_THEBRIG_MAINTENANCE;?></span></a></li>
			<li class="tabinact"><a href="extensions_thebrig_log.php"><span><?=gettext("Log");?></span></a></li>

		</ul>
	    </td>
	</tr>
	<tr><td class="tabnavtbl">
		<ul id="tabnav2">
			<li class="tabinact"><a href="extensions_thebrig_tarballs.php"><span><?=_THEBRIG_TARBALL_MGMT;?></span></a></li>
			<li class="tabinact"><a href="extensions_thebrig_config.php" title="<?=gettext("Reload page");?>"><span><?=_THEBRIG_BASIC_CONFIG;?></span></a></li>
			<li class="tabact"><a href="extensions_thebrig_tools.php"><span><?=_THEBRIG_TOOLS;?></span></a></li>
		</ul>
	</td></tr>
	<tr><form action="extensions_thebrig_tools.php" method="post" name="iform" id="iform">
		<td class="tabcont">
			 <?php if (!empty($input_errors)) print_input_errors($input_errors); ?>
			 <table width="100%" border="0" cellpadding="6" cellspacing="0">
			 
			 	<?php html_titleline(gettext("Configuration Backup/Restore"));?>
			 	<tr>
						<td width="22%" valign="top" class="vncell">Backup Existing Config&nbsp;</td>
						<td width="78%" class="vtable">
							<?=gettext("Make a backup of the existing configuration.");?><br />
							<div id="submit">
								<input name="export" type="submit" class="formbtn" value="<?=gettext("Export");?>" /><br />
							</div>
						</td>
					</tr>
					<tr>
						<td width="22%" valign="top" class="vncell">Restore&nbsp;</td>
						<td width="78%" class="vtable">
							<?=gettext("Restore jails config from XML.");?><br />
							<div id="submit">
								<input name="jailsfile" type="file" class="formfld" id="jailsfile" size="40" accept="*.jails" />&nbsp;
								<input name="import" type="submit" class="formbtn" id="import" value="<?=gettext("Import");?>" /><br />
							</div>
						</td>
					</tr>
			 	
			 	<?php html_separator();
				html_titleline(gettext("rc.conf.local Migrator"));?>
				<?php html_filechooser("oldconfig", gettext("Path to source"), $pconfig['oldconfig'], sprintf(gettext("If you want convert old rc.conf.local to TheBrig application, please add path to it."), $pconfig['name']), "/mnt/", false);
				html_text($confconv, gettext("Convert and download xml"), '<input name="submit" type="submit" value="Convert">') ?>
				
									
				<?php html_separator();
				html_titleline(gettext("Jail Archiver"));?>
				<?php  html_filechooser("fname", gettext("Path to folder"), $pconfig['fname'], sprintf(gettext("If you want archive old jail or any another folder choose it. It will be compressed and stored in " . $config['thebrig']['rootfolder'] . "work/."), $pconfig['fname']), "/mnt/", false);
				html_text($confconv, gettext("Compress and save"), '<input name="submit" type="submit" value="Compress">') ?>

	     
			 </table>
		</td>
	<?php include("formend.inc");?>
	</form>
	</tr>
</table>
<?php include("fend.inc");?>
