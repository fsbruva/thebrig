#!/bin/sh
#thebrig_stop.sh - Copyright Matthew Kempe 2012
# and I'm

files=`ls /usr/local/www | grep thebrig`
cd /usr/local/www
for phfile in ${files}
do
	   unlink ${phfile}
done


