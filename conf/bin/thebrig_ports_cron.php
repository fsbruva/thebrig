#!/usr/local/bin/php-cgi -f
<?php
/*
	file: thebrig_ports_cron.php
	
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
require_once ("/etc/inc/config.inc");
$brig_root = $config['thebrig']['rootfolder'];
require_once ("/usr/local/www/ext/thebrig/functions.inc");

$brig_port_db = $brig_root . "conf/db/ports/";
sleep ( rand( 0 , 360 ) );

//$brig_update_ready = thebrig_update_prep();
//if ( $brig_update_ready == 0 ) {
	$response = thebrig_portsnap($brig_root . "conf/ports", $brig_root . "conf/db/ports", $brig_root . "conf/portsnap.conf", "Fetch");
//}
write_briglog("Ports tree updated","info");
?>
