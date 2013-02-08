#!/bin/sh

# This is the script to initially install thebrig
# It first fetches the zip of the most recent version from github
# and then extracts it

# Fetch the master branch as a zip file
fetch https://github.com/fsbruva/thebrig/archive/master.zip

# Extract the files we want, stripping the leading directory, and exclude 
# the git nonsense
tar -xvf master.zip --exclude='.git*' --strip-components 1

