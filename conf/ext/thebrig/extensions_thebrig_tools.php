<?php
/*
 * extensions_thebrig_tools.php
 Autor Alexey Kruglov

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
		} 
		else {
			// Take care array already exists.
			if (!isset($config['thebrig']['content']) || !is_array($config['thebrig']['content'])) {
				$config['thebrig']['content'] = array();
			}

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

$pgtitle = array(_THEBRIG_EXTN , _THEBRIG_TITLE, "Tools");
include("fbegin.inc");
// This will evaluate if there were any input errors from prior to the user clicking "save"
if ($input_errors) { 
	print_input_errors($input_errors);
}
?>

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
			<li class="tabinact"><a href="extensions_thebrig_config.php"><span><?=_THEBRIG_BASIC_CONFIG;?></span></a></li>
			<li class="tabact"><a href="extensions_thebrig_tools.php"  title="<?=gettext("Reload page");?>"><span><?=_THEBRIG_TOOLS;?></span></a></li>
		</ul>
	</td></tr>
	<tr><form action="extensions_thebrig_tools.php" method="post" name="iform" id="iform">
		<td class="tabcont">
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
			 </table>
		</td>
	<?php include("formend.inc");?>
	</form>
	</tr>
</table>
<?php include("fend.inc");?>
