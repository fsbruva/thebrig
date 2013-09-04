#!/bin/sh

# define our bail out shortcut function anytime there is an error - display the error message, then exit
# returning 1.
exerr () { echo -e "$*" >&2 ; exit 1; }
startfolder=`pwd`

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
	cd temporary || exerr "ERROR: Could not access install directory!"
#    cd $BRIG_ROOT || exerr "ERROR: Could not access install directory!"
else
# We are here because the user did not specify an alternate location. Thus, we should use the 
# current directory as the root.

    # Determine the current directory
    # Method adapted from user apokalyptik at
    # http://hintsforums.macworld.com/archive/index.php/t-73839.html
    STAT=$(procstat -f $$ | grep -E "/"$(basename $0)"$")
    FULL_PATH=$(echo $STAT | sed -r s/'^([^\/]+)\/'/'\/'/1 2>/dev/null)
    BRIG_ROOT=$(dirname $FULL_PATH | sed 's|/thebrig_install.sh||')
fi
# touch /tmp/thebrig.tmp

# This is the script to initially install thebrig
# It first fetches the zip of the most recent version from github
# and then extracts it.

if [ $2 -eq 2 ]; then 
    # Fetch the testing branch as a zip file
    echo "Retrieving the testing branch as a zip file"
    fetch https://github.com/fsbruva/thebrig/archive/working.zip || exerr "ERROR: Could not write to install directory!"
    mv working.zip master.zip
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

# Rename some files we have so there is only one bin/ftp
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
