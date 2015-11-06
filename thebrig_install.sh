#!/bin/sh

#
# File name:	thebrig_install.sh
# Author:      	Matt Kempe, Alexey Kruglov
# Modified:		Feb 2015
# 
# Purpose: 		This script is used to intall/update the extension used by
# 				Nas4Free's lighttpd webgui server. It checks the environment,
#				then downloads the latest copy of the software from GitHub,
#				extracts it to a staging directory, checks the upgrade
#				path/status, installs the new software and creates the 
#				appropriate symlinks.
#  
# Variables used:
# 
# STAT			a string containing the process info about this script's
# 				invocation
# FULL_PATH		a string of the full path of this script, as invoked
# START_FOLDER	a string with the full path of the folder this script 
#				was invoked from
# BRIG_ROOT		a string of the desired install location for thebrig
# STAGE_BIN_PATH	a string of the "bin" directory in the stage directory
# CHANGE_VER_FILE 	a string with the file name of the upgrade detection
#					script
# FILE_ACT		a string with the filename where CHANGE_VER_FILE saves
#				its detection results
# ACTION		an integer that indicates if this is an initial install (0),
#				an upgrade (1) or a re-installation (2)
# ACTION_MSG	a string with useful info for the user about our methods
# THEBRIG_START_FILE	a string with the file name of the auto-start
#						initialization script
# CURRENTDATE	a string containing the date/time string right.... NOW!


# define our bail out shortcut function anytime there is an error - display the error message, then exit
# returning 1.
exerr () { echo -e "$*" >&2 ; exit 1; }

# Determine the current directory
# Method adapted from user apokalyptik at
# http://hintsforums.macworld.com/archive/index.php/t-73839.html
STAT=$(procstat -f $$ | grep -E "/"$(basename $0)"$")
FULL_PATH=$(echo $STAT | sed -r s/'^([^\/]+)\/'/'\/'/1 2>/dev/null)
START_FOLDER=$(dirname $FULL_PATH | sed 's|/thebrig_install.sh||')

# First stop any users older than 9.3 from installing
MAJ_REL=$(uname -r | cut -d- -f1 | cut -d. -f1)
MIN_REL=$(uname -r | cut -d- -f1 | cut -d. -f2)

# Prevent users from breaking their system
if [ $MAJ_REL -lt 9 -o $MAJ_REL -eq 9 -a $MIN_REL -lt 3 ]; then
	echo "ERROR: This version of TheBrig is incompatible with your system!"
	exerr "ERROR: Please upgrade Nas4Free to version 9.3 or higher!"
fi

# Store the script's current location in a file
echo $START_FOLDER > /tmp/thebriginstaller

# This first checks to see that the user has supplied an argument
if [ ! -z $1 ]; then
    # The first argument will be the path that the user wants to be the root folder.
    # If this directory does not exist, it is created
    BRIG_ROOT=$1    
    
    # This checks if the supplied argument is a directory. If it is not
    # then we will try to create it
    if [ ! -d $BRIG_ROOT ]; then
        echo "Attempting to create a new destination directory....."
        mkdir -p $BRIG_ROOT || exerr "ERROR: Could not create directory!"
    fi
else
# We are here because the user did not specify an alternate location. Thus, we should use the 
# current directory as the root.
    BRIG_ROOT=$START_FOLDER
fi

# Make and move into the install staging folder
mkdir -p $START_FOLDER/install_stage || exerr "ERROR: Could not create staging directory!"
cd $START_FOLDER/install_stage || exerr "ERROR: Could not access staging directory!"

if [ $2 -eq 3 ]; then 
    # Fetch the testing branch as a zip file
    echo "Retrieving the unstable branch as a zip file"
    echo "If you aren't a developer for TheBrig, is a bad idea!"
    echo "Please re-install according to the documentation..."
    fetch https://github.com/fsbruva/thebrig/archive/alcatraz.zip || exerr "ERROR: Could not write to install directory!"
    mv alcatraz.zip master.zip
