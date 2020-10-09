<?php
#if DEBUG

// controller
$_SERVER['_CT_END'] = microtime(1);
$_SERVER['_CM_END'] = memory_get_usage();
$_SERVER['_CT'] = $_SERVER['_CT_END']-$_SERVER['_CT_BEG'];
$_SERVER['_CM'] = $_SERVER['_CM_END']-$_SERVER['_CM_BEG'];

$fmt = "%s (%s) - logic: %.8fs [%d kb]";
$msg = sprintf($fmt, 
	$_SERVER['REQUEST_URI'],
	$_SERVER['SCRIPT_NAME'],
	$_SERVER['_CT'],
	$_SERVER['_CM'],
);

error_log($msg);
#endif