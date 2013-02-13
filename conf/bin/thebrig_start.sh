#!/bin/sh
#thebrig_start.sh - Copyright Matthew Kempe 2012
mkdir -p /usr/local/www/ext/thebrig

# Method adapted from user apokalyptik at http://hintsforums.macworld.com/archive/index.php/t-73839.html
STAT=$(procstat -f $$ | grep -E "/"$(basename $0)"$")
FULL_PATH=$(echo $STAT | sed -r s/'^([^\/]+)\/'/'\/'/1 2>/dev/null)
BRIG_ROOT=$(dirname $FULL_PATH | sed 's|/bin||')

cp $BRIG_ROOT/ext/thebrig/* /usr/local/www/ext/thebrig
cd /usr/local/www
for file in /usr/local/www/ext/thebrig/*.php
do
ln -s "$file" "${file##*/}"
done