<?php

function _error_handler($error_level, $error_message, $error_file, $error_line,$error_context){
	$request_id = isset($_SERVER['_REQ_ID'])?$_SERVER['_REQ_ID']:'0';
	$ip = $_SERVER['REMOTE_ADDR'];
	$str = "$request_id > $ip - ";
	$str .= "[JSON SERVICE] $error_message - [$error_line] $error_file -  $error_level";
	error_log($str);
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
	$str = "[SHUTDOWN] [JSON SERVICE] {$_SERVER['REMOTE_ADDR']} - {$type} - {$err['file']} - {$err['line']} - {$err['message']}";
	error_log($str);
	
	if($err['type'] === E_COMPILE_ERROR || $err['type'] === E_PARSE){
		$line = dechex($err['line']);
		json::error("CRITICAL_ERROR","An unrecoverable error has occurred. Error code 0x{$line}");
		json::send(500);
		exit;
	}
	
	json::error("INTERNAL_ERROR");
	json::send(500);
	exit;
}

function _exception_handler($e){
	if($e->getCode() == 42000){
		error_log(db::$query);
	}
	trigger_error($e->getMessage(),E_USER_ERROR);
	json::error("INTERNAL_SERVICE_ERROR");
	json::send(500);
	exit;
}

set_error_handler('_error_handler');
set_exception_handler('_exception_handler');
register_shutdown_function('_shutdown_handler');