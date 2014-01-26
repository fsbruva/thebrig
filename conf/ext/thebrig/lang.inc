<?php
	define(_THEBRIG_VERSION_NBR, 'v0.8');
	
	if ($config['system']['language'] == 'en_US') { }
	
		define(_THEBRIG_TITLE, "TheBrig");
		define(_THEBRIG_NOT_INSTALLED, "Something went awry with TheBrig's installation. Please attempt to re-install");
		define(_THEBRIG_NOT_CONFIRMED, "It appears you just finished installing TheBrig! <br>Please confirm the install location by visiting the <a href='/extensions_thebrig_config.php'>Rudimentary Config</a> page and click \"Save\".");
		define(_THEBRIG_INSTALL, "Install the jail tarball");
		define(_THEBRIG_DONT_CHANGE, "Keep existing - don't change");
		define(_THEBRIG_MAINTENANCE, "Maintenance");
		define(_THEBRIG_UPDATES , "Updates");
		define(_THEBRIG_PORTS, "Central Ports");
		define(_THEBRIG_PORT_CRON, "Enable 2:47 a.m. daily cron fetch of port tree.");
		define(_THEBRIG_UPDATE_CRON, "Enable 1:28 a.m. Tuesday weekly cron fetch of updates for all jails.");
		define(_THEBRIG_UPDATER , "FreeBSD Update");
		define(_THEBRIG_MANAGER , "TheBrig Manager");
		define(_THEBRIG_JAIL, "Jail" );
		define(_THEBRIG_EDIT, "Edit" );
		define(_THEBRIG_ADD, "Add" );
		define(_THEBRIG_TOOLS, "Backup/Restore");
		define(_THEBRIG_NO_BASE, "Creating a jail requires at least one \"base\" tarball. Please visit the <a href='/extensions_thebrig_tarballs.php'>Tarball Management</a> page and download one.");
		define(_THEBRIG_NO_LIB32, "As this is 64 bit Nas4free, creating a jail requires at least one \"lib32\" tarball. Please visit the <a href='/extensions_thebrig_tarballs.php'>Tarball Management</a> page and download one.");
		define(_THEBRIG_BASIC_CONFIG, "Rudimentary Config");
		define(_THEBRIG_JAILS, "Current Jails");
		define(_THEBRIG_SETTINGS_BASIC, "Basic Settings");
		define(_THEBRIG_CLEANUP, "Uninstall TheBrig");
		define(_THEBRIG_CLEANUP_DESC, "Remove TheBrig integration from NAS4Free. This will leave the installation folder untouched - remove the files using Windows Explorer, FTP or some other tool of your choice.<br><br><b>Please note</b>: this page will no longer be available. You'll have to re-run TheBrig installation to get it back on your NAS4Free.");
		define(_THEBRIG_ROOT, "Installation folder");
		define(_THEBRIG_ROOT_DESC, "Please define TheBrig's jail installation folder. </br><b>Please note:</b> TheBrig will not work properly if this value is empty or incorrect.");
		define(_THEBRIG_BASE, "Basejail Location"); 
		define(_THEBRIG_BASE_DESC, "Sets the alternate location for the shared basejail. Default is a folder named basejail within TheBrig's installation folder. </br><b>Please note:</b> This value cannot be edited while there are any thin jails.");
		define(_THEBRIG_NONEXISTENT_FOLDER, "Folder does not exist!");
		define(_THEBRIG_SAVE, "Save");
		define(_THEBRIG_JAIL_ALREADY, "It appears that at least one jail has already been created. TheBrig does not support moving its installation folder once jails have been created, or if either the basejail or templates have files.</br> If you wish to move the installation folder, you must delete all jails first.");
		define(_THEBRIG_EXTN, "Extensions");
		define(_THEBRIG_TARBALL_MGMT, "Tarball Management");
		define(_THEBRIG_NOTWRITABLE_FOLDER, "Either the old or new folder is not writable!");
		define(_THEBRIG_CURRENT_TB, "Existing Tarballs");
		define(_THEBRIG_OFFICIAL_TB, "Official FreeBSD Flavor");
		define(_THEBRIG_CUSTOM_TB, "Custom Flavor (Homegrown)");
		define(_THEBRIG_PARTIAL_TB, "Official FreeBSD (in progress)");
		define(_THEBRIG_NO_TB, "There are no tarballs available. Please choose some archives and download them using the button below.<br><br>");
		define(_THEBRIG_NO_CUST_TB, "There are no custom tarballs available. This is an advanced feature, so don't worry about it.<br><br>");
		define(_THEBRIG_NO_PART_TB, "There are no partial tarballs. This indicates there are no downloads in progress.<br><br>Or, if you recently fetched a tarball, you will need to refresh this page to have it show up in the list of existing tarballs.");
		define(_THEBRIG_DELETE_TB, "Delete Selected Tarballs");
		define(_THEBRIG_DELETE_ERROR, "You selected deletion without naming files.");
		define(_THEBRIG_REMOTE_TB, "Remote Tarballs");
		define(_THEBRIG_REMOTE_AVAIL, "Available Tarballs");
		define(_THEBRIG_REMOTE_ACTIVE, "Active Downloads");
		define(_THEBRIG_REMOTE_INST, "The ftp server has not been queried (yet). Press the \"Query\" button below to begin the download process.<br><br>");
		define(_THEBRIG_TB_WAIT, "Updating in 1 second...");
		define(_THEBRIG_CONF_TB, "Do you really want to delete these tarball(s)?");
		define(_THEBRIG_FTPQUERY, "Click the query button to get info about available tarballs." );
		define(_THEBRIG_QUERYBTN, "Query!" );
		define(_THEBRIG_CHECK_NETWORKING, "There seems to be a networking issue. I can't reach ftp.freebsd.org. Please check <a href='/system.php'>DNS</a> and other <a href='/interfaces_lan.php'>networking settings</a>. If networking is working, then ensure the ftp binary is present.<br>Alternatively, try it again to see if there was some transient network problem.");
		define(_THEBRIG_FETCH, "Fetch!");
		define(_THEBRIG_FETCH_ERROR, "You selected to fetch without selecting any files.");
		define(_THEBRIG_DELETE_FAIL, "Could not delete file: ");
		define(_THEBRIG_VERSION, 'TheBrig Version ' . _THEBRIG_VERSION_NBR);
		define(_THEBRIG_MATCH_ERROR, "There seem to be no matching releases for your kernel. See the documentation regarding kernel/jail binary mismatch");
		define(_THEBRIG_INFO_TB, "The selected packages will be downloaded as a background job. Please check back in a few moments.");
		define(_THEBRIG_JAIL_PARALLEL, "Start all jails in parallel");
		define(_THEBRIG_JAIL_ROOT_HOST, "Allow root user in a jail to change its hostname");
		define(_THEBRIG_JAIL_ROUTE, "Route only TCP/IP within a jail");
		define(_THEBRIG_JAIL_IPC, "Allow SystemV IPC use from within a jail");
		
		define(_SRVSLIM_MODULE_VERSION_CONFLICT, "Warning - the Logitech Media Server version you're trying to run is not compatible with this SlimNAS version. If you encounter problems running it, please update SlimNAS or revert to a compatible Logitech Media Server. Thanks for your understanding.");
		define(_SRVSLIM_NO_SLIMNAS, "There's no updated SlimNAS package available for installation.");
		define(_SRVSLIM_UPDATE_SLIMNAS, "Update SlimNAS<br>(<a href='http://www.herger.net/SlimNAS' target='_blank'>check for new version</a>)");
		define(_SRVSLIM_CORRUPT_TARBALL, "The file you tried to install seems to be corrupt. Please download and try again: ");
		define(_SRVSLIM_RELOAD_PAGE, " (<a href='/services_slimserver.php'>Update status</a>)");
		
		
		define(_THEBRIG_LIB, "Specify any additional tarballs to be added to the base jail. For an AMD64 system, this should at least include a tarball with lib32.");
?>
