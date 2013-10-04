#!/usr/local/bin/php-cgi -f
<?php
include ("config.inc");
$thebrigversion=0;
$workdir_1 = file("/tmp/thebriginstaller");
$workdir = trim($workdir_1[0]);
$langfile = file("{$workdir}/temporary/conf/ext/thebrig/lang.inc");
$version_1 = preg_split ( "/VERSION_NBR, 'v/", $langfile[1]);
$currentversion=substr($version_1[1],0,3);
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
?>
