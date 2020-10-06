<?php

function _error_handler($error_level, $error_message, $error_file, $error_line,$error_context){
	$id = isset($_SERVER['_REQ_ID'])?$_SERVER['_REQ_ID']:'0';
	$str = "_error_handler(): $id - $error_message - [$error_line] $error_file -  $error_level";
	error_log($str);
	exit;
}

function _exception_handler($e){
	echo "_exception_handler(): ".$e->getMessage() . "Line: ".$e->getLine();
	exit;
}

function _shutdown_handler(){
	
	$err = error_get_last();
	if (!isset($err)){
		return;
	}

	$error_types = array(
		E_USER_ERROR      => 'USER ERROR',
		E_ERROR           => 'ERROR',
		E_PARSE           => 'PARSE',
		E_CORE_ERROR      => 'CORE_ERROR',
		E_CORE_WARNING    => 'CORE_WARNING',
		E_COMPILE_ERROR   => 'COMPILE_ERROR',
		E_COMPILE_WARNING => 'COMPILE_WARNING',
		E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
	);

	if(!isset($error_types[$err['type']])){
		return;
	}
	
	$type = $error_types[$err['type']];
	$str = "_shutdown_handler(): Type: {$type} File: '{$err['file']}' @ {$err['line']} : {$err['message']}";
	error_log($str);
	exit;
}

set_error_handler('_error_handler');
set_exception_handler('_exception_handler');
register_shutdown_function('_shutdown_handler');