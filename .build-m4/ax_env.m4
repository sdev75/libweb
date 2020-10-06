# build configuration files based on NS and ENV vars
CNUL=`tput sgr0`
COK=`tput setaf 2`
CERR=`tput setaf 1`
CWARN=`tput setaf 3`
CINFO=`tput setaf 4`
CDEBUG=`tput dim`
FBOLD=`tput bold`

abs_srcdir=$(realpath $srcdir)
abs_thisdir=$(realpath .)

# force prefix to be set to current folder
test "$prefix" = NONE && prefix=$abs_thisdir
# prevent accidental builds in the root dir
if test "x$PWD" = "x$abs_srcdir" ; then
	msg="Building in the root directory not allowed: $abs_srcdir ..."
	AC_MSG_ERROR([${FBOLD}${CERR}$msg${CNUL}])
fi