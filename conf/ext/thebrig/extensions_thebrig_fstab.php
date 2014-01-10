<?php
/*
     extensions_thebrig_fstab.php   Autor Alexey Kruglov 2013
*/
require("auth.inc");
require("guiconfig.inc");
ob_start();
$k=0;
if ($_GET) {
unset ($input_errors);
print_r ($_GET);
	if ($_GET['act'] == "editor") {
	$link = $_SERVER['HTTP_REFERER'];
	$uuid = $_GET['uuid'];
	$pconfig['uuid'] = $uuid;
	$p_config = array();
	$a_jail = &$config['thebrig']['content'];
	if (isset($uuid) && (FALSE !== ($cnid = array_search_ex($uuid, $a_jail, "uuid")))) {
	$p_config = $a_jail[$cnid];
	$tmpfile = "/tmp/tempjail";
    $handle = fopen($tmpfile,"wb");
	fwrite ($handle, "uuid:".$a_jail[$cnid]['uuid']."\n"); //0
	if(isset($a_jail[$cnid]['enable'])) {fwrite ($handle, "enable:yes\n");} else { fwrite ($handle, "enable:no\n" );}
	fwrite ($handle, "jailno:".$a_jail[$cnid]['jailno']."\n");
	fwrite ($handle, "jailname:".$a_jail[$cnid]['jailname']."\n");
	fwrite ($handle, "type:".$a_jail[$cnid]['type']."\n");
	fwrite ($handle, "if:".$a_jail[$cnid]['if']."\n");
	fwrite ($handle, "ipaddr:".$a_jail[$cnid]['ipaddr']."\n");
	fwrite ($handle, "subnet:".$a_jail[$cnid]['subnet']."\n");
	fwrite ($handle, "jailpath:".$a_jail[$cnid]['jailpath']."\n");
	if(isset($a_jail[$cnid]['jail_mount'])) {fwrite ($handle, "jail_mount:yes\n");} else { fwrite ($handle, "jail_mount:no\n" );}
	if (isset($a_jail[$cnid]['devfs_enable'])) {fwrite ($handle, "devfs_enable:yes\n");} else { fwrite ($handle, "devfs_enable:no\n" );} //10
	if(isset($a_jail[$cnid]['proc_enable'])) {fwrite ($handle, "proc_enable:yes\n");} else { fwrite ($handle, "proc_enable:no\n" );}
	if(isset($a_jail[$cnid]['fdescfs_enable'])) {fwrite ($handle, "fdescfs_enable:yes\n");} else { fwrite ($handle, "fdescfs_enable:no\n" );}
	if (isset($a_jail[$cnid]['auxparam']) && is_array($a_jail[$cnid]['auxparam']))  {	$p_config['auxparam'] = implode(",", $a_jail[$cnid]['auxparam']); } 
	fwrite ($handle, "auxparam:".$p_config['auxparam']."\n");/*13*/
	fwrite ($handle, "exec_prestart:".$a_jail[$cnid]['exec_prestart']."\n");
	fwrite ($handle, "exec_start:".$a_jail[$cnid]['exec_start']."\n");
	fwrite ($handle, "afterstart0:".$a_jail[$cnid]['afterstart0']."\n");
	fwrite ($handle, "afterstart1:".$a_jail[$cnid]['afterstart1']."\n");
	fwrite ($handle, "exec_stop:".$a_jail[$cnid]['exec_stop']."\n");
	fwrite ($handle, "extraoptions:".$a_jail[$cnid]['extraoptions']."\n");
	fwrite ($handle, "jail_parameters:".$a_jail[$cnid]['jail_parameters']."\n");
	fwrite ($handle, "desc:".$a_jail[$cnid]['desc']."\n");
	fwrite ($handle, "base_ver:".$a_jail[$cnid]['base_ver']."\n");
	fwrite ($handle, "lib_ver:".$a_jail[$cnid]['lib_ver']."\n");
	fwrite ($handle, "src_ver:".$a_jail[$cnid]['src_ver']."\n");
	fwrite ($handle, "doc_ver:".$a_jail[$cnid]['doc_ver']."\n");
	fwrite ($handle, "image:".$a_jail[$cnid]['image']."\n");
	fwrite ($handle, "image_type:".$a_jail[$cnid]['image_type']."\n");
	fwrite ($handle, "attach_params:".$a_jail[$cnid]['attach_params']."\n");
	fwrite ($handle, "attach_blocking:".$a_jail[$cnid]['attach_blocking']."\n");
	fwrite ($handle, "force_blocking:".$a_jail[$cnid]['force_blocking']."\n");
	fwrite ($handle, "zfs_datasets:".$a_jail[$cnid]['zfs_datasets']."\n");
	if(isset($a_jail[$cnid]['fib'])) {fwrite ($handle, "fib:yes\n");} else { fwrite ($handle, "fib:no\n" );}
	if(isset($a_jail[$cnid]['ports'])) {fwrite ($handle, "ports:yes\n");} else { fwrite ($handle, "ports:no\n" );}
	// By default, when editing an existing jail, path and name will be read only.
	$path_ro = true;
	$name_ro = true;
	if ( !is_dir( $p_config['jailpath']) ) {
		$input_errors[] = "The specified jail location does not exist - probably because you imported the jail's config. Please choose another.";
		$path_ro = false;
	}
	if ( (FALSE !== ( $ncid = array_search_ex($p_config['jailname'], $a_jail, "jailname"))) && $ncid !== $cnid ){
		$input_errors[] = "The specified jailname is a duplicate - probably because you imported the jail's config. Please choose another.";	
		$name_ro = false;
			}
	}
	else { $input_errors[]=" Jail not defined!  Please define jail over Add|Edit tab and push <b>Add</b> button for store configuration into config.xml "; goto menu; }
		if (isset($a_jail[$cnid]['auxparam']) && is_array($a_jail[$cnid]['auxparam'])) {
			$fstab = $a_jail[$cnid]['auxparam'];
			$linenumbers = count($fstab);
			if (empty($fstab[0])) $linenumbers=0;
			foreach ($fstab as $fstabline) {
			$fstabentry = explode(" ",$fstabline);
			$fstabentry1[] =	$fstabentry;	
					} 
				}
		$fstabfile= "/tmp/fstab.edit";
		$handle1 = fopen($fstabfile, "wb");
		foreach ($fstab as $fstab1) {	fwrite ($handle1, $fstab1."\n"); } 
		fclose($handle1);
	}
	if ($_GET['act'] == "tempedit") {
		$link = $_GET['referer'];
		$pconfig['referer'] = $_GET['referer'];
		$pconfig['uuid'] = $_GET['uuid'];
		$uuid=$pconfig['uuid'];
		$k	=	$_GET['numberline'];
		for ($j = 0; $j <= $k; $j++) { $result[$j] = $_GET['device'][$j]." ".$_GET['mountpoint'][$j]." ".$_GET['filesys'][$j]." ".$_GET['option'][$j]." ".$_GET['dump'][$j]." ".$_GET['fsck'][$j]; }
		$fstabfile= "/tmp/fstab.edit";
		$handle1 = fopen($fstabfile, "wb");
		foreach ($result as $fstab1) {	fwrite ($handle1, trim($fstab1)."\n"); } 
		fclose($handle1);
	}
	if ($_GET['act'] == "delete") {
		$pconfig['uuid'] = $_GET['uuid'];
		$uuid=$pconfig['uuid'];
		$link = $_GET['referer'];
		$pconfig['referer'] = $_GET['referer'];
		$fstabfile= "/tmp/fstab.edit";
		$line = $_GET['line'];
		//$numberline=$_GET['numberline'];
		$result1 = file($fstabfile);
		$numberline = count($result1);
		for ($i = 0; $i < $numberline; $i++) {
			$result1[$i] = trim($result1[$i]); //sanitize array
			}
		unset ($result1[$line]);
		$result = array_diff($result1, array(''));
		$handle1 = fopen($fstabfile, "wb");
		foreach ($result as $fstab1) {	fwrite ($handle1, $fstab1."\n"); } 
		fclose($handle1);
	}
}
//============================this is post============================
if(isset($_POST["Cancel"])) { ob_start();
	$link = $_POST['referer'];
	print_r ($_POST);
	 header("Location: ".$link);
	}