elif [ $# -gt 1 -o $2 -eq 2 ]; then
	echo "ERROR: You are attempting an obsolete installation method!!"
	echo "ERROR: If you were following an online tutorial, alert the author!"
	exerr "ERROR: The method you attempted is not supported!"
else
    # Fetch the master branch as a zip file
    echo "Retrieving the most recent stable version of TheBrig"
    fetch https://github.com/fsbruva/thebrig/archive/master.zip || exerr "ERROR: Could not write to install directory!"
fi

# Extract the files we want, stripping the leading directory, and exclude
# the git nonsense
echo "Unpacking the tarball..."
tar -xf master.zip --exclude='.git*' --strip-components 1
echo "Done!"
rm master.zip

echo "Detecting current configuration..."
# Run the change_ver script to deal with different versions of TheBrig
STAGE_BIN_PATH=$START_FOLDER/install_stage/conf/bin
CHANGE_VER_FILE=change_ver.php

# Nas4Free doesn't ship php-cli, so we have to fool it.
export REDIRECT_STATUS=200
export GATEWAY_INTERFACE="CGI/1.1"
export REQUEST_METHOD="GET"
export SCRIPT_FILENAME=$STAGE_BIN_PATH/$CHANGE_VER_FILE
export SCRIPT_PATH=$CHANGE_VER_FILE
export PATH_INFO=$SCRIPT_FILENAME
/usr/local/bin/php-cgi -q

# The file /tmp/thebrigversion should get created by the change_ver script
# Its existence implies that change_ver.php finished successfully. 
# No matter what type of install it is, change_ver will backup and remove
# the old stuff. From this script's perpective, all that needs to happen
# is to copy the contents of the staging directory to the destination 
# folder.

# There are two use cases for this file:
# 1. Brand new install, so there is no config array yet (don't run start)
# 2. Upgraded install, so change_ver made a backup of the old stuff (run start)

FILE_ACT="/tmp/thebrig_action"

if [ -f "$FILE_ACT" ]
then
	ACTION=`cat ${FILE_ACT}` 
		
	# Copy downloaded version to the install destination
	rsync -r $START_FOLDER/install_stage/* $BRIG_ROOT/

	# Change_ver didn't update - this is the initial installation
	if [ "$ACTION" -eq 0 ]
	then
		echo "Installing..."
		# Create the symlinks/schema. We can't use thebrig_start since
		# there is nothing for the brig in the config XML
		mkdir -p /usr/local/www/ext
		ln -s $BRIG_ROOT/conf/ext/thebrig /usr/local/www/ext/thebrig
		cd /usr/local/www
		# For each of the php files in the extensions folder
		for file in $BRIG_ROOT/conf/ext/thebrig/*.php
		do
			# Create link
			ln -s "$file" "${file##*/}"
		done
		# Store the install destination into the /tmp/thebrig.tmp
		echo $BRIG_ROOT > /tmp/thebrig.tmp
		ACTION_MSG="Freshly installed"
		echo "Congratulations! TheBrig was installed. Navigate to rudimentary config tab and push Save."
	else 
		echo "Upgrading/Re-installing..."
		# Change_ver detected an existing config, so we need to run thebrig_start
		# in order to create all the necessary simlinks. Thebrig_start
		# requires that the XML config has all the needed data.
		# Nas4Free doesn't ship php-cli, so we have to fool it.
		THEBRIG_START_FILE=thebrig_start.php

		export REDIRECT_STATUS=200
		export GATEWAY_INTERFACE="CGI/1.1"
		export REQUEST_METHOD="GET"
		export SCRIPT_FILENAME=$STAGE_BIN_PATH/$THEBRIG_START_FILE
		export SCRIPT_PATH=$THEBRIG_START_FILE
		export PATH_INFO=$SCRIPT_FILENAME
		/usr/local/bin/php-cgi -q
		# "1" means we had an older version. "2" means we re-installed
		if [ "$ACTION" -eq 1 ]
		then
			ACTION_MSG="Upgraded"
			echo "Congratulations! TheBrig was upgraded."
		else
			ACTION_MSG="Re-installed"
			echo "Congratulations! TheBrig was re-installed."
		fi
		# Start cleaning up
		rm /tmp/thebrig_action
	fi
	# Get rid of staged updates & cleanup
	rm -r $START_FOLDER/install_stage
	rm /tmp/thebriginstaller
else
# There was no /tmp/thebrigversion, so something bad happened in change_ver
	echo "Something bad happened with change_ver.php. Please re-download and run the install."
fi
# Log it!
CURRENTDATE=`date -j +"%Y-%m-%d %H:%M:%S"`
echo "[$CURRENTDATE]: TheBrig installer!: installer: ${ACTION_MSG} successfully" >> $BRIG_ROOT/thebrig.log
