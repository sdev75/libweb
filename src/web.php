<?php

// $_error['field_id'] = "error with psasword"
function _shut(){
	$e = error_get_last();
	if($e === NULL){

		if(!empty($_redirect)){
			if(session_status() === PHP_SESSION_ACTIVE){
				$_SESSION['_msg'] = $_msg;
				$_SESSION['_errors'] = $_errors;
				$_SESSION['_redirect'] = true;
				session_write_close();	
			}
			header("Location: {$_redirect}");
		}
		error_log("SESSION STATUS: ". session_status());
		return;
	}

	$buf = sprintf("[exit] IP: %s Type: %d File: %s @ %d",
		$_SERVER['REMOTE_ADDR'], $e['type'], $e['file'], $e['line']);
	error_log($buf);
	unset($buf);

	if($e['type'] === E_COMPILE_ERROR ||
		$e['type'] === E_PARSE || 
		$e['type'] === E_USER_ERROR){
		header('HTTP/1.1 500 Internal Server Error');
		include '{{ PATH_PUBLIC }}/error/500.html';
		exit(1);
	}
}

function _except($e){
	if($e->getCode() == 42000){
		error_log("[except] DB Error 42000: ".db::$query);
	}

	$msg = $e->getMessage();
	$ln = $e->getLine(); 
	trigger_error("[except] [line: $ln] $msg", E_USER_ERROR);
}

set_exception_handler('_except');
register_shutdown_function('_shut');

define('_BASE_URL',$_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].'{{ APP_BASEURI }}');
define('_BASE_URI','{{ APP_BASEURI }}');
define('_LANG_DEF','{{ LANG_DEF }}');

$uri = $_SERVER['REQUEST_URI'];
// remove query string
if(false !== ($pos=mb_strrpos($uri,'?'))){
	$uri = mb_substr($uri,0,$pos);
}

// remove basepath from uri
if(mb_strpos($uri,'{{ APP_BASEURI }}')===0){
	$uri = mb_substr($uri,mb_strlen('{{ APP_BASEURI }}'));
}

define('_URI',$uri);

$_redirect = false;
$_msg = [];
$_errors = [];