if(isset($_POST['Submit'])) {
	$link = $_POST['referer'];
	$pconfig['referer'] = $_POST['referer'];
	$pconfig['uuid'] = $_POST['uuid'];
	$uuid = $pconfig['uuid'];
	$fstabfile= "/tmp/fstab.edit";
	$result1 = file($fstabfile);
	$numberline = count($result1);
	for ($i = 0; $i < $numberline; $i++) {
			$result2[$i] = trim($result1[$i]); //sanitize array
			}
	$result = array_diff($result2, array(''));
	$frombackup=file("/tmp/tempjail");
	$a_jail = &$config['thebrig']['content'];
	if (isset($uuid) && (FALSE !== ($cnid = array_search_ex($uuid, $a_jail, "uuid")))) {
		$jail = array();
		$a_frombackup = explode(":", $frombackup[0]);
				$jail['uuid'] = trim ($a_frombackup[1], "\n");
		$a_frombackup = explode(":", $frombackup[1]);
				if (strlen($a_frombackup[1]) > 3) { $jail['enable'] = true; } else { unset($jail['enable']); }
		$a_frombackup = explode(":", $frombackup[2]);
				$jail['jailno'] = trim ($a_frombackup[1], "\n");
		$a_frombackup = explode(":", $frombackup[3]);
				$jail['jailname'] = trim ($a_frombackup[1], "\n");
		$a_frombackup = explode(":", $frombackup[4]);
				$jail['type'] = trim ($a_frombackup[1], "\n");
		$a_frombackup = explode(":", $frombackup[5]);
				$jail['if'] = trim ($a_frombackup[1], "\n");
		$a_frombackup = explode(":", $frombackup[6]);
				$jail['ipaddr'] = trim ($a_frombackup[1], "\n");
		$a_frombackup = explode(":", $frombackup[7]);
				$jail['subnet'] = trim ($a_frombackup[1], "\n");
		$a_frombackup = explode(":", $frombackup[8]);
				$jail['jailpath'] = trim ($a_frombackup[1], "\n");
		$a_frombackup = explode(":", $frombackup[9]);
				if (strlen($a_frombackup[1]) > 3) {	$jail['jail_mount'] = true; } else { unset($jail['jail_mount']); }
		$a_frombackup = explode(":", $frombackup[10]);
				if (strlen ($a_frombackup[1]) > 3) {	$jail['devfs_enable'] = true; } else { unset($jail['devfs_enable']); }
		$a_frombackup = explode(":", $frombackup[11]);
				if (strlen ($a_frombackup[1]) > 3) {	$jail['proc_enable'] = true; } else { unset($jail['proc_enable']); }
		$a_frombackup = explode(":", $frombackup[12]);
				if (strlen ($a_frombackup[1]) > 3) { $jail['fdescfs_enable'] = true; } else { unset($jail['fdescfs_enable']); }
		unset($jail['auxparam']);
				$jail['auxparam'] = $result;
		$a_frombackup = explode(":", $frombackup[14]);
				$jail['exec_prestart'] = trim ($a_frombackup[1], "\n");
		$a_frombackup = explode(":", $frombackup[15]);		
				$jail['exec_start'] = trim ($a_frombackup[1], "\n");
		$a_frombackup = explode(":", $frombackup[16]);
				$jail['afterstart0'] = trim ($a_frombackup[1], "\n");
		$a_frombackup = explode(":", $frombackup[17]);
				$jail['afterstart1'] = trim ($a_frombackup[1], "\n");
		$a_frombackup = explode(":", $frombackup[18]);
				$jail['exec_stop'] = trim ($a_frombackup[1], "\n");
		$a_frombackup = explode(":", $frombackup[19]);
				$jail['extraoptions'] = trim ($a_frombackup[1], "\n");
		$a_frombackup = explode(":", $frombackup[20]);
				$jail['jail_parameters'] = trim ($a_frombackup[1], "\n");
		$a_frombackup = explode(":", $frombackup[21]);
				$jail['desc'] = trim ($a_frombackup[1], "\n");
		$a_frombackup = explode(":", $frombackup[22]);
			$jail['base_ver'] = trim ($a_frombackup[1], "\n");
		$a_frombackup = explode(":", $frombackup[23]);
			$jail['lib_ver'] = trim ($a_frombackup[1], "\n");
		$a_frombackup = explode(":", $frombackup[24]);
			$jail['src_ver'] = trim ($a_frombackup[1], "\n");
		$a_frombackup = explode(":", $frombackup[25]);
			$jail['doc_ver'] = trim ($a_frombackup[1], "\n");
		$a_frombackup = explode(":", $frombackup[26]);
			$jail['image'] = trim ($a_frombackup[1], "\n");
		$a_frombackup = explode(":", $frombackup[27]);
			$jail['image_type'] = trim ($a_frombackup[1], "\n");
		$a_frombackup = explode(":", $frombackup[28]);
			$jail['attach_params'] = trim ($a_frombackup[1], "\n");
		$a_frombackup = explode(":", $frombackup[29]);
			$jail['attach_blocking'] = trim ($a_frombackup[1], "\n");
		$a_frombackup = explode(":", $frombackup[30]);
			$jail['force_blocking'] = trim ($a_frombackup[1], "\n");
		$a_frombackup = explode(":", $frombackup[31]);
			$jail['zfs_datasets'] = trim ($a_frombackup[1], "\n");
		$a_frombackup = explode(":", $frombackup[32]);
			if (strlen ($a_frombackup[1]) > 3) { $jail['fib']  = true; } else { unset($jail['fib'] ); }
		$a_frombackup = explode(":", $frombackup[33]);
			if (strlen ($a_frombackup[1]) > 3) { $jail['ports']  = true; } else { unset($jail['ports'] ); }
				
		if (isset($uuid) && (FALSE !== ($cnid = array_search_ex($uuid, $a_jail, "uuid")))) {
				// Copies newly modified properties over the old
			$a_jail[$cnid] = $jail;
			$mode = UPDATENOTIFY_MODE_MODIFIED;
		} else {
			// Copies the first jail into $a_jail
			$a_jail[] = $jail;
			$mode = UPDATENOTIFY_MODE_NEW;
		}
		updatenotify_set("thebrig", $mode, $jail['uuid']);
		write_config();
				}
	unlink ("/tmp/fstab.edit");
	unlink ("/tmp/tempjail");
	 header("Location: ".$link);

}
menu:
?>
<style>

