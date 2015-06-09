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
//require_once("XML/Serializer.php");
//require_once("XML/Unserializer.php");
if ( !is_dir ( $config['thebrig']['rootfolder']."/work") ) { $input_errors[] = _THEBRIG_NOT_CONFIRMED; }
$swich_converted = "0";

if (isset($_POST['export']) && $_POST['export']) {

$doc = new DOMDocument('1.0', 'UTF-8');
	$doc->formatOutput = true;
	$elm = $doc->createElement(get_product_name());
	$elm->setAttribute('version', get_product_version());
	$elm->setAttribute('revision', get_product_revision());
	$node = $doc->appendChild($elm);

	// export as XML
	array_sort_key($config['thebrig']['content'], "jailno");
	foreach ($config['thebrig']['content'] as $k => $v) {
		$elm = $doc->createElement('jail');
		foreach ($v as $k2 => $v2) {
			if (is_array($v2)) {
					foreach ($v2 as $k3 => $v3) {
						$elm2 = $doc->createElement($k2, htmlentities($v3));
						$elm->appendChild($elm2);
						//print_r($v3);
					}
			}	else {
			
			$elm2 = $doc->createElement($k2, $v2);
			$elm->appendChild($elm2);
			}
			
			
		}
		$node->appendChild($elm);
	}
	$xml = $doc->saveXML();
	if ($xml === FALSE) {
		$errormsg = gettext("Invalid file format.");
	} else {
		$ts = date("YmdHis");
		$fn = "thebrig-{$config['system']['hostname']}.{$config['system']['domain']}-{$ts}.jails";
		$data = $xml;
		$fs = strlen($data);

		header("Content-Type: application/octet-stream");
		header("Content-Disposition: attachment; filename={$fn}");
		header("Content-Length: {$fs}");
		header("Pragma: hack");
		echo $data;

		exit;

	}
} elseif (isset($_POST['import']) && $_POST['import']) {
		if (is_uploaded_file($_FILES['jailsfile']['tmp_name'])) {
			
			// import from XML
		$xml = file_get_contents($_FILES['jailsfile']['tmp_name']);
		$doc = new DOMDocument();
		$data = array();
		$data['content'] = array();
		if ($doc->loadXML($xml) != FALSE) {
			$doc->normalizeDocument();
			$jails = $doc->getElementsByTagName('jail');
			
			foreach ($jails as $jail) {
				$a = array();
				foreach ($jail->childNodes as $node) {
					if ($node->nodeType != XML_ELEMENT_NODE)
						continue;
					$name = !empty($node->nodeName) ? (string)$node->nodeName : '';
					$value = !empty($node->nodeValue) ? (string)$node->nodeValue : '';
					if (!empty($name))
						$a[$name] = $value;
				}
				$data['content'][] = $a;
			}
		}

		if (empty($data['content'])) {
			$input_errors[] = gettext("Invalid file format.");
		} else {
			// Take care array already exists.
			if (!isset($config['thebrig']['content']) || !is_array($config['thebrig']['content'])) $config['thebrig']['content'] = array();

			// Import jails.
			foreach ($data as $jail) {
				// Check if jail already exists.
				$index = array_search_ex($jail['uuid'], $config['thebrig']['content'], "uuid");
				if (false !== $index) {
					// Create new uuid and mark jail as duplicate (modify description).
					$jail['uuid'] = uuid();
					$jail['desc'] = "*** Imported duplicate ***" . $jail['desc'];
				}
				$config['thebrig']['content'] = $jail;
				updatenotify_set("thebrig", UPDATENOTIFY_MODE_NEW, $jail['uuid']);
			}

			write_config();

			header("Location: extensions_thebrig.php");
			exit;
		}
	} else {
		$input_errors[] = sprintf("%s %s", gettext("Failed to upload file."),
			$g_file_upload_error[$_FILES['jailsfile']['error']]);
	}
} else {}

$pgtitle = array(_THEBRIG_EXTN , _THEBRIG_TITLE, "Tools");
include("fbegin.inc");
// This will evaluate if there were any input errors from prior to the user clicking "save"
if ($input_errors) { 
	print_input_errors($input_errors);
}

?>
<?php if ($errormsg) print_error_box($errormsg);?>

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
	<tr><form action="extensions_thebrig_tools.php" method="post" name="iform" id="iform" enctype="multipart/form-data">
	
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
								<input name="jailsfile" type="file" class="formfld" id="jailsfile" size="45" accept="*.jails" />&nbsp;
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
