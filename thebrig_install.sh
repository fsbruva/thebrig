#!/bin/sh

# define our bail out shortcut function anytime there is an error - display the error message, then exit
# returning 1.
exerr () { echo -e "$*" >&2 ; exit 1; }

# Determine the current directory
# Method adapted from user apokalyptik at
# http://hintsforums.macworld.com/archive/index.php/t-73839.html
STAT=$(procstat -f $$ | grep -E "/"$(basename $0)"$")
FULL_PATH=$(echo $STAT | sed -r s/'^([^\/]+)\/'/'\/'/1 2>/dev/null)
START_FOLDER=$(dirname $FULL_PATH | sed 's|/thebrig_install.sh||')

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
#    cd $BRIG_ROOT || exerr "ERROR: Could not access install directory!"
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
    echo "Retrieving the testing branch as a zip file"
    fetch https://github.com/fsbruva/thebrig/archive/alcatraz.zip || exerr "ERROR: Could not write to install directory!"
    mv alcatraz.zip master.zip
     # Fetch the testing branch as a zip file  I want working branch also for check upgrade
     # I already merged working --> master! Working === Master
else
    # Fetch the master branch as a zip file
    echo "Retrieving the most recent version of TheBrig"
    fetch https://github.com/fsbruva/thebrig/archive/master.zip || exerr "ERROR: Could not write to install directory!"
fi

# Extract the files we want, stripping the leading directory, and exclude
# the git nonsense
echo "Unpacking the tarball..."
tar -xvf master.zip --exclude='.git*' --strip-components 1
#rm master.zip

# Run the change_ver script to deal with different versions of TheBrig
/usr/local/bin/php-cgi -f conf/bin/change_ver.php

# The file /tmp/thebrigversion should get created by the change_ver script
# Its existence implies that change_ver.php finished successfully. 
# No matter what type of install it is, change_ver will backup and remove
# the old stuff. From this script's perpective, all that needs to happen
# is to copy the contents of the staging directory to the destination 
# folder.

# There are two use cases for this file:
# 1. Brand new install, so there is no config array yet (don't run start)
# 2. Upgraded install, so change_ver made a backup of the old stuff (run start)

filever="/tmp/thebrigversion"

if [ -f "$filever" ]
then
	action=`cat ${filever}` 
		
	# Copy downloaded version to the install destination
	cp -r * $BRIG_ROOT/

	# Change_ver didn't update - this is the initial installation
	if [ "$action" -eq 0 ]
	then
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
		echo "Congratulations! TheBrig was installed. Navigate to rudimentary config tab and push Save."
	else 
		# Change_ver upgraded an install, so we need to run thebrig_start
		# in order to create all the necessary simlinks. Thebrig_start
		# requires that the XML config has all the needed data.
		/usr/local/bin/php-cgi -f conf/bin/thebrig_start.php
		echo "Congratulations! TheBrig was upgraded/reinstalled."
	fi
else
# There was not /tmp/thebrigversion, so something bad happened in change_ver
	echo "Something bad happened with change_ver.php. Please re-download and run the install."
fi

# Clean after work
cd $START_FOLDER
# Get rid of staged updates
#rm -r install_stage
#rm /tmp/thebriginstaller
if [ -f "$file" ] 
then 
	#rm /tmp/thebrigversion
fi
currentdate=`date -j +"%Y-%m-%d %H:%M:%S"`
echo "[$currentdate]: TheBrig installer!: installer: ${action} successfully" >> $BRIG_ROOT/thebrig.log