.formdata input:focus, .formdata input.hilite {
	border: 2px solid #000000;
}
</style>
	
<script type="text/javascript">
<!--
function cf(e,f) {
  for (var i=0; i<e.childNodes.length; i++) {
    var el = e.childNodes[i];
    var elName = el.nodeName.toLowerCase();
    if (elName=='input' && el.name!='') {
      var type = el.type.toLowerCase();
      switch (type) {
        case 'text': {
          var tmp_el = document.createElement("input");
          tmp_el.name=el.name;
          tmp_el.type='hidden';
          tmp_el.value=el.value;
          f.appendChild(tmp_el);
          break;
        }
        case 'checkbox': {
          if (el.checked) {
            var tmp_el = document.createElement("input");
            tmp_el.name=el.name;
            tmp_el.type='checkbox';
            tmp_el.value=el.value;
            f.appendChild(tmp_el);
            tmp_el.checked=true;
          }
          break;
        }
        case 'radio': {
          if (el.checked) {
            var tmp_el = document.createElement("input");
            tmp_el.name=el.name;
            tmp_el.type='radio';
            tmp_el.value=el.value;
            f.appendChild(tmp_el);
            tmp_el.checked=true;
          }
          break;
        }
        case 'hidden': {
          var tmp_el = document.createElement("input");
          tmp_el.name=el.name;
          tmp_el.type='hidden';
          tmp_el.value=el.value;
          f.appendChild(tmp_el);
          break;
        }
        case 'password': {
          var tmp_el = document.createElement("input");
          tmp_el.name=el.name;
          tmp_el.type='hidden';
          tmp_el.value=el.value;
          f.appendChild(tmp_el);
          break;
        }
        default: {
          break;
        }
      }
    }
    else if (elName=='textarea' && el.name!='') {
      var tmp_el = document.createElement("textarea");
      tmp_el.name=el.name;
      tmp_el.value=el.value;
      f.appendChild(tmp_el);
    }
    else if (elName=='select' && el.name!='') {
      var tmp_el = document.createElement("input");
      tmp_el.name=el.name;
      tmp_el.type='hidden';
      tmp_el.value=el.value;
      f.appendChild(tmp_el);
    }
    else {
      cf(el,f);
    }
  }
}
function ds(f) {
  var e=document.getElementById(f);
  if (!e) return false;
  var tmp_form = document.createElement("form");
  tmp_form.method='get';
  tmp_form.action='extensions_thebrig_fstab.php';
  tmp_form.style.display='none';
  document.getElementsByTagName('body')[0].appendChild(tmp_form);
  cf(e,tmp_form);
  tmp_form.submit();
}

