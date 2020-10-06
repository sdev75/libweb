<?php

function _error_handler($error_level, $error_message, $error_file, $error_line,$error_context){
	$request_id = isset($_SERVER['_REQ_ID'])?$_SERVER['_REQ_ID']:'0';
	$ip = $_SERVER['REMOTE_ADDR'] ?? 'localhost';
	$str = "$request_id > $ip - ";
	$str .= "$error_message - [$error_line] $error_file -  $error_level";
	throw new Exception($str);
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
	$str = "[SHUTDOWN] {$_SERVER['REMOTE_ADDR']} - {$type} - {$err['file']} - {$err['line']} - {$err['message']}";

	if($err['type'] === E_COMPILE_ERROR || $err['type'] === E_PARSE){
		throw new Exception("E_COMPILE_ERROR or E_PARSE error: $str");
	}

	throw new Exception($str);
}

function _exception_handler($e){
	$file = $e->getFile();
	$line = $e->getLine();
	$msg = $e->getMessage();
	fprintf(STDERR, "\e[0;91mException: $file @ $line: $msg\e[0m\n");
	exit(1);
}

error_reporting(E_ALL);
set_exception_handler('_exception_handler');
set_error_handler('_error_handler');
register_shutdown_function('_shutdown_handler');
