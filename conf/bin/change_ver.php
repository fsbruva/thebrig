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
//	$a_jail = &$config['thebrig']['content'];
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
	$config['thebrig'] = array();
	$config['thebrig']['content'] = array();
// conversion
	if (isset ($config['thebrig']['sethostname']))  unset ($oldthebrigconf['sethostname']);
	if (isset ($config['thebrig']['unixiproute']))  unset ($oldthebrigconf['unixiproute']);
	if (isset ($config['thebrig']['systenv']))  unset ($oldthebrigconf['systenv']);
	$oldthebrigconf['gl_statfs'] = 1;
	
	foreach ( $oldthebrigconf['content'] as $jail) {
		$jail['allowedip'] = $jail['if'] ."|". $jail['ipaddr'] ."/". $jail['subnet'] ;
		unset ($jail['if']);
		unset ($jail['ipaddr']);
		unset ($jail['subnet']);
		$jail['statfs'] =1;
		$jail['cmd'] = array();
		if (!empty ( $jail['exec_prestart'])) $jail['cmd'][] = "prestart|0|" .  $jail['exec_prestart'];
		if (!empty ($jail['afterstart0'])) { 
			$jail['cmd'][] = "afterstart_for_main|0|" .  $jail['afterstart0'];
			if (!empty ($jail['afterstart1']))  $jail['cmd'][] = "afterstart_for_main|1|" .  $jail['afterstart1']; 
		}
		if (!empty ($jail['jail_parameters'])) { 
			$message = "Detected parameters \"" . $jail['jailname']."\" :" . $jail['jail_parameters']."\n" ;
			fwrite ($handle, $message );
			//$removemessage = 0;
		}
		unset ($jail['exec_prestart']);
		unset ($jail['afterstart1']);
		unset ($jail['afterstart0']);
		unset ($jail['extraoptions']);
		unset ($jail['jail_parameters']);
		unset ($jail['image']);
		unset ($jail['image_type']);
		unset ($jail['attach_params']);
		unset ($jail['zfs_datasets']);
		unset ($jail['fib']);
		
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
