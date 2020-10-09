#                                               -*- Autoconf -*-
# Process this file with autoconf to produce a configure script.
m4_include([VERSION])

AC_PREREQ([2.69])
AC_INIT([libw],
m4_esyscmd_s(cat VERSION),
[29279974+sdev75@users.noreply.github.com])

AC_CONFIG_AUX_DIR([.build-aux])
AC_CONFIG_MACRO_DIR([.build-m4])

AM_INIT_AUTOMAKE([foreign subdir-objects])

#m4_ifdef([AM_SILENT_RULES], [AM_SILENT_RULES([yes])])

m4_include([.build-m4/ax_debug.m4])
m4_include([.build-m4/ax_env.m4])

# if test "x$debug" = xtrue; then
# else
# fi

AC_CONFIG_FILES([
  Makefile
  libver.sh
])
AC_OUTPUT
cat << EOF
-------------------------------------------------

Summary:

${PACKAGE_NAME} ${PACKAGE_VERSION}

 Debug Enabled     = $enable_debug
        Config     = $config
  
--------------------------------------------------
EOF

chmod +x $abs_builddir/libver.sh