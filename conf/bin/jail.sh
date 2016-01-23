#!/bin/sh

# Alternative rc script for jails. This script relies on
# /etc/jail.conf instead of rc.conf variables. Usage:
#
# thebrig_enable           -> Enables the script
# thebrig_list     -> List of jails to be started. The names
#                                     must match the names in /etc/jail.conf
# thebrig_$name_zfs  -> List of ZFS datasets to connect to the
#                                         jail $name.
#
# To manage ZFS datasets within a jail the dataset must have
# set the parameter "jailed" to 1. Additionally the jail must
# have set the proberties "allow.mount", "allow.mount.zfs"
# and "enforce_statfs" to 0.

# PROVIDE: thebrig
# REQUIRE: LOGIN cleanvar
# BEFORE: securelevel
# KEYWORD: shutdown
# XQUERY: -i "count(//thebrig/thebrig_enable) > 0" -o "0" -b
# RCVAR: thebrig

. /etc/rc.subr
. /etc/util.subr
. /etc/configxml.subr

case $0 in
/etc/rc*)
        # during boot (shutdown) $0 is /etc/rc (/etc/rc.shutdown),
        # so get the name of the script from $_file
        name=$_file
        ;;
*)
        name=$0
        ;;
esac

name=thebrig
rcvar=${name}_enable

load_rc_config $name
: ${jail_enable="NO"}

cmd="$1"
if [ $# -gt 0 ]; then
        shift
fi
if [ -n "$*" ]; then
        _list="$*"
fi


#_jail_list=${thebrig_list:-$*}
eval "${rcvar}=\${${rcvar}:-'NO'}"
eval "_jail_list=\${_list:-\$${name}_list}"

rootfolder=`configxml_get "//thebrig/rootfolder"`

required_files="${rootfolder}/conf/thebrig.conf"

jail_cmd="/usr/sbin/jail"
jail_args="-f ${rootfolder}/conf/thebrig.conf"

start_cmd="thebrig_start"
stop_cmd="thebrig_stop"

thebrig_start()
{
        echo -n "Starting jails: "
        /sbin/sysctl security.jail.enforce_statfs=`configxml_get "//thebrig/gl_statfs"`

        devfs_init_rulesets

        for _j in ${_jail_list}; do
                echo -n "${_j} "

                if [ -e /var/run/jail_${_j}.id ]; then
                        echo "${_j} already exists"
                        continue
                fi
                #      Uncomment for debug next string and comment next+1.
                #       jail -c -d -p 20 -f /etc/thebrig.conf -J /var/run/jail_${_j}.id ${_j}  >> /$
                $jail_cmd $jail_args -p 20 -J /var/run/jail_${_j}.id -c ${_j}

        done
        echo ""
}

thebrig_stop()
{
        echo -n "Stopping jails: "

        for _j in ${_jail_list}; do
        echo -n "${_j} "

                if [ ! -e /var/run/jail_${_j}.id ]; then
                        echo "${_j} doesn't exist"
                        continue
                fi
        #       eval _zfs=\"\${jail_${_j}_zfs:-}\"

                _jid=`jls -j ${_j} jid 2>/dev/null`

        #       jail -r -f /etc/thebrig.conf  ${_j}  >> /var/log/jail.log 2>&1
                $jail_cmd $jail_args -r ${_j}
                retval=$?
                if [ $retval -eq 0 ]; then
                    rm /var/run/jail_${_j}.id
                fi

        done

        echo

}

run_rc_command "${cmd}"




