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

mb_internal_encoding('UTF-8');
date_default_timezone_set('{{ TIMEZONE_DEF }}');
set_include_path('{{ INCLUDE_PATH }}');

define('_BASEURL',$_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].'{{ APP_BASEURI }}');
define('_BASEURI','{{ APP_BASEURI }}');
define('_PATH',!empty($_SERVER['REDIRECT_C'])?$_SERVER['REDIRECT_C']:'{{ PATH_DEF }}');
define('_LANG_DEF','{{ LANG_DEF }}');

define('_AMP',true);
define('_CANONICAL_URL',mb_strtolower(mb_substr($_SERVER['SCRIPT_URI'],0,-4)));

$uri = $_SERVER['REQUEST_URI'];

// remove query string
if(false !== ($pos=mb_strrpos($uri,'?'))){
	$uri = mb_substr($uri,0,$pos);
}

// remove basepath from uri
if(mb_strpos($uri,'{{ APP_BASEURI }}')===0){
	$uri = mb_substr($uri,mb_strlen('{{ APP_BASEURI }}'));
}

if(!empty($_SERVER['REDIRECT_LANG'])){
	define('_URI',mb_substr($uri,3,-4));
	define('_LANG',$_SERVER['REDIRECT_LANG']);
}else{
	define('_URI',mb_substr($uri,0,-4));
	define('_LANG','{{ LANG_DEF }}');
}
