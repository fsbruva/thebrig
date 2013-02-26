#!/bin/sh

# define our bail out shortcut function anytime there is an error - display the error message, then exit
# returning 1.
exerr () { echo -e "$*" >&2 ; exit 1; }

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
    cd $BRIG_ROOT || exerr "ERROR: Could not access install directory!"
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


# This is the script to initially install thebrig
# It first fetches the zip of the most recent version from github
# and then extracts it.

# Fetch the master branch as a zip file
echo "Retrieving the most recent version of TheBrig"
fetch https://github.com/fsbruva/thebrig/archive/master.zip || exerr "ERROR: Could not write to install directory!"

# Extract the files we want, stripping the leading directory, and exclude
# the git nonsense
echo "Unpacking the tarball..."
tar -xvf master.zip --exclude='.git*' --strip-components 1

# Get rid of the tarball
rm master.zip

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

# Place the path (of the current directory) within a file for the intial
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

echo "Congratulations! Refresh to see a new tab under \" Extensions\"!"
