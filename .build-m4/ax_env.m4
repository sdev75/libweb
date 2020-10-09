# build configuration files based on NS and ENV vars
CNUL=`tput sgr0`
COK=`tput setaf 2`
CERR=`tput setaf 1`
CWARN=`tput setaf 3`
CINFO=`tput setaf 4`
CDEBUG=`tput dim`
FBOLD=`tput bold`

abs_srcdir=$(realpath $srcdir)
abs_builddir=$(realpath .)
abs_thisdir=$(realpath .)

# force prefix to be set to current folder
test "$prefix" = NONE && prefix=$abs_thisdir
# prevent accidental builds in the root dir
if test "x$PWD" = "x$abs_srcdir" ; then
	msg="Building in the root directory not allowed: $abs_srcdir ..."
	AC_MSG_ERROR([${FBOLD}${CERR}$msg${CNUL}])
fi

printf "%-50s " "Checking configuration env data..."
config_filename="$abs_builddir/src/config.env"
if ! test -f "$config_filename"; then
	msg="Missing configuration file: $config_filename";
	AC_MSG_ERROR([${FBOLD}${CERR}$msg${CNUL}])
fi
printf "%-12s\n" "${COK}OK${CNUL}"

ENV_BUF=$(cat $config_filename | grep "^DEPLOY_" )
if test -z "$ENV_BUF"; then
	DEPLOYABLE=0
else
	DEPLOYABLE=1
	export $(echo "$ENV_BUF" | xargs)
	AC_SUBST([DEPLOY_HOST])
	AC_SUBST([DEPLOY_USER])
	AC_SUBST([DEPLOY_PATH])
fi
AM_CONDITIONAL([DEPLOYABLE], [test x$DEPLOYABLE = x1])
AC_SUBST([DEPLOYABLE])

# echo -e $xxx > $abs_builddir/test.sh
# chmod +x $abs_builddir/test.sh
# cp $abs_srcdir/libver.sh $abs_builddir/libver.sh
# chmod +x $abs_builddir/libver.sh