<?php
#if DEBUG
if(isset($_SERVER['_DEBUG'])){
	// view
	$_SERVER['_GT_END'] = microtime(1);
	$_SERVER['_GM_END'] = memory_get_usage();
	$_SERVER['_GT'] = $_SERVER['_GT_END']-$_SERVER['_GT_BEG'];
	$_SERVER['_GM'] = $_SERVER['_GM_END']-$_SERVER['_GM_BEG'];

	$fmt = "%s (%s) - logic: %.8fs [%d kb]";
	$msg = sprintf($fmt, 
	$_SERVER['REQUEST_URI'],
	$_SERVER['SCRIPT_NAME'],
	$_SERVER['_GT'],
	$_SERVER['_GM'],
	);

	error_log($msg,E_USER_NOTICE);
}
#endif