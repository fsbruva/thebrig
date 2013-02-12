#!/bin/sh

# The first argument will be the path that the user wants to be the root folder.
# If this directory does not exist, it is created
BRIG_ROOT=$1


# define our bail out shortcut function anytime there is an error - display the error message, then exit
# returning 1.
exerr () { echo -e "$*" >&2 ; exit 1; }

# This checks if the supplied argument is a directory. If it is not
# then we will try to create it
if [ ! -d $BRIG_ROOT ]; then
    echo "Attempting to create a new destination directory....."
    mkdir -p $BRIG_ROOT || exerr "ERROR: Could not create directory!"
fi

cd $BRIG_ROOT

# This is the script to initially install thebrig
# It first fetches the zip of the most recent version from github
# and then extracts it

# Fetch the master branch as a zip file
fetch https://github.com/fsbruva/thebrig/archive/master.zip

# Extract the files we want, stripping the leading directory, and exclude
# the git nonsense
echo "Unpacking the tarball..."
tar -qxvf master.zip --exclude='.git*' --strip-components 1

# Get rid of the tarball
rm master.zip

# Rename some files we have so there is only one bin/ftp
if [ `uname -p` = "amd64" ]; then
    echo "Renaming 64 bit ftp binary"
    mv bin/ftp_amd64 bin/ftp
    rm bin/ftp_i386
else
    echo "Renaming 32 bit ftp binary"
    mv bin/ftp_i386 bin/ftp
    rm bin/ftp_amd64
fi

# Place the path (of the current directory) within a file for the intial
# run of the extension
touch /tmp/thebrig.tmp
echo $BRIG_ROOT > /tmp/thebrig.tmp

# Copy all the requisite files to be used into the /usr/local/www folders as needed
/bin/sh $BRIG_ROOT/bin/thebrig_start.sh

echo "Congratulations! You should see a new tab under \" Extensions\"!"
