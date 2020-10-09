<?php
#if DEBUG
if(isset($_GET['debug']) && $_GET['debug'] === '33967'){
	$_SERVER['_VT_BEG'] = microtime(1);
	$_SERVER['_VM_BEG'] = memory_get_usage();
	$_SERVER['_DEBUG'] = true;
}
#endif