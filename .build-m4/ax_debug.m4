

AC_ARG_ENABLE(debug,
AS_HELP_STRING([--enable-debug],
               [enable debugging, default: no]),
[case "${enableval}" in
             yes) debug=true ;;
             no)  debug=false ;;
             *)   AC_MSG_ERROR([bad value ${enableval} for --enable-debug]) ;;
esac],
[debug=false])
AM_CONDITIONAL(DEBUG, test x"$debug" = x"true")
#AC_SUBST([DEBUG])

if test "x$debug" = xtrue; then
AC_DEFINE([DEBUG], [1], [Debug configuration --enable-debug])
else
AC_DEFINE([DEBUG], [0], [Debug configuration --enable-debug])
fi


