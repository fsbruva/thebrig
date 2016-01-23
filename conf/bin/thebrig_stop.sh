#!/bin/sh
#thebrig_stop.sh - Copyright Matthew Kempe 2012
# and I'm

echo -n "Stopping jails: "
	runjails=`jls name`
	for _j in ${runjails}; do
		jail -r -f /mnt/tank/briggy/conf/thebrig.conf  ${_j} 
		rm /var/run/jail_${_j}.id
	done
	echo
#unlink  /usr/local/www/ext/thebrig

files=`ls /usr/local/www | grep thebrig`
cd /usr/local/www
for phfile in ${files}
do
#	   unlink ${phfile}
done


