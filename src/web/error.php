<?php

function _error_handler_todelete($error_level, $error_message, $error_file, $error_line,$error_context){
	$ip = $_SERVER['REMOTE_ADDR'];
	$str = "$ip - ";
	$str .= "_error_handler(): $error_message - [$error_line] $error_file -  $error_level";
	error_log($str);
}

function _shutdown_handler(){

	$err = error_get_last();
	if (!isset($err)){
		return;
	}

	$error_types = array(
		E_USER_ERROR      => 'USER_ERROR',
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
	$str = "_shutdown_handler($type): {$err['message']} - {$err['file']}@{$err['line']} ({$_SERVER['REMOTE_ADDR']})";
	error_log($str);

	if($err['type'] === E_COMPILE_ERROR || $err['type'] === E_PARSE || $err['type'] === E_USER_ERROR){
		if(ob_get_level()){
			ob_clean();
		}
		header('HTTP/1.1 500 Internal Server Error');
		include '{{ PATH_PUBLIC }}/error/500.html';
		exit;
	}
	if(ob_get_level()){
		ob_clean();
	}
	header('HTTP/1.1 500 Internal Server Error');
	include '{{ PATH_PUBLIC }}/error/500.html';
	exit;
}

function _exception_handler($e){
	if($e->getCode() == 42000){
		error_log("_exception_handler() [DATABASE] : ".db::$query);
	}
	
	$msg = print_r($e,true);
	$line = $e->getLine(); 
	trigger_error("_exception_handler(): ".$e->getMessage() . " [$line]",E_USER_ERROR);
}

//set_error_handler('_error_handler');
set_exception_handler('_exception_handler');
register_shutdown_function('_shutdown_handler');

