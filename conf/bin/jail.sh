#!/bin/sh

# Alternative rc script for jails. This script relies on
# /etc/jail.conf instead of rc.conf variables. Usage:
#
# jail_enable	   -> Enables the script
# jail_list	   -> List of jails to be started. The names
#				      must match the names in /etc/jail.conf
# jail_$name_zfs  -> List of ZFS datasets to connect to the
#					  jail $name.
#
# To manage ZFS datasets within a jail the dataset must have
# set the parameter "jailed" to 1. Additionally the jail must
# have set the proberties "allow.mount", "allow.mount.zfs"
# and "enforce_statfs" to 0.

# PROVIDE: jail
# REQUIRE: LOGIN cleanvar
# BEFORE: securelevel
# KEYWORD: shutdown

. /etc/rc.subr
. /etc/util.subr
. /etc/configxml.subr

name="jail"
rcvar=jail_enable

start_cmd="jail_start"
stop_cmd="jail_stop"



jail_start()
{
	echo -n "Starting jails: "
	/sbin/sysctl security.jail.enforce_statfs=`configxml_get "//thebrig/gl_statfs"`

	devfs_init_rulesets

	for _j in ${jail_list}; do
		echo -n "${_j} "

		if [ -e /var/run/jail_${_j}.id ]; then
			echo "${_j} already exists"
			continue
		fi
		#      Uncomment for debug next string and comment next+1.
		#	jail -c -d -p 20 -f /etc/thebrig.conf -J /var/run/jail_${_j}.id ${_j}  >> /var/log/jail.log 2>&1
		jail -c  -p 20 -f /etc/thebrig.conf -J /var/run/jail_${_j}.id ${_j} 
		
		
	done

	echo
}

jail_stop()
{
	echo -n "Stopping jails: "

	for _j in ${jail_list}; do
     	echo -n "${_j} "

		if [ ! -e /var/run/jail_${_j}.id ]; then
			echo "${_j} doesn't exists"
			continue
		fi

		eval _zfs=\"\${jail_${_j}_zfs:-}\"
		_jid=`jls -j ${_j} jid 2>/dev/null`

	#	jail -r -f /etc/thebrig.conf  ${_j}  >> /var/log/jail.log 2>&1
		jail -r -f /etc/thebrig.conf  ${_j} 
		rm /var/run/jail_${_j}.id
		
	done

	echo
	
}

load_rc_config $name
: ${jail_enable="NO"}

cmd="$1"
if [ $# -gt 0 ]; then
	shift
fi
if [ -n "$*" ]; then
	jail_list="$*"
fi

run_rc_command "${cmd}"
