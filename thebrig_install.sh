#!/bin/sh

# define our bail out shortcut function anytime there is an error - display the error message, then exit
# returning 1.
exerr () { echo -e "$*" >&2 ; exit 1; }

# Alexey - the use of pwd is too simplistic. If user is in /etc and calls script, pwd will return /etc
# Your right, but I need Current Working Folder Name for work! And all files will deleted from /CurrentWorkFolder/temporary or /CurrentWorkFolder/master.zip
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
	mkdir -p temporary || exerr "ERROR: Could not create install directory!"
	echo $BRIG_ROOT > /tmp/thebrig.tmp
	cd temporary || exerr "ERROR: Could not access install directory!"
#    cd $BRIG_ROOT || exerr "ERROR: Could not access install directory!"
else
# We are here because the user did not specify an alternate location. Thus, we should use the 
# current directory as the root.
    BRIG_ROOT=$START_FOLDER
fi


if [ $2 -eq 2 ]; then 
    # Fetch the testing branch as a zip file
    echo "Retrieving the testing branch as a zip file"
    fetch https://github.com/fsbruva/thebrig/archive/working.zip || exerr "ERROR: Could not write to install directory!"
    mv working.zip master.zip
elif [ $2 -eq 3 ]; then
	echo "Retrieving the alexey's branch as a zip file"
	fetch https://github.com/fsbruva/thebrig/archive/alexey.zip || exerr "ERROR: Could not write to install directory!"
	mv alexey.zip master.zip
else
	# Fetch the master branch as a zip file
	echo "Retrieving the most recent version of TheBrig"
	fetch https://github.com/fsbruva/thebrig/archive/master.zip || exerr "ERROR: Could not write to install directory!"
fi


# Extract the files we want, stripping the leading directory, and exclude
# the git nonsense
echo "Unpacking the tarball..."
tar -xvf master.zip --exclude='.git*' --strip-components 1
# Get rid of the tarball
rm master.zip

# Run the change_ver script to deal with different versions of TheBrig
/usr/local/bin/php-cgi -f conf/bin/change_ver.php

file="/tmp/thebrigversion"

# The file /tmp/thebrigversion might get created by the change_ver script
# Its existence implies that we need to carry out the install procedure
if [ -f "$file" ]
then
	echo "Thebrig install/update"
		if [ `uname -p` = "amd64" ]; then
			echo "Renaming 64 bit ftp binary"
			mv conf/bin/ftp_amd64 conf/bin/ftp
			rm conf/bin/ftp_i386
		else
			echo "Renaming 32 bit ftp binary"
			mv conf/bin/ftp_i386 conf/bin/ftp
			rm conf/bin/ftp_amd64
		fi
	cp -r * $BRIG_ROOT/
	mkdir -p /usr/local/www/ext/thebrig
	cp $BRIG_ROOT/conf/ext/thebrig/* /usr/local/www/ext/thebrig
	cd /usr/local/www
	# For each of the php files in the extensions folder
	for file in /usr/local/www/ext/thebrig/*.php
	do
	# Check if the link is already there
		if [ -e "${file##*/}" ]; then
			rm "${file##*/}"
		fi
			# Create link
		ln -s "$file" "${file##*/}"
		done
	echo "Congratulations! Thebrig was updated/installed . Navigate to rudimentary config and push Save "
else
# There was not /tmp/thebrigversion, so we are already using the latest version
	echo "You use fresh version"
fi
# Clean after work
cd $START_FOLDER
# Get rid of staged updates
rm -Rf temporary/*
rmdir temporary
rm /tmp/thebriginstaller
rm /tmp/thebrigversion
currentdate=`date -j +"%Y-%m-%d %H:%M:%S"`
echo "[$currentdate]: TheBrig installer!: installer: install/upgrade action successfull" >> $BRIG_ROOT/thebrig.log