//-->

</script>
<!----Script from this page, many thanks http://www.manhunter.ru/webmaster/343_kak_otpravit_iz_formi_html_tolko_chast_dannih.html ---->

<?php $pgtitle = array("Fstab", "edit"); ?>
<?php include ("fbegin.inc"); ?>
<body>
<table width="100%" border="0" cellpadding="0" cellspacing="0" >
<tr><td class="tabcont">
<?php if ($input_errors) print_input_errors($input_errors);?>
<form action="extensions_thebrig_fstab.php" method="post" name="iform1" id="iform1">
	<div id="wrapper0">
		<table class="formdata" width="100%" border="0" cellpadding="5" cellspacing="0">
						
							<tr><td width="5%" class="listhdrlr"><?=gettext("number");?></td>
							<td width="25%" class="listhdrlr"><?=gettext("device");?></td>
								<td width="35%" class="listhdrc"><?=gettext("Mount point");?></td>
								<td width="10%" class="listhdrc"><?=gettext("Filesystem");?></td>
								<td width="10%" class="listhdrc"><?=gettext("Option");?></td>
								<td width="5%" class="listhdrc"><?=gettext("dumpfreq");?></td>
								<td width="5%" class="listhdrc"><?=gettext("fsck");?></td>
								<td width="5%" class="listhdrc"></td>
							</tr>
		<?php // this line need for analystic from host
					$fstabfile= file("/tmp/fstab.edit");
					$countfstabfile=count($fstabfile);
					if (($countfstabfile==1) && ( filesize("/tmp/fstab.edit") < 12)) $countfstabfile = 0;
					for ($i = 0; $i <= $countfstabfile; $i++):
					$fstabelement[$i] = explode(" ",$fstabfile[$i]); ?>
					<tr><td width='5%'" class='listr'><span class='vexpl'><?php if ($i < $countfstabfile) echo (1+$i); else echo "New"; ?></span></td>
						<td width="25%" class="listr"><input type="text"  size="60" name=<?php echo "device[".$i."]"?> id=<?php echo "device[".$i."]"?> value="<?php if (!empty($fstabelement[$i][0])) print($fstabelement[$i][0]); else print "";?>"/></td>
						<td width="35%" class="listr" ><input type="text"  size="75" name=<?php echo "mountpoint[".$i."]" ?> id=<?php echo "mountpoint[".$i."]" ?> value="<?php if (!empty($fstabelement[$i][1])) print($fstabelement[$i][1]); else print "";?>"/></td>
						<td width="10%" class="listr"><input type="text"  name=<?php echo "filesys[".$i."]" ?> id=<?php echo "filesys[".$i."]" ?> value="<?php if (!empty($fstabelement[$i][2])) print($fstabelement[$i][2]); else print "";?>"/></td>
						<td width="10%" class="listr"><input type="text"  size="15" name=<?php echo "option[".$i."]" ?> id=<?php echo "option[".$i."]" ?> value="<?=$fstabelement[$i][3];?>"/></td>
						<td width="5%" class="listr"><input type="text"  size="2" name= <?php echo "dump[".$i."]" ?> id=<?php echo "dump[".$i."]" ?> value="<?=$fstabelement[$i][4];?>"/></td>
						<td width="5%" class="listr"><input type="text"  size="2" name= <?php echo "fsck[".$i."]" ?> id=<?php echo "fsck[".$i."]" ?> value="<?=$fstabelement[$i][5];?>"/></td>
						<td width="5%"><a href="extensions_thebrig_fstab.php?act=delete&amp;uuid=<?=$pconfig['uuid'];?>&amp;line=<?=$i;?>&amp;referer=<?=$link;?>&amp;line=<?=$i;?>&amp;numberline=<?=$countfstabfile;?>" onclick="return confirm('<?=gettext("Do you really want to delete this line? ");?>')"><img src="x.gif" title="<?=gettext("Delete line");?>" border="0" alt="<?=gettext("Delete line");?>" /></a></td>
						</tr>	<?php endfor; ?>
		</table>
					<input name="act" type="hidden" value="tempedit" />
					<input name="numberline" type="hidden" value="<?=$countfstabfile;?>" />
					<input name="uuid" type="hidden" value="<?=$pconfig['uuid'];?>" />
					<input name="referer" type="hidden" value="<?=$link;?>" />	
				 <input type="button" style = "font-family:Tahoma,Verdana,Arial,Helvetica,sans-serif;font-size:11px;font-weight:bold;color: #900;" value="Add line" onclick="ds('wrapper0');">	
	</div>
			<tr><td>If you edit entered values without add new line, please use add line button for add entered value to temporary storage.</td></tr>
			<div id="submit">
					<input name="Submit" type="submit" class="formbtn" value="<?=gettext("Save and exit");?>" />
					<input name="Cancel" type="submit" class="formbtn" value="<?=gettext("Cancel");?>" />
					<input name="numberline" type="hidden" value="<?=$i;?>" />
					<input name="uuid" type="hidden" value="<?=$pconfig['uuid'];?>" />
					<input name="referer" type="hidden" value="<?=$link;?>" />
			</div>	

	<?php include("formend.inc");?>
</form>
</td>
</tr>
</table>			
<?php include ("fend.inc"); ?>
