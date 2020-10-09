<?php
#if DEBUG
if(isset($_SERVER['_DEBUG'])){
	$_SERVER['_VT_BEG'] = microtime(1);
	$_SERVER['_VM_BEG'] = memory_get_usage();
}
#endif