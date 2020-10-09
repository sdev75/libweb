<?php
#if DEBUG
// view
$_SERVER['_VT_END'] = microtime(1);
$_SERVER['_VM_END'] = memory_get_usage();
$_SERVER['_VT'] = $_SERVER['_VT_END']-$_SERVER['_VT_BEG'];
$_SERVER['_VM'] = $_SERVER['_VM_END']-$_SERVER['_VM_BEG'];

$fmt = "%s (%s) - view: %.8fs [%d kb]";
$msg = sprintf($fmt, 
$_SERVER['REQUEST_URI'],
$_SERVER['SCRIPT_NAME'],
$_SERVER['_VT'],
$_SERVER['_VM'],
);

error_log($msg);
#endif