#!/bin/bash
source build_vars.sh

# Add build string for nightly builds
if [ "$1" == "nightly" ]; then
	LOCAL_VERSION="$VERSION-$BUILD"
else
	LOCAL_VERSION=$VERSION
fi

if [ ! -d $RPMHOME/SOURCES ]; then
	mkdir -p $RPMHOME/SOURCES || exit 1
fi

echo "Creating source tarballs in $RPMHOME/SOURCES"

# Console
cd $CODEHOME; cd .. && tar zcvf $RPMHOME/SOURCES/IntegriaIMS-$LOCAL_VERSION.tar.gz --exclude \.svn --exclude config.php --exclude enterprise trunk || exit 1

# Enterprise 
cd $PANDHOME_ENT && tar zcvf $RPMHOME/SOURCES/IntegriaIMS_enterprise-$LOCAL_VERSION.tar.gz --exclude \.svn enterprise/* || exit 1

# Create symlinks needed to build RPM packages
if [ "$1" == "nightly" ]; then
	ln -s $RPMHOME/SOURCES/IntegriaIMS-$LOCAL_VERSION.tar.gz $RPMHOME/SOURCES/IntegriaIMS-$VERSION.tar.gz || exit 1
	ln -s $RPMHOME/SOURCES/IntegriaIMS_enterprise-$LOCAL_VERSION.tar.gz $RPMHOME/SOURCES/IntegriaIMS_enterprise-$VERSION.tar.gz || exit 1
fi

exit 0
