<?php
/*
   File:  extensions_thebrig_ajax.php


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

function get_jailinfo() {
	global $config;
	$tabledata = array();
	if (is_array($config['thebrig']['content']) ) { 
		array_sort_key($config['thebrig']['content'], "jailno");
		$jails =  $config['thebrig']['content'];
		$tabledata['rowcount']=count_safe($jails);
		$k=0;
		for ($k = 0; $k < $tabledata['rowcount']; $k++ ) {
			$n_jail = $jails[$k];
			$i=1+$k;
			$tabledata['name'][$i] = $n_jail['jailname'];
			if (!is_dir( $n_jail['jailpath'] ."var/run")) 	{
					$tabledata['built'][$i] = 'OFF';
					$tabledata['builtports'][$i] = 'OFF';
					$tabledata['builtsrc'][$i] = 'OFF';
				}else{
					$tabledata['built'][$i] = 'ON';
					if (is_dir( $n_jail['jailpath'] . "usr/ports/Mk")) {
						$tabledata['builtports'][$i] = "ON";
						} else {
						$tabledata['builtports'][$i] = "OFF";
						}
					if (is_dir( $n_jail['jailpath'] . "usr/src/sys")) {
						$tabledata['builtsrc'][$i] = "ON";
						} else {
						$tabledata['builtsrc'][$i] = "OFF";
						}
				}	
			$file_id = "/var/run/jail_".$n_jail['jailname'].".id";
			if (true === is_file($file_id)) {
			$jail_id = exec ("jls -j ".$n_jail['jailname']. " jid");
			$sleep_cmd = "ps -o jid,stat -ax -J | awk 'BEGIN{c=0}\$1~\"{$jail_id}\"&&(\$2~\"S\"||\$2~\"I\")&&\$2!~\"S[\+]\"{++c}END{print c}'";
			$runn_cmd = "ps -o jid,stat -ax | awk 'BEGIN{c=0}\$1~\"{$jail_id}\"&&(\$2~\"R\"||\$2~\"S[\+]\"){++c}END{print c}'";
			$sleep_cnt = exec ( $sleep_cmd ); 
			$runn_cnt = exec ( $runn_cmd);
			$total = intval($sleep_cnt) + intval($runn_cnt);
			$tabledata['status'][$i] = "{$total} processes: {$runn_cnt} running, {$sleep_cnt} sleeping";
			$tabledata['id'][$i] = $jail_id;
			if (1 == exec ("jls -j ".$n_jail['jailname']. " vnet") ) { 
				unset ($result);
				if (!empty($n_jail['epair_b_suname'] )) {
					$cmd = "jexec ".$n_jail['jailname']." ifconfig " . $n_jail['epair_b_suname'] ." | grep inet | awk '{ print \$2}'";
				} else { 
					$cmd = "jexec ".$n_jail['jailname']." ifconfig epair" . $n_jail["jailno"] ."b | grep inet | awk '{ print \$2}'"; }
				exec ($cmd, $result); 
				$tabledata['ip'][$i] = implode(",", $result); } else {
				$tabledata['ip'][$i] = exec ("jls -j ".$n_jail['jailname']." ip4.addr"); }
				$tabledata['hostname'][$i] = exec ("jls -j ".$n_jail['jailname']." host.hostname");
				$tabledata['path'][$i] = exec ("jls -j ".$n_jail['jailname']." path");
				$tabledata['file_id'][$i] = $file_id;
			} 
			else {
				$tabledata['status'][$i] = 'OFF'; 
				$tabledata['id'][$i] = 'OFF';
				$tabledata['ip'][$i] = 'OFF';
				$tabledata['hostname'][$i] = 'OFF';
				$tabledata['path'][$i] = 'OFF';
				$tabledata['file_id'][$i] = false;
			}
		}
	}
return $tabledata;
}

if (is_ajax()) {
	
	if (isset($_GET['id']) && isset($_GET['action'])) {
		$jailname=$_GET['id'];
		$jailcmd=$_GET['action'];

		$jail_args = "-f " . $config['thebrig']['rootfolder'] . "conf/thebrig.conf";
		$jail_JID = "/var/run/jail_" . $jailname . ".id ";
		if ( strcmp($jailcmd , "onestart") == 0 ){
			// This is in case we need to do separate commands (for 9.1, 9.2, etc)
			//$cmd_string = "/usr/sbin/jail {$jail_args} -J {$jail_JID} -p 20 -c {$jailname}";
			//cmd_exec( $cmd_string,$a_tolog, $err_log);
		}
		else {
			//$cmd_string = "/usr/sbin/jail {$jail_args} -J {$jail_JID} -r {$jailname}";
			//cmd_exec( $cmd_string,$a_tolog, $err_log);
			//mwexec ( 'rm $jail_JID' );
		}
		// Next lines write messages to log
		cmd_exec("/etc/rc.d/thebrig {$jailcmd} {$jailname}",$a_tolog, $err_log);
		write_briglog($err_log, "ERROR");
	}
	elseif ( isset ($_GET['ports'])) {
		// This is the code to detect if we have a connection to the portsnap server
		
	}
	elseif ( isset ($_GET['update'])) {
		// This is the code to detect if we have a connection to the FreeBSD update server
	}
	else {
		$jailinfo = get_jailinfo();
		render_ajax($jailinfo);
	}
	
}
