<?php

$_view['_msg'] = $_msg;
$_view['_errors_json'] = "[]";
if(!empty($_errors)){
	$_view['_errors'] = [];
	foreach($_errors as $a){
		$_view['_errors'][$a[0]] = [
			'id'=>$a[0],
			'msg'=>$a[1]??'',
			'value'=>$a[2]??'',
		];
	}
	unset($_errors);
	$_view['_errors_json'] = json_encode($_view['_errors']);
}

$_SERVER['_VT_BEG'] = microtime(1);
$_SERVER['_VM_BEG'] = memory_get_usage();