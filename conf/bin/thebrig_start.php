#!/usr/local/bin/php-cgi -f
<?php
/*
*/
require_once ("config.inc");
require_once ("{$config['thebrig']['rootfolder']}conf/ext/thebrig/functions.inc");
if ( ! copy ( $config['thebrig']['rootfolder']."conf/bin/jail.sh", "/etc/rc.d/jail"))  { exec ("logger Failed copy rc script");}  else {}
chmod("/etc/rc.d/jail", 0755);
exec( "mkdir -p /usr/local/www/ext/thebrig/" );
exec( "cp {$config['thebrig']['rootfolder']}conf/ext/thebrig/* /usr/local/www/ext/thebrig/");
$php_list = glob( "/usr/local/www/ext/thebrig/*.php" ); 
foreach ( $php_list as $php_file ) {
        $php_file = str_replace( "/usr/local/www/ext/thebrig/" , "", $php_file);
        if ( is_link ( "/usr/local/www/ext/thebrig/" . $php_file ) ) {        unlink (  "/usr/local/www/ext/thebrig/" . $php_file );        } else {}
        exec ( "ln -s /usr/local/www/ext/thebrig/{$php_file} /usr/local/www/{$php_file}");
}
if ( count ( $config['thebrig']['content'] ) > 0 ) {
        if ( !is_file ( "/etc/rc.conf.local" ) ) {
                // This means we are on embedded
                write_rcconflocal ();
                array_sort_key($config['thebrig']['content'], "jailno");
                $a_jail = &$config['thebrig']['content'];
                foreach ($a_jail as $n_jail) {
                          if ( isset ($n_jail['enable']) && !empty ($n_jail['exec_prestart'])) {  exec ( $n_jail['exec_prestart']); }
                }
                exec ( "/etc/rc.d/jail restart" ) ;
        }
}
?>
