<?php
/*
   File:  extensions_thebrig_ajax.php
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
		$tabledata['rowcount']=count($jails);
		foreach ($jails as $n_jail){
			$tabledata['name'][$n_jail["jailno"]] = $n_jail['jailname'];
			if (!is_dir( $n_jail['jailpath'] ."var/run")) 	{
					$tabledata['built'][$n_jail["jailno"]] = 'OFF';
					$tabledata['builtports'][$n_jail["jailno"]] = 'OFF';
					$tabledata['builtsrc'][$n_jail["jailno"]] = 'OFF';
				}else{
					$tabledata['built'][$n_jail["jailno"]] = 'ON';
					if (is_dir( $n_jail['jailpath'] . "usr/ports/Mk")) {
						$tabledata['builtports'][$n_jail["jailno"]] = "ON";
						} else {
						$tabledata['builtports'][$n_jail["jailno"]] = "OFF";
						}
					if (is_dir( $n_jail['jailpath'] . "usr/src/sys")) {
						$tabledata['builtsrc'][$n_jail["jailno"]] = "ON";
						} else {
						$tabledata['builtsrc'][$n_jail["jailno"]] = "OFF";
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
			$tabledata['status'][$n_jail["jailno"]] = "{$total} processes: {$runn_cnt} running, {$sleep_cnt} sleeping";
			$tabledata['id'][$n_jail["jailno"]] = $jail_id;
			if (1 == exec ("jls -j ".$n_jail['jailname']. " vnet") ) { 
				$cmd = "jexec ".$n_jail['jailname']." ifconfig epair" . $n_jail["jailno"] ."b | grep inet | awk '{ print \$2}'";
				exec ($cmd, $result); 
				$tabledata['ip'][$n_jail["jailno"]] = implode(",", $result); } else {
				$tabledata['ip'][$n_jail["jailno"]] = exec ("jls -j ".$n_jail['jailname']." ip4.addr"); }
				$tabledata['hostname'][$n_jail["jailno"]] = exec ("jls -j ".$n_jail['jailname']." host.hostname");
				$tabledata['path'][$n_jail["jailno"]] = exec ("jls -j ".$n_jail['jailname']." path");
				$tabledata['file_id'][$n_jail["jailno"]] = $file_id;
			} 
			else {
				$tabledata['status'][$n_jail["jailno"]] = 'OFF'; 
				$tabledata['id'][$n_jail["jailno"]] = 'OFF';
				$tabledata['ip'][$n_jail["jailno"]] = 'OFF';
				$tabledata['hostname'][$n_jail["jailno"]] = 'OFF';
				$tabledata['path'][$n_jail["jailno"]] = 'OFF';
				$tabledata['file_id'][$n_jail["jailno"]] = false;
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
		cmd_exec("sh /etc/rc.d/thebrig {$jailcmd} {$jailname}",$a_tolog, $err_log);
		write_briglog($err_log, "ERROR");
	}
	else {
		$jailinfo = get_jailinfo();
		render_ajax($jailinfo);
	}
	
}
