#!/bin/sh
#==========================================================================================================================
#  
#  Function: 	thebrig_fetch
#  Author:		Matthew Kempe
#  Date:		26 Dec 2012
#  
#  Purpose:
#  	This script is used to automatically fetch the requested tarball from the FreeBSD ftp servers. In order to carry out 
#   this operation, the architecture, release, package and destination directory must be known. 
#   All four of the commandline arguments must be specified in order to carry out the fetch operation. As this script is designed 
#   for use with TheBrig extension, no input validation or verification is employed. These tasks are carried out by the webgui, 
#   which enumerates the list of possible architectures, releases and packages, and verifies the accessibility of the 
#   destination folder.
#   
#   The appropriate folder on the ftp server is identified via the arch and release, and this information is used to form a base 
#   URL for each subsequent fetch operation. The first thing to be fetched is the MANIFEST file, which contains the SHA256 hash 
#   of the various tarballs in the folder. Once the MANIFEST is downloaded, grep and awk are used to find the line in the manifest 
#   that corresponds to the selected pacakge, and then extract the hex hash value. The next operation is to fetch the tarball, 
#   and append "partial" to its filename. This way, if the download operation fails for any reason, the user will know it. Once 
#   the tarball fetch has completed, the hash for the downloaded tarball is determined, and compared to the hash from the MANIFEST. 
#   If the hashes match, then the partial file is renamed to its full name "FreeBSD-i386-9.0-RELEASE-base.txz," for example. This 
#   naming convention is used because the other portions of TheBrig extension use that to know it is an official FreeBSD tarball. 
#   If the hash doesn't match, then the file is renamed to "failed-i386-9.0-RELEASE-base.txz." 
#  
#  Example usage:    ./thebrig_fetch.sh amd64 9.1-RELEASE base /thebrig/work
#  
#  In the above example, the 64 bit version of 9.1-RELEASE's base tarball will be downloaded and placed in /thebrig/work
#  
# ===========================================================================================================================
arch=$1  	# The first argument will be the architecture
rel=$2		# The second argument will be the release
pack=$3		# The third argument will be the desired package
dest=$4		# The fourth argument will be the destination folder (TheBrig's work folder)

# This is the url of the ftp folder, as well as the background redirect (if we need it)
ftp_url="ftp://ftp.freebsd.org/pub/FreeBSD/releases/$arch/$arch/$rel"
redirect=">/dev/null 2>&1"

# Fetch the manifest. place it in the desired folder, and name it based on the release and arch
/usr/bin/fetch -q -o ${dest}/${rel}_${arch}_MANIFEST $ftp_url/MANIFEST >/dev/null 2>$1

# Carry out the grep operation to extract the desired sha256 hash from the manifest
desired_hash=`grep ${pack} ${dest}/${rel}_${arch}_MANIFEST | awk '{print $2}'`

# Determine the file size from the ftp server
desired_size=`/usr/bin/fetch -s ${ftp_url}/${pack}.txz`

# Carry out the tarball fetch command, appending "partial" to the temporary download file
/usr/bin/fetch -q -o ${dest}/FreeBSD-${arch}-${rel}-${pack}_partial_${desired_size}H.txz $ftp_url/$pack.txz >/dev/null 2>$1

# Now that we've finished the download, we need to calculate the hash of the file in order to figure out 
# if the download went well or not
result_hash=`sha256 -q ${dest}/FreeBSD-${arch}-${rel}-${pack}_partial_${desired_size}.txz`

# Now compare the hash from the manifest with the hash from the file we downloaded
if [ $desired_hash eq $result_hash ]; then
	# The hashes match, so we need to rename the tarball to its final name
	mv ${dest}/FreeBSD-${arch}-${rel}-${pack}_partial_${desired_size}.txz ${dest}/FreeBSD-${arch}-${rel}-${pack}.txz
else
	# The hashes do not match, so we need to rename the tarball so we know it failed
	mv ${dest}/${rel}_${arch}_${pack}_partial.txz ${dest}/failed-${arch}-${rel}-${pack}.txz
fi
