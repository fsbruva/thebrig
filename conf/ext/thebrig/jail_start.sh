#!/usr/local/bin/php-cgi -f
<?php
/*
This is only fo reference. I will check it and remove this message,when OK.  It worked from webgui now, but I not checked from command line
jail_start.php
*/
include ("config.inc");
$testarray = array();
$startonboot ="";
// copy part of config in temporary array
$testarray = $config['thebrig']['jail']; 
$jaills =  count($testarray);
$celljail = array();
$out_jail = array(); 
$file = "/mnt/data/thebrig/conf/rc.conf.local";
$handle=fopen($file, "w");
If (isset($config['thebrig']['parastart'])) { fwrite ($handle, "jail_parallel_start=\"YES\"\n");} else {fwrite($handle, "jail_parallel_start=\"NO\"\n");}
If (isset($config['thebrig']['sethostname'])) { fwrite ($handle, "jail_set_hostname_allow=\"YES\"\n");} else {fwrite($handle, "jail_set_hostname_allow=\"NO\"\n");}
If (isset($config['thebrig']['unixiproute'])) { fwrite ($handle, "jail_socket_unixiproute_only=\"YES\"\n");} else {fwrite($handle, "jail_socket_unixiproute_only=\"NO\"\n");}
If (isset($config['thebrig']['systenv'])) { fwrite ($handle, "jail_sysvipc_allow=\"YES\"\n");} else {fwrite($handle, "jail_sysvipc_allow=\"NO\"\n");}
// I explode multi array to small arrays and replace tag [cell(n)] to [number] number is 1,2,3,4... 
// with this trick I can make simple loop for write config
$i=1;
foreach ($testarray as $key => $values) { $celljail[$i] = $values; $i = $i+1;}
for ($i = 1; $i <= $jaills; $i++) 
{  
		$out_jail = $celljail[$i];
		fwrite ($handle, "##{$k}###########{$out_jail['jailname']}####{$out_jail['desc']}#####\n");
		fwrite ($handle, "jail_{$out_jail['jailname']}_rootdir=\"{$config['thebrig']['rootfolder']}/{$out_jail['jailname']}\"\n");
		fwrite ($handle, "jail_{$out_jail['jailname']}_hostname=\"{$out_jail['jailname']}.{$config['system']['domain']}\"\n");
		fwrite ($handle, "jail_{$out_jail['jailname']}_interface=\"{$out_jail['if']}\"\n");
		fwrite ($handle, "jail_{$out_jail['jailname']}_ip=\"{$out_jail['ipaddr']}/{$out_jail['subnet']}\"\n");
		fwrite ($handle, "jail_{$out_jail['jailname']}_exec_start=\"/bin/sh /etc/rc\"\n");
		fwrite ($handle, "jail_{$out_jail['jailname']}_exec_afterstart0=\"{$out_jail['afterstart0']}\"\n");
		fwrite ($handle, "jail_{$out_jail['jailname']}_exec_afterstart1=\"{$out_jail['afterstart1']}\"\n");
		fwrite ($handle, "jail_{$out_jail['jailname']}_exec_stop=\"{$out_jail['exec_stop']}\"\n");
		fwrite ($handle, "jail_{$out_jail['jailname']}_flags=\"{$out_jail['extraoptions']}\"\n");
		fwrite ($handle, "jail_{$out_jail['jailname']}_fstab=\"/etc/fstab.{$out_jail['jailname']}\"\n");
		$fstabfile= "/mnt/data/thebrig/conf/fstab.{$out_jail['jailname']}";
		$handle1 = fopen($fstabfile, "w");
		fwrite ($handle1, "{$out_jail['fstab']}");
		fclose($handle1);
		fwrite ($handle, "jail_{$out_jail['jailname']}_devfs_ruleset=\"{$out_jail['devfsrules']}\"\n");
		If (isset($out_jail['jail_mount'])) { fwrite ($handle, "jail_{$out_jail['jailname']}_mount_enable=\"YES\"\n");} else {fwrite($handle, "jail_{$out_jail['jailname']}_mount_enable=\"NO\"\n");}
		If (isset($out_jail['devfs_enable'])) { fwrite ($handle, "jail_{$out_jail['jailname']}_devfs_enable=\"YES\"\n");} else {fwrite($handle, "jail_{$out_jail['jailname']}_devfs_enable=\"NO\"\n");}
		If (isset($out_jail['proc_enable'])) { fwrite ($handle, "jail_{$out_jail['jailname']}_procfs_enable=\"YES\"\n");} else {fwrite($handle, "jail_{$out_jail['jailname']}_procfs_enable=\"NO\"\n");}
		If (isset($out_jail['fdescfs_enable'])) { fwrite ($handle, "jail_{$out_jail['jailname']}_fdescfs_enable=\"YES\"\n");} else {fwrite($handle, "jail_{$out_jail['jailname']}_fdescfs_enable=\"NO\"\n");}
		If (isset($out_jail['enable'])) {$startonboot = $startonboot.$out_jail['jailname']." ";} else {$startonboot = $startonboot;}
	}
fwrite ($handle, "jail_list=\"{$startonboot}\"\n");
fclose($handle);
?>
