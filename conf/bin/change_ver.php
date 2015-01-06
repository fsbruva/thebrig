#!/usr/local/bin/php-cgi -f
<?php
include ("config.inc");
$thebrigversion=0;
$workdir_1 = file("/tmp/thebriginstaller");
$workdir = trim($workdir_1[0]);
$langfile = file("{$workdir}/temporary/conf/ext/thebrig/lang.inc");
$version_1 = preg_split ( "/VERSION_NBR, 'v/", $langfile[1]);
$currentversion=substr($version_1[1],0,3);
if (isset($config['thebrig']['gl_statfs']) && is_numeric ($config['thebrig']['gl_statfs'])  ) { 	
	if (is_array($config['thebrig'])) {
		if ($config['thebrig']['rootfolder']) { 
			$thebrigrootfolder = $config['thebrig']['rootfolder'];
			$thebrigversion = $config['thebrig']['version'];
			if ($thebrigversion == $currentversion) {
				$message = "No need updates \n"; 
				if (is_file("/tmp/thebrigversion") ) unlink ("/tmp/thebrigversion");
				goto met1;
				}
			elseif ( $thebrigversion == 1 )  {
				$message = "You use first thebrig version \n";
				$config['thebrig']['version'] = $currentversion;
				write_config();
				file_put_contents("/tmp/thebrigversion", "updated");
				}
			else {
				$message = "You use old thebrig version, we reinstall it \n";
				$config['thebrig']['version'] = $currentversion;
				write_config();
				file_put_contents("/tmp/thebrigversion", "updated");
				}
			}
		else { $message = "You cannot have Thebrig installed"; 
		file_put_contents("/tmp/thebrigversion", "installed");
		}
		}
	else { $message = "Hello new user, We will install TheBrig now \n";
	file_put_contents("/tmp/thebrigversion", "installed"); }
	met1 : echo $message;
}  else { 
// stop all jails
	exec ( "/usr/sbin/jls name", $runningjails);
	if (is_array ($runningjails) && !empty($runningjails)) {foreach ($runningjails as $jail) { exec("/etc/rc.d/jail stop ". $jail);} }
	$handle=fopen("/tmp/upgrademessage.txt", "w");
	$removemessage = 1;
	$message = "Warning! Please define parameters for jails manually. \n " ;
	$a_jail = &$config['thebrig']['content'];
// Begin alcatraz
// backup config in first
/**	if (is_dir ($config['thebrig']['rootfolder'] )) { */
// Check tag entry, folder, is writable and make backup
		/*	if ( ! copy ("/conf/config.xml ", $config['thebrig']['rootfolder']."config.xml.backup"))  {
				exec ("logger Failed copy rc script. TheBrig root folder not writable"); 
				exit;
				} else {}
		} else  {	exec ("logger Extension homing folder not defined."); exit;} */   /** WOW I'm root on php cli , but I can't copy!! */  
	$oldthebrigconf = array();
	$oldthebrigconf['content'] = array();
	$oldthebrigconf = $config['thebrig'];
	unset ($config['thebrig']);
	write_config();
	$config['thebrig'] = array();
	$config['thebrig']['content'] = array();
// conversion
 unset ($oldthebrigconf['sethostname']);
unset ($oldthebrigconf['unixiproute']);
  unset ($oldthebrigconf['systenv']);
	$oldthebrigconf['gl_statfs'] = 1;
	$jail=array();
	for ($i=0; $i <count($oldthebrigconf['content']);)  {
		$jail['allowedip'] = $a_jail[$i]['if'] ."|". $a_jail[$i]['ipaddr'] ."/". $a_jail[$i]['subnet'] ;
		
		$jail['statfs'] =1;
		$jail['cmd'] = array();
		if (!empty ( $a_jail[$i]['exec_prestart'])) $jail['cmd'][] = "prestart|0|" .  $a_jail[$i]['exec_prestart'];
		if (!empty ($jail['afterstart0'])) { 
			$jail[$i]['cmd'][] = "afterstart_for_main|0|" .  $a_jail['afterstart0'];
			if (!empty ($a_jail[$i]['afterstart1']))  $jail['cmd'][] = "afterstart_for_main|1|" .  $a_jail[$i]['afterstart1']; 
		}
		if (!empty ($jail[$i]['jail_parameters'])) { 
			$message = "Detected parameters \"" . $a_jail[$i]['jailname']."\" :" . $a_jail[$i]['jail_parameters']."\n" ;
			fwrite ($handle, $message );
			//$removemessage = 0;
		}
		unset ($jail[$i]['exec_prestart']);
		unset ($jail[$i]['afterstart1']);
		unset ($jail[$i]['afterstart0']);
		unset ($jail[$i]['extraoptions']);
		unset ($jail[$i]['jail_parameters']);
		unset ($jail[$i]['image']);
		unset ($jail[$i]['image_type']);
		unset ($jail[$i]['attach_params']);
		unset ($jail[$i]['zfs_datasets']);
		unset ($jail[$i]['fib']);
		unset ($jail[$i]['if']);
		unset ($jail[$i]['ipaddr']);
		unset ( $a_jail[$i]);
		if (1<count($oldthebrigconf['content'])) { $a_jail[$i] = $jail;} else {$a_jail = $jail;}
		write_config();
		 ++$i
	} // end foreach jails
	fclose ($handle);
	if ($removemessage == 0) exec ("/bin/rm /tmp/upgrademessage.txt");
	exec ("/bin/rm -rf ".$config['thebrig']['rootfolder']."conf");
	exec ("/bin/rm /etc/rc.conf.local");
	exec ("/bin/rm -rf " .$config['thebrig']['rootfolder']."bin");
	
	$config['thebrig'] = $oldthebrigconf;
	$config['thebrig']['version'] = $currentversion;
	write_config();
	file_put_contents("/tmp/thebrigversion", "upgraded");
	$message = "We upgrade Thebrig \n";

}
?>
