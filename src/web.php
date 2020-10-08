<?php

function _shut(){
	$e = error_get_last();
	if($e === NULL)
		return;

	$fmt = "[shut] File: {$e['file']} @ {$e['line']} Type: {$e['type']} IP: {$_SERVER['REMOTE_ADDR']}";
	error_log($fmt);

	if($e['type'] === E_COMPILE_ERROR ||
		$e['type'] === E_PARSE || 
		$e['type'] === E_USER_ERROR){
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
define('_LANG_DEF','{{ LANG_DEF }}');

#if DEBUG
if(isset($_GET['debug']) && $_GET['debug'] === '33967'){
	$_SERVER['_GT_BEG'] = microtime(1);
	$_SERVER['_GM_BEG'] = memory_get_usage();
	$_SERVER['_DEBUG'] = true;
}
#endif