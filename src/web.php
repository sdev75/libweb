<?php

function _shut(){

	$e = error_get_last();
	if($e === NULL)
		return;

	$fmt = "[shut] File: {$e['file']} @ {$e['line']} Type: {$e['type']} IP: {$_SERVER['REMOTE_ADDR']}";
	error_log($fmt);

	if($e['type'] === E_COMPILE_ERROR || $e['type'] === E_PARSE || $err['type'] === E_USER_ERROR){
		if(ob_get_level())
			ob_clean();
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

ob_start();
mb_internal_encoding('UTF-8');
date_default_timezone_set('{{ TIMEZONE_DEF }}');
set_include_path('{{ INCLUDE_PATH }}');

define('_BASEURL',$_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].'{{ APP_BASEURI }}');
define('_BASEURI','{{ APP_BASEURI }}');
define('_PATH',!empty($_SERVER['REDIRECT_C'])?$_SERVER['REDIRECT_C']:'{{ PATH_DEF }}');
define('_LANG_DEF','{{ LANG_DEF }}');
define('_AMP',false);
define('_CANONICAL_URL',rtrim(mb_strtolower($_SERVER['SCRIPT_URI']),'/'));

$t = $_SERVER['REQUEST_URI'];
// remove query string
if(false !== ($pos=mb_strrpos($t,'?'))){
	$t = mb_substr($t,0,$pos);
}
// remove basepath from uri
if(mb_strpos($t,'{{ APP_BASEURI }}')===0){
	$t = mb_substr($t,mb_strlen('{{ APP_BASEURI }}'));
}

if(!empty($_SERVER['REDIRECT_LANG'])){
	define('_URI',mb_substr($t,3));
	define('_LANG',$_SERVER['REDIRECT_LANG']);
}else{
	define('_URI',$t);
	define('_LANG','{{ LANG_DEF }}');
}