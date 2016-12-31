#!/usr/local/bin/php-cgi -q -f
<?php
/*
convert.php  -  script convert old cmd style to Nas4Free 11 based style
Author Alexey Kruglov
*/
header_remove('x-powered-by');
header("content-type: none");
header_remove("content-type");
require_once ("guiconfig.inc");
$freebsdversion=floatval(exec("uname -r | cut -d- -f1 | cut -d. -f1"));
if ( $freebsdversion >10 ) {
$a_rc=&$config['rc']['param'];
  $j=0;
  // convert preinit
  if (is_array($config['rc']['preinit']) && isset ($config['rc']['preinit']['cmd'])) {
      if (! is_array($config['rc']['param']) ) $config['rc']['param'] = array();
      $cmd_preinit = &$config['rc']['preinit']['cmd'];
      $i=0;
      for ($i; $i < count($config['rc']['preinit']['cmd']); ++$i) {
	$sphere_record['uuid'] = uuid();
	$sphere_record['enable'] = true;
	$sphere_record['protected'] = false;
	$sphere_record['name'] = '';
	$sphere_record['value'] = $cmd_preinit[$i];
	$sphere_record['comment'] = '';
	$sphere_record['typeid'] = '1';
	$a_rc[] = $sphere_record;
	unset ($sphere_record);
	$j++;
      }
  }
  // convert postinit
  if (is_array($config['rc']['postinit']) && isset ($config['rc']['postinit']['cmd'])) {
      if (! is_array($config['rc']['param']) ) $config['rc']['param'] = array();
      $cmd_postinit = &$config['rc']['postinit']['cmd'];
      $i=0;
      for ($i; $i < count($config['rc']['postinit']['cmd']); ++$i ) {
     // foreach ( $config['rc']['postinit']['cmd'] as $command ) {
	$sphere_record['uuid'] = uuid();
	$sphere_record['enable'] = true;
	$sphere_record['protected'] = false;
	$sphere_record['name'] = '';
	$sphere_record['value'] = $cmd_postinit[$i];
	$sphere_record['comment'] = '';
	$sphere_record['typeid'] = '2';
	$a_rc[] = $sphere_record;
	echo "postinit " . $i . " " . $cmd_postinit[$i] . "\n";
	unset ($sphere_record);
	$j++;
      }
  }
  // convert shutdown
  if (is_array($config['rc']['shutdown']) && isset ($config['rc']['shutdown']['cmd'])) {
      if (! is_array($config['rc']['param']) ) $config['rc']['param'] = array();
      $cmd_shutdown = &$config['rc']['shutdown']['cmd'];
      $i=0;
       for ($i; $i < count($config['rc']['shutdown']['cmd']); ++$i) {
      // foreach ( $config['rc']['shutdown']['cmd'] as $command) {
	$sphere_record['uuid'] = uuid();
	$sphere_record['enable'] = true;
	$sphere_record['protected'] = false;
	$sphere_record['name'] = '';
	$sphere_record['value'] = $cmd_shutdown[$i];
	$sphere_record['comment'] = '';
	$sphere_record['typeid'] = '3';
	$a_rc[] = $sphere_record;
	unset ($sphere_record);
	$j++;
      }
  }
  if ($j>0) {
  // Comment unsets if you not want delete old entries
      unset ($config['rc']['preinit']);
      unset ($config['rc']['postinit']);
      unset ($config['rc']['shutdown']);
   //End unsets
      write_config();
      echo " Config converted, found ".$j." commands\n";
    } else {
      echo " Nothing\n";
    }
} else {
echo "this script for FreeBSD-11 version\n";
}
exit;
?>
