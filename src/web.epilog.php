<?php
#if DEBUG
if(isset($_SERVER['_DEBUG'])){
$_SERVER['_GT_END'] = microtime(1);
$_SERVER['_GM_END'] = memory_get_usage();

$fmt = "logic %.6f [%d kb]";
$msg = sprintf($fmt, 
	$_SERVER['_GT_END']-$_SERVER['_GT_BEG'],
	$_SERVER['_GM_END']-$_SERVER['_GM_BEG']
);

error_log($msg,E_USER_NOTICE);
}
#endif