#!/bin/sh

# define our bail out shortcut function anytime there is an error - display the error message, then exit
# returning 1.
exerr () { echo -e "$*" >&2 ; exit 1; }
startfolder=`pwd`

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
# touch /tmp/thebrig.tmp

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
. /etc/rc.subr
. /etc/configxml.subr
thebrigversion=0
thebrig_installed=`/usr/local/bin/xml sel -t -v //thebrig /conf/config.xml`
if [ "$thebrig_installed" ]; then
	thebrigversion=`configxml_get "//thebrig/version"`
	if [ $thebrigversion == 1 ]; then
			echo "You have first version. It will updated.."
			file=conf/bin/change_ver.php
			echo "#!/usr/local/bin/php-cgi -f" > $file
			echo "<?php" >> $file
			echo "include (\"config.inc\");">> $file
			echo "if ($config['thebrig']['version'] == 1) { echo \"this is first thebrig version\"; }">> $file
			echo "else {echo \"You have new version\"; echo \"\n\"; exit;}">> $file
			echo "\$langfile = file(\"/usr/local/www/ext/thebrig/lang.inc\");" >> $file
			echo "\$version_1 = preg_split ( \"/VERSION_NBR, 'v/\", $langfile[1]);">> $file
			echo "echo \"\n\";">> $file
			echo "\$version=substr($version_1[1],0,3);">> $file
			echo "echo \$version;">> $file
			echo "\$config['thebrig']['version'] = \$version;">> $file
			echo "write_config();">> $file
			echo "echo \"\n\";">> $file
			echo "?>">> $file
			chmod 755 $file
			/usr/local/bin/php-cgi -f conf/bin/change_ver.php
		else 
			echo "You have version number "`echo $thebrigversion`
			thebrigversion1=`configxml_get "//thebrig/version" | head -c1`
			thebrigversion2=`configxml_get "//thebrig/version" | tail -c2`
			thebrigversion3=$((thebrigversion1*10+thebrigversion2))
			revision=`cat conf/ext/thebrig/lang.inc | grep _THEBRIG_VERSION_NBR, | awk '{print $2}' | tail -c7 | head -c3`
			revision1=`cat conf/ext/thebrig/lang.inc | grep _THEBRIG_VERSION_NBR, | awk '{print $2}' | tail -c7 | head -c1`
			revision2=`cat conf/ext/thebrig/lang.inc | grep _THEBRIG_VERSION_NBR, | awk '{print $2}' | tail -c5 | head -c1`
			revision3=$((revision1*10+revision2))
			if [ "$thebrigversion3" -ge "$revision3" ]; then
				echo "You use current.."
				exit
			else
				echo "Thebrig will update.."
				file=conf/bin/change_ver.php
				echo "#!/usr/local/bin/php-cgi -f" > $file
				echo "<?php" >> $file
				echo "include (\"config.inc\");">> $file
				echo "if ($config['thebrig']['version'] == 1) { echo \"this is first thebrig version\"; }">> $file
				echo "else { exit;}">> $file
				echo "\$langfile = file(\"/usr/local/www/ext/thebrig/lang.inc\");" >> $file
				echo "\$version_1 = preg_split ( \"/VERSION_NBR, 'v/\", $langfile[1]);">> $file
				echo "echo \"\n\";">> $file
				echo "\$version=substr($version_1[1],0,3);">> $file
				echo "echo \$version;">> $file
				echo "\$config['thebrig']['version'] = \$version;">> $file
				echo "write_config();">> $file
				echo "echo \"\n\";">> $file
				echo "?>">> $file
				chmod 755 $file
				/usr/local/bin/php-cgi -f conf/bin/change_ver.php
				message="Congratulations! Updated to version "$revision". Navigate to rudimentary config and push Save"
			fi
		fi
else
		echo $BRIG_ROOT > /tmp/thebrig.tmp
		message="Congratulations! Refresh to see a new tab under \" Extensions\"!"
fi
# Get rid of the tarball
# rm master.zip

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
# Place the path (of the current directory) within a file for the intial
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
# run of the extension
touch /tmp/thebrig.tmp
echo $BRIG_ROOT > /tmp/thebrig.tmp

# Copy all the requisite files to be used into the /usr/local/www folders as needed
mkdir -p /usr/local/www/ext/thebrig
cp $BRIG_ROOT/conf/ext/thebrig/* /usr/local/www/ext/thebrig
cd /usr/local/www
# For each of the php files in the extensions folder
for file in /usr/local/www/ext/thebrig/*.php
do
	# Check if the link is alredy there
	if [ -e "${file##*/}" ]; then
		rm "${file##*/}"
	fi
	# Create link
	ln -s "$file" "${file##*/}"
done
cd $startfolder
rm -rf temporary/
echo $message
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
