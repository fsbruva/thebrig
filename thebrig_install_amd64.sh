#!/bin/sh

# This is the script to initially install thebrig
# It first fetches the zip of the most recent version from github
# and then extracts it

# Fetch the master branch as a zip file
fetch https://github.com/fsbruva/thebrig/archive/master.zip

# Extract the files we want, stripping the leading directory, and exclude 
# the git nonsense
tar -xvf master.zip --exclude='.git*' --strip-components 1
mv bin/ftp_amd64 bin/ftp
rm bin/ftp_i386

# Determine the current directory
# Method adapted from user apokalyptik at
# http://hintsforums.macworld.com/archive/index.php/t-73839.html
STAT=$(procstat -f $$ | grep -E "/"$(basename $0)"$")
FULL_PATH=$(echo $STAT | sed -r s/'^([^\/]+)\/'/'\/'/1 2>/dev/null)
BRIG_ROOT=$(dirname $FULL_PATH | sed 's|/thebrig_install.sh||')

# Place the path (of the current directory) within a file for the intial
# run of the extension
echo $BRIG_ROOT > /tmp/thebrig.tmp

/bin/sh $BRIG_ROOT/bin/jail_start.sh
