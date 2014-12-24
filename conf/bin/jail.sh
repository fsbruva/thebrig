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


jail_mount_fstab()
{
	local _device _mountpt _rest

	while read _device _mountpt _rest; do
		case ":${_device}" in
		:#* | :)
			continue
			;;
		esac
		if is_symlinked_mountpoint ${_mountpt}; then
			warn "${_mountpt} has symlink as parent - not mounting from ${_fstab}"
			return
		fi
	done <${_fstab}
	mount -a -F "${_fstab}"
}

jail_start()
{
	echo -n "Starting jails: "
	/sbin/sysctl security.jail.enforce_statfs=`configxml_get "//thebrig/gl_statfs"`
	# this use temporary, in general it will replace for prestart script (php or sh )
	cp /mnt/idisk2/app/jail.conf /etc/jail.conf
	
	devfs_init_rulesets

	for _j in ${jail_list}; do
		echo -n "${_j} "

		if [ -e /var/run/jail_${_j}.id ]; then
			echo "${_j} already exists"
			continue
		fi
		_fstab="/etc/fsab."${-j}
		jail_mount_fstab
		jail -c -i -f /etc/jail.conf -J /var/run/jail_${_j}.id ${_j}  > /mnt/idisk/thebrig/jail.log 2>&1

		eval _zfs=\"\${jail_${_j}_zfs:-}\"
		_jid=`jls -j ${_j} jid 2>/dev/null`

		if [ -n "${_zfs}" ]; then
			for _ds in ${_zfs}; do
				_jailed=`zfs get -H jailed ${_ds} 2>/dev/null | awk '{ print $3 }'`
				if [ "${_jailed}" = "on" ]; then
					zfs jail "${_jid}" ${_ds} 2>/dev/null
				fi
			done
		fi
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

		jail -r -f /etc/jail.conf  ${_j} > /dev/null 2>&1
		rm /var/run/jail_${_j}.id

		if [ -n "${_zfs}" ]; then
			for _ds in ${_zfs}; do
				_jailed=`zfs get -H jailed ${_ds} 2>/dev/null | awk '{ print $3 }'`
				if [ "${_jailed}" = "on" ]; then
					zfs unjail "${_jid}" ${_ds} 2>/dev/null
				fi
			done
		fi
	done

	echo
	unlink /etc/jail.conf
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