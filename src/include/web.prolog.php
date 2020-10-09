<?php
#if DEBUG
if(isset($_GET['debug']) && $_GET['debug'] === '33967'){
	$_SERVER['_GT_BEG'] = microtime(1);
	$_SERVER['_GM_BEG'] = memory_get_usage();
	$_SERVER['_DEBUG'] = true;
}
#endif

