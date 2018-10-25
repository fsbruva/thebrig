<?php
/* 
extensions_thebrig_log.php
 

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
// require("diag_log.inc");
require_once("globals.inc");
require_once("rc.inc");
require_once("ext/thebrig/lang.inc");
require_once("ext/thebrig/functions.inc");
if (isset($_GET['log']))
	$log = $_GET['log'];
if (isset($_POST['log']))
	$log = $_POST['log'];
if (empty($log))
	$log = 0;

$loginfo2 = array(
	array(
		"visible" => TRUE,
		"desc" => gettext("Thebrig"),
		"logfile" => $config['thebrig']['rootfolder']."thebrig.log",
		"filename" => "thebrig.log",
		"type" => "plain",
		"pattern" => "/^(\[\S+\s+\S+\s)([a-zA-Z]+\s[a-zA-Z]+.+\!\:)(.+)/",
		"columns" => array(
			array("title" => gettext("Date & Time"), "class" => "listlr", "param" => "nowrap=\"nowrap\"", "pmid" => 1),
			array("title" => gettext("Who"), "class" => "listr", "param" => "nowrap=\"nowrap\"", "pmid" => 2),
			array("title" => gettext("Event"), "class" => "listr", "param" => "", "pmid" => 3)
		))
);
if (is_array($config['thebrig']['content'])) {
$countjails = count_safe($config['thebrig']['content']);
foreach ($config['thebrig']['content'] as $jails) { $jailnames[]= $jails['jailname']; }
for ($i=0;  $i<$countjails; $i++) {
$loginfo1[$i] = array(
		"visible" => TRUE,
		"desc" => $jailnames[$i],
		"logfile" => $config['thebrig']['rootfolder'].$jailnames[$i]."/var/log/messages",
		"filename" => "messages",
		"type" => "plain",
		"pattern" => "/^(\w+\s+\d+\s+\S+)(\s\w+\s\S+)(\:\s.+$)/",
		"columns" => array(
			array("title" => gettext("Date & Time"), "class" => "listlr", "param" => "nowrap=\"nowrap\"", "pmid" => 1),
			array("title" => gettext("Who"), "class" => "listr", "param" => "nowrap=\"nowrap\"", "pmid" => 2),
			array("title" => gettext("Event"), "class" => "listr", "param" => "", "pmid" => 3)
		))
;
}
$loginfo = array_merge_recursive($loginfo2,$loginfo1);
}
else {$loginfo = $loginfo2;}

if (isset($_POST['clear']) && $_POST['clear']) {
	log_clear($loginfo[$log]);
	header("Location: extensions_thebrig_log.php?log={$log}");
	exit;
}

if (isset($_POST['download']) && $_POST['download']) {
	log_download($loginfo[$log]);
	exit;
}

if (isset($_POST['refresh']) && $_POST['refresh']) {
	header("Location: extensions_thebrig_log.php?log={$log}");
	exit;
}

function log_get_contents($logfile, $type) {


	$content = array();

	$param = (isset($config['syslogd']['reverse']) ? "-r " : "");
	$param .= "-n 200";

	switch ($type) {
		case "clog":
			exec("/usr/sbin/clog {$logfile} | /usr/bin/tail {$param}", $content);
			break;

		case "plain":
			exec("/bin/cat {$logfile} | /usr/bin/tail {$param}", $content);
	}

	return $content;
}

function log_display($loginfo) {
	if (!is_array($loginfo))
		return;

	// Create table header
	echo "<tr>";
	foreach ($loginfo['columns'] as $columnk => $columnv) {
		echo "<td {$columnv['param']} class='" . (($columnk == 0) ? "listhdrlr" : "listhdrr") . "'>".htmlspecialchars($columnv['title'])."</td>\n";
	}
	echo "</tr>";

	// Get log file content
	$content = log_get_contents($loginfo['logfile'], $loginfo['type']);
	if (empty($content))
		return;

	// Create table data
	foreach ($content as $contentv) {
		// Skip invalid pattern matches
		$result = preg_match($loginfo['pattern'], $contentv, $matches);
		if ((FALSE === $result) || (0 == $result))
			continue;

		// Skip empty lines
		if (count_safe($loginfo['columns']) == 1 && empty($matches[1]))
			continue;

		echo "<tr valign=\"top\">\n";
		foreach ($loginfo['columns'] as $columnk => $columnv) {
			echo "<td {$columnv['param']} class='{$columnv['class']}'>" . htmlspecialchars($matches[$columnv['pmid']]) . "</td>\n";
		}
		echo "</tr>\n";
	}
}

function log_clear($loginfo) {
	if (!is_array($loginfo))
		return;

	switch ($loginfo['type']) {
		case "clog":
			exec("/usr/sbin/clog -i -s {$loginfo['size']} {$loginfo['logfile']}");
			break;

		case "plain":
			exec("/bin/cat /dev/null > {$loginfo['logfile']}");
	}
}

function log_download($loginfo) {
	if (!is_array($loginfo))
		return;

	$fs = get_filesize($loginfo['logfile']);

	header("Content-Type: application/octet-stream");
	header("Content-Disposition: attachment; filename={$loginfo['filename']}");
	header("Content-Length: {$fs}");
	header("Pragma: hack");

	switch ($loginfo['type']) {
		case "clog":
			exec("/usr/sbin/clog {$loginfo['logfile']}", $content);
			echo implode("\n", $content);
			break;

		case "plain":
			readfile($loginfo['logfile']);
	}
}
$pgtitle = array(_THEBRIG_EXTN , _THEBRIG_TITLE, gettext(" Log"));
?>
<?php include("fbegin.inc");?>
<script type="text/javascript">
<!--
function log_change() {
	// Reload page
	window.document.location.href = 'extensions_thebrig_log.php?log=' + document.iform.log.value;
}
//-->
</script>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr>
		<td class="tabnavtbl">
			<ul id="tabnav">
				<li class="tabinact"><a href="extensions_thebrig.php"><span><?=_THEBRIG_JAILS;?></span></a></li>
				<?php If (!empty($config['thebrig']['content'])) { 
				$thebrigupdates=_THEBRIG_UPDATES;
				echo "<li class=\"tabinact\"><a href=\"extensions_thebrig_update.php\"><span>{$thebrigupdates}</span></a></li>";
				} else {} ?>
				<li class="tabinact"><a href="extensions_thebrig_tarballs.php"><span><?=_THEBRIG_MAINTENANCE;?></span></a></li>
				<li class="tabact"><a href="extensions_thebrig_log.php"><span><?=gettext("Log");?></span></a></li>
			</ul>
		</td>
	</tr>	
	<tr>
    <td class="tabcont">
    	<form action="extensions_thebrig_log.php" method="post" name="iform" id="iform">
				<select id="log" class="formfld" onchange="log_change()" name="log">
					<?php foreach($loginfo as $loginfok => $loginfov):?>
					<?php if (FALSE === $loginfov['visible']) continue;?>
					<option value="<?=$loginfok;?>" <?php if ($loginfok == $log) echo "selected=\"selected\"";?>><?=htmlspecialchars($loginfov['desc']);?></option>
					<?php endforeach;?>
				</select>
				<input name="clear" type="submit" class="formbtn" value="<?=gettext("Clear");?>" />
				<input name="download" type="submit" class="formbtn" value="<?=gettext("Download");?>" />
				<input name="refresh" type="submit" class="formbtn" value="<?=gettext("Refresh");?>" />
				<br /><br />
				<table width="100%" border="0" cellpadding="0" cellspacing="0">
				  <?php log_display($loginfo[$log]);?>
				</table>
				<?php include("formend.inc");?>
			</form>
		</td>
  </tr>
</table>

<?php include("fend.inc");?>
