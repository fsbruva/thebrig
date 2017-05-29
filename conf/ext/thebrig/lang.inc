<?php
/*
	file: lang.inc
	
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
	define(_THEBRIG_VERSION_NBR, 'v1.00');	
		
	if ($config['system']['language'] == 'ru') { 
		include_once "ext/thebrig/ru_lang.inc";
	} else {
	/** All pages  */
		define(_THEBRIG_EXTN, "Extensions");
		define(_THEBRIG_TITLE, "TheBrig");
		define(_THEBRIG_LOG, "Log");
		define(_THEBRIG_MAINTENANCE, "Maintenance");
		define(_THEBRIG_UPDATES , "Updates");
		define(_THEBRIG_TOOLS, "Backup/Restore");

		
	/** Main page */
	
		define(_THEBRIG_ONLINETABLE_TITLE, "On-line view");
		define(_THEBRIG_ONLINETABLE_TITLE1, "Built");
		define(_THEBRIG_ONLINETABLE_TITLE2, "Jail ip");
		define(_THEBRIG_ONLINETABLE_TITLE3, "Jail hostname");
		define(_THEBRIG_ONLINETABLE_TITLE4, "Path to jail");
		define(_THEBRIG_ONLINETABLE_TITLE5, "Status");
		define(_THEBRIG_ONLINETABLE_TITLE6, "ID");
		define(_THEBRIG_ONLINETABLE_TITLE7, "Action");
		define(_THEBRIG_TABLE1_TITLE,"TheBrig config");
		define(_THEBRIG_JAILS, "Jails" );
		define(_THEBRIG_TABLE1_TITLE3,"Start on boot");
		define(_THEBRIG_TABLE1_TITLE1,"Jail name");
		define(_THEBRIG_TABLE1_TITLE2,"Network");		
		define(_THEBRIG_TABLE1_TITLE4,"ZFS");
		define(_THEBRIG_TABLE1_TITLE5,"Hostname");
		define(_THEBRIG_TABLE1_TITLE6,"Path to jail");
		define(_THEBRIG_TABLE1_TITLE7,"Description");
		define(_THEBRIG_GLOBALS_TITLE,"Globals");
		define(_THEBRIG_GLOBALS_ENABLE,"Allow start all jails");
		define(_THEBRIG_GLOBALS_STATFS,"Choose Global enforce_statfs. Default value =2. Jail's value  cannot be less then Global value . <br />Value 2 not allow  jail root user mount inside a jail. \"High\" = 1  and \"All\" = 0 values allow mount jail-friendly filesystems");

		/** Edit page */
		define('_THEBRIG_EDIT', "Edit" );
		define('_THEBRIG_ADD', "Add" );
		define('_THEBRIG_JAIL', "Jail" );
		define('_THEBRIG_DATASET_COMPRESS', "Compression" );
		define('_THEBRIG_DATASET_COMPRESS_EXPL', "Controls the compression algorithm used for this dataset. The 'lzjb' compression algorithm is optimized for performance while providing decent data compression. Setting compression to 'On' uses the 'lzjb' compression algorithm. You can specify the 'gzip' level by using the value 'gzip-N', where N is an integer from 1 (fastest) to 9 (best compression ratio). Currently, 'gzip' is equivalent to 'gzip-6'.");
		define ('_THEBRIG_JAIL_PARAMETERS', "Jail parameters");
		define ('_THEBRIG_JAIL_NUMBER', "Jail number");
		define ('_THEBRIG_JAIL_NUMBER_EXPL', "The jail number determines the order of the jail.");
		define ('_THEBRIG_TABLE1_TITLE1_EXPL', "The jail's  name.");
		define ('_THEBRIG_JAIL_TYPE', "Jail Type \n <input type=\"button\" onclick=\"helpbox()\" value=\"Help\" />");
		define ('_THEBRIG_JAIL_TYPE_EXPL', "Choose jail type ");
		define('_THEBRIG_TABLE1_TITLE3_EXPL',"Check it if you want start current jail automatically with main system ");
		define('_THEBRIG_ONLINETABLE_TITLE4_EXPL', "Sets an alternate location for the jail. Default is {$config['thebrig']['rootfolder']}jail_name/");
		define('_THEBRIG_TABLE1_TITLE_ZFS',"Jail folder type");
		define('_THEBRIG_TABLE1_TITLEZFS_EXPL',"Choice how to store the jail's world. If you want use new dataset, TherBrig will create it automatically and dataset name will as jailname");
		define('_THEBRIG_J_POOL', "Pool" );
		define('_THEBRIG_J_POOL_COPM', "Compression" );
		define('_THEBRIG_J_POOL_EXPL', "Choise pool, where TheBrig will create dataset for jail. You can manage dataset after creation over ZFS page GUI" );
		define('_THEBRIG_J_ALLOW', "In jail allow:" );
		define('_THEBRIG_J_OPTION', "In jail" ); //  insert into jscript page
		define('_THEBRIG_J_MOUNTFSTAB', "mount/umount jail's fs");
		define('_THEBRIG_J_MOUNTFSTAB_EXPL', "Enable the jail to automount its fstab file. <font color=\"red\">This is not optional for slim jails.</color> " );
		define('_THEBRIG_J_STATFS', "Information about a mounted file system (statfs)");
		define('_THEBRIG_J_STATFS_EXPL', "Choose enforce_statfs. Default value =2. It not allow for <b>jail user</b> mount inside a jail. \"High\" = 1  and \"All\" = 0 values allow mount <font color=\"red\">jail-friendly</color> filesystems");
		define('_THEBRIG_J_DEVFS', "Enable mount devfs");
		define('_THEBRIG_J_DEVFS_EXPL', "Use to mount the device file system inside the jail. <br /><b>This must be checked if you want 'ps', 'top' or most rc.d scripts to function inside jail.</b>");
		define('_THEBRIG_J_DEVFSRULES', "Devfs ruleset");
		define('_THEBRIG_J_DEVFSRULES_EXPL', "Define additional rules for current jail.");
		define('_THEBRIG_J_PROCFS', "Enable mount procfs");
		define('_THEBRIG_J_PROCFS_EXPL', "<font color=magenta>if this checked, TheBrig will add entry to fstab automatically</color>");
		define('_THEBRIG_J_FDESCFS',  "Enable mount fdescfs");
		define('_THEBRIG_J_FDESCFS_NOTE', "Some command (as join) and programm (as bash) require mount syntetic descriptors filesystem ");		
		define('_THEBRIG_J_ZFS', "Enable mount zfs dataset");		
		define('_THEBRIG_J_ZFS_MOUNTED', "ZFS dataset, mounted to jail");
		define('_THEBRIG_J_FSTAB', "Fstab for current jail");
		define('_THEBRIG_J_FSTAB_EXPL', " This will be added to fstab. Format: device &lt;space&gt; mount-point as full path &lt;space&gt; fstype &lt;space&gt; options &lt;space&gt; dumpfreq &lt;space&gt; passno. <a href=http://www.freebsd.org/doc/en_US.ISO8859-1/books/handbook/mount-unmount.html target=\"_blank\">Manual</a> <p> Also you can use fstab editor ");
		define('_THEBRIG_J_VNET', "Virtual network");
		define('_THEBRIG_J_VNET_EXPL', "Enable virtual network stack (vnet)");
		define('_THEBRIG_VNET_LAN', "Attach to interface");
		define('_THEBRIG_VNET_LAN_EXPL', "Choice interface for virtual net glue");
		
		define('_THEBRIG_J_NETWORK', "Jail Network settings");
		define('_THEBRIG_J_COMMAND', "Commands");
		define('_THEBRIG_J_COMMANDS', "Jail  commands");
		define('_THEBRIG_J_STARTCOMMAND', "Jail start command");
		define('_THEBRIG_J_STARTCOMMAND_EXPL', "command to execute,  when a jail start. /etc/rc command load rc scripts.");
		define('_THEBRIG_J_STOPCOMMAND', "User command stop");
		define('_THEBRIG_J_STOPCOMMAND_EXPL',  "command to execute in jail for stopping. Usually <i>/bin/sh /etc/rc.shutdown</i>, but can defined by user for execute prestop script");
		define('_THEBRIG_J_DESCRIPTION', "Description");
		define('_THEBRIG_J_DESCRIPTION_EXPL', "You may enter a description here for your reference.");		
		define('_THEBRIG_J_SRC_TITLE', "Installation Source");
		define('_THEBRIG_J_SRC', "Jail Source");
		define('_THEBRIG_SRC_EXPL', "Choose jail source. Selecting 'From Template' will clone the jail specified by the template folder." );
	
	/** Tools page */	
		define(_THEBRIG_LOG_TITLELINE, "Configuration Backup/Restore");
		define(_THEBRIG_BACKUP_CONFIG, "Backup Existing Config");
		define(_THEBRIG_BACKUP_CONFIG_EXPL, "Make a backup of the existing configuration.");
		define(_THEBRIG_RESTORE_CONFIG, "Restore jail's configuration from saved xml");
		define(_THEBRIG_IMPORT_CONFIG, "Import");
		define(_THEBRIG_FILED_UPLOAD, "Failed to upload file.");
	
	/** Config page */	
		define(_THEBRIG_ARCHIVE_ASK, "Archive");
		define(_THEBRIG_ARCHIVE_EXPL,  "Check if you want a backup archive of jail created before deletion.");	
		define(_THEBRIG_LOC_TITLE, "Advanced Jail Locations");
		define(_THEBRIG_LOC_TEMP, "Template Location");
		define(_THEBRIG_LOC_TEMP_EXPL, "Sets the alternate location for the buildworld jail template. Default is in a folder named template within TheBrig's installation folder.");
		define(_THEBRIG_DELETE_WARN,'If you want to uninstall the TheBrig, please make sure that all jails have been removed');
	/** Portsnap page */
		define(_THEBRIG_TAG_NEVER, "Never");
		define(_THEBRIG_PORTSTREE_TITLE, "Portstree");
		define(_THEBRIG_PORTCURRENTSTATUS, "Current Status");
		define(_THEBRIG_PORTDOWNLOADABLE, "The latest snapshot available for download is dated: ");
		define(_THEBRIG_PORTDOWNLOADED, "You have downloaded a snapshot that was released: ");
		define(_THEBRIG_PORTAPPLIED, "The last time you applied a downloaded snapshot was: ");
		define(_THEBRIG_FETCH_FIRST1, "Fetch and Extract the tree");
		define(_THEBRIG_FETCH_FIRST2, "The first step to using a central ports tree is to fetch and extract the snapshot of the ports tree. <br /><b>This step should only need to be done the very first time, as it will overwrite anything in the ports tree directory!</b>");
		define(_THEBRIG_FETCH_UPDATE1, "Fetch and Update the tree");
		define(_THEBRIG_FETCH_UPDATE2, "Click below to perform an 'on-demand' fetch and update of the exising ports tree.");
		define(_THEBRIG_UPDATETREE1, "Update the tree");
		define(_THEBRIG_UPDATETREE2, "Click below to update of the exising ports tree using the most recently downloaded snapshot.");
		define(_THEBRIG_ENABLE_PORTSTREE1, "Ports Tree Enabling");
		define(_THEBRIG_ENABLE_PORTSTREE2, "Please choose which of the following jails should have access to the shared ports tree:");
		define(_THEBRIG_PORTSNOTE, "<font color='red'><b>Please note: </b></font> Selecting a jail to have access to the portstree will result in a non-standard make.conf");
		define(_THEBRIG_PORTCRON, "Cronjob");
		define(_THEBRIG_PORT_CRON, "Enable 2:47 a.m. daily cron fetch of port tree.");
	/** Freebsd-update page */
		define(_THEBRIG_FBSDVERSION, "The latest version on the FTP server is: ");
		define(_THEBRIG_FBSDVERSION_VALID, "The update on the FTP server is valid until: ");
		define(_THEBRIG_UPDATEOP, "Update Operations");
		define(_THEBRIG_UPDATEOP_EXPL, "Please choose which of the following jails should have the update action carried out:");
		define(_THEBRIG_UPDATETABLE1, "Last Check for Updates");
		define(_THEBRIG_UPDATETABLE2, "Downloaded Version");
		define(_THEBRIG_UPDATETABLE3, "Files Affected by Pending Update");
		define(_THEBRIG_UPDATETABLE4, "Installed Version");
		define(_THEBRIG_NOUPDATE, "No updates pending");
		define(_THEBRIG_UPDATETABLE_NOTE, "<font color='red'><b>Please note:</b></font> Selecting a slim jail for any update operation will mandate that all other slim jails be updated as well.");
		define(_THEBRIG_UPDATE_FETCH, "Fetch the Updates");
		define(_THEBRIG_UPDATE_FETCHEXPL, "Click below to perform an 'on-demand' fetch for the selected jails.");
		define(_THEBRIG_UPDATE_INSTALL, "Install the Updates");
		define(_THEBRIG_UPDATE_INSTALLEXPL, "Click below to install the pending update to the selected jail(s).");
		define(_THEBRIG_UPDATE_DETAILS, "Update Details");
		define(_THEBRIG_UPDATE_DETAILS_EXPL, "Choose jail to view more information regarding a pending update.");
		define(_THEBRIG_UPDATE_DETAILS1, "Impact of Update");
		define(_THEBRIG_UPDATE_DETAILS2, "Added");
		define(_THEBRIG_UPDATE_DETAILS3, "Updated");
		define(_THEBRIG_UPDATE_DETAILS4, "Removed");
		
	/** Buttons */
		define(_THEBRIG_INSTALL_BUTTON, "Install" );
		define(_THEBRIG_FETCH_BUTTON, "Fetch");
		define(_THEBRIG_SAVE_BUTTON, "Save");
		define(_THEBRIG_ADD_BUTTON, "Add");
		define(_THEBRIG_CANCEL_BUTTON, "Cancel");
		define(_THEBRIG_FSTAB_BUTTON, "Fstab editor");
		define(_THEBRIG_FETCHUPDATE_BUTTON, "Fetch & Update");
		define(_THEBRIG_UPDATE_BUTTON, "Update");
		define(_THEBRIG_FETCHEXTRACT_BUTTON, "Fetch & extract");
		define(_THEBRIG_EXPORT_BUTTON, "Export");
		
		
	/** Error messages */	
		define(_THEBRIG_ERROR, "ERROR!");
		define(_THEBRIG_HUGEERROR, "ERROR! ... Like, a HUGE error!");
		define(_THEBRIG_NOT_INSTALLED, "Something went awry with TheBrig's installation. Please attempt to re-install");
		define(_THEBRIG_NO_BASE, "Creating a jail requires at least one \"base\" tarball. Please visit the <a href='/extensions_thebrig_tarballs.php'>Tarball Management</a> page and download one.");
		define(_THEBRIG_NO_LIB32, "As this is 64 bit Nas4free, creating a jail requires at least one \"lib32\" tarball. Please visit the <a href='/extensions_thebrig_tarballs.php'>Tarball Management</a> page and download one.");
		define(_THEBRIG_NO_JAILLOCATION, "The specified jail location does not exist - probably because you imported the jail's config. Please choose another.");
		define(_THEBRIG_DUPLICATE_NAME, "The specified jailname is a duplicate - probably because you imported the jail's config. Please choose another.");
		define(_THEBRIG_INVALID_MOUNTPOINT, "The attribute into zfs mount point <i>'%s'</i> contains invalid characters .");
		define(_THEBRIG_CONFLICT_DATASET, "The selected dataset was previously assigned to another jail. Possible conflict, and therefore assign a different dataset or edit another prison <b>'%s'</b>.");
		define(_THEBRIG_FORBIDDEN_MOUNTPOINT, "The specified mount point <i>'%s'</i> is forbidden. Why? <a href=\"http://forums.nas4free.org/viewtopic.php?f=66&t=8081\">I so want!</a>  Please choose another.");
		define(_THEBRIG_DUPLICATE_COMMAND, "Duplicate command detected, please inspect command values.");
		define(_THEBRIG_JAILNAME_INUSE, "The specified jailname is already in use. Please choose another.");
		define(_THEBRIG_JAILNAME_RESERVED, "The specified jailname is reserved. Please choose another.");
		define(_THEBRIG_LOCATION_RESERVED, "The specified jail location is reserved. Please choose another.");
		//define(_THEBRIG_LIB32_SELECTED, "The specified jail location is reserved. Please choose another.");
		define(_THEBRIG_2TARBALS_SELECTED,  "You have selected more than one of a given tarball type!!  Please select only one of each type");
		define(_THEBRIG_CREATEFOLDERERROR, "Could not create directory for jail to live in!");
		define(_THEBRIG_NOT_CONFIRMED, "It appears you just finished installing TheBrig! <br>Please confirm the install location by visiting the <a href='/extensions_thebrig_config.php'>Rudimentary Config</a> page and click \"Save\".");
		define(_THEBRIG_NOTENOUGHSPACE, "There is not enough space on the target disk!");
		define(_THEBRIG_NORETURN_UPDATE, "Something bad happened while attempting to return Nas4Free to its previous state");
		define(_THEBRIG_NOPREPARE_UPDATE, "Something bad happened while attempting to prep for the update operation");
		define(_THEBRIG_JAILSNODEFINED, "There are no jails defined/created. Updating is impossible until there is at least one.");
		define(_THEBRIG_NEEDSELECT, "You need to select a jail to carry out operations on!");
		define(_THEBRIG_NOUPDATESREADY, sprintf("The jail named <i>'%s'</i> does not have any updates ready for installation. <br>Please run 'fetch' for this jail.",$my_jail['jailname']));
		define(_THEBRIG_NOROLLBACK, sprintf("The jail named <i>'%s'</i> cannot have its installation rolled back. <br>Sorry.",$my_jail['jailname']));
		define(_THEBRIG_NOUPDATESREADY_TEMPLATE, "The TEMPLATE jail does not have any updates ready for installation. <br>Please run 'fetch' for this jail.");
		define(_THEBRIG_NOROLLBACK_TEMPLATE, "The TEMPLATE jail cannot have its installation rolled back. <br>Sorry.");
		define(_THEBRIG_ABOVEERROR, "No action was taken because of the above errors.");
		
	/**Messages */	
	
		
		define(_THEBRIG_TYPEHELP, "Slim - This is a fully functional jail, but when first installed, only occupies about 2 MB in its folder.\\n\\n full - This is a full sized jail, about 300 MB per jail, and is completely self contained.\\n\\n Linux - jail for Linux, such Debian.\\n\\n custom- this only create jail folder and make simulation without install. Usefull for migrate jails.");
		
		define(_THEBRIG_INSTALL, "Install the jail tarball");
		define(_THEBRIG_DONT_CHANGE, "Keep existing - don't change");


		define(_THEBRIG_PORTS, "Central Ports");
		define(_THEBRIG_PORT_CRON, "Enable 2:47 a.m. daily cron fetch of port tree.");
		define(_THEBRIG_UPDATE_CRON, "Enable 1:28 a.m. Tuesday weekly cron fetch of updates for all jails.");
		define(_THEBRIG_UPDATER , "FreeBSD Update");
		define(_THEBRIG_MANAGER , "TheBrig Manager");
		define(_THEBRIG_MANAGER_TITLE , "Manager");
		
		define(_THEBRIG_BASIC_CONFIG, "Rudimentary Config");
		define(_THEBRIG_JAILS_TAB, "Current Jails");
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
		define(_THEBRIG_CHECK_NETWORKING, "There seems to be a networking issue. I can't reach ftp.freebsd.org. Please check <a href='/system.php'>DNS</a> and other <a href='/interfaces_lan.php'>networking settings</a>.<br>Alternatively, try it again to see if there was some transient network problem.");
		define(_THEBRIG_CHECK_NETWORKING_GIT, "There seems to be a networking issue. I can't reach GitHub to retrieve the file. Please check <a href='/system.php'>DNS</a> and other <a href='/interfaces_lan.php'>networking settings</a>. <br>Alternatively, try it again to see if there was some transient network problem.");
		define(_THEBRIG_GIT_LANG_FAIL, "The fetch succeeded, but the file is missing or not accessible. Please try again!");
		
		define(_THEBRIG_UNABLECONTINUE, "Unable to Continue");
		define(_THEBRIG_UNABLECONTINUE_EXPL1, "In order to begin configuration and update tasks for a common ports tree, you need to have at least one configured and filled jail!");
		define(_THEBRIG_UNABLECONTINUE_EXPL2, "For some reason, Nas4Free cannot be prepared for portsnap tasks. This is highly suspect, as the copying was attempted by root.");
		define(_THEBRIG_UNABLECONTINUE_EXPL3, "In order to begin configuration and update tasks for freebsd-update, you need to have at least one configured and filled jail!");
		define(_THEBRIG_UNABLECONTINUE_EXPL4, "For some reason, Nas4Free cannot be prepared for update tasks. This is highly suspect, as the copying was attempted by root.");

		define(_THEBRIG_FETCH, "Fetch!");
		define(_THEBRIG_FETCH_ERROR, "You selected to fetch without selecting any files.");
		define(_THEBRIG_DELETE_FAIL, "Could not delete file: ");
		define(_THEBRIG_VERSION, 'TheBrig Version ' . _THEBRIG_VERSION_NBR);
		define(_THEBRIG_MATCH_ERROR, "There seem to be no matching releases for your kernel. See the documentation regarding kernel/jail binary mismatch");
		define(_THEBRIG_INFO_TB, "The selected packages will be downloaded as a background job. Please check back in a few moments.");
		define(_THEBRIG_INFO_MGR, "Any jails running will be stopped, and the latest version will be downloaded. Are you sure you want to continue?");
		define(_THEBRIG_JAIL_PARALLEL, "Start all jails in parallel");
		define(_THEBRIG_JAIL_ROOT_HOST, "Allow root user in a jail to change its hostname");
		define(_THEBRIG_JAIL_ROUTE, "Route only TCP/IP within a jail");
		define(_THEBRIG_JAIL_IPC, "Allow SystemV IPC use from within a jail");		
		define(_THEBRIG_LIB, "Specify any additional tarballs to be added to the base jail. For an AMD64 system, this should at least include a tarball with lib32.");
		}
?>
