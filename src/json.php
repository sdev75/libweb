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

mb_internal_encoding('UTF-8');
date_default_timezone_set('{{ TIMEZONE_DEF }}');
set_include_path('{{ INCLUDE_PATH }}');

define('_BASEURL',$_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].'{{ APP_BASEURI }}');
define('_BASEURI','{{ APP_BASEURI }}');
define('_PATH',!empty($_SERVER['REDIRECT_C'])?$_SERVER['REDIRECT_C'].'.json':'{{ PATH_DEF }}.json');
define('_LANG_DEF','{{ LANG_DEF }}');

define('_TIME_B',microtime(1));
define('_MEM_B',memory_get_usage());
define('_REQ_ID',str_pad(rand(1,1000000).time(),10,'0',STR_PAD_RIGHT));
define('_REQ_TS',time());

define('_AMP',false);

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
	define('_URI',mb_substr($uri,3));
	define('_LANG',$_SERVER['REDIRECT_LANG']);
}else{
	define('_URI',$uri);
	define('_LANG','{{ LANG_DEF }}');
}

class json {
	
	public static $error = false;
	public static $error_map = [];
	public static $error_msg;
	public static $error_code;
	public static $error_desc;

	public static $messages = [];
	public static $headers = [];
	public static $req = [];
	public static $res = [];

	public static function error($code,$desc=false){
		if(isset(self::$error_map[$code])){
			self::$error = true;
			self::$error_code = $code;
			self::$error_msg = self::$error_map[$code]['message'];
			self::$error_desc = $desc;
			return;
		}

		self::$error = true;
		self::$error_code = $code;
		self::$error_msg = 'An error has occurred.';
		self::$error_desc = $desc;
	}

	public static function send($status,$data=array()){
		ob_start('ob_gzhandler');
		ob_implicit_flush(1);
		http_response_code($status);

		$res = [];
		
		if(self::$error){
			$res['error'] = [
				'code' => self::$error_code,
				'msg' => self::$error_msg,
				'desc' => self::$error_desc,
			];
		}

		$res['res'] = [];
		$res['res']['timestamp'] = gmdate("Y-m-d\TH:i:s\Z");
		$res['res']['data'] = $data;
		$res['res']['messages'] = self::$messages;
		$res['res'] = array_merge($res['res'],self::$res);

		$res['req'] = [];
		$res['req']['id'] = _REQ_ID;
		$res['req']['timestamp'] = gmdate("Y-m-d\TH:i:s\Z",_REQ_TS);
		$res['req']['time_render'] = number_format(microtime(1)-_TIME_B,5);
		$res['req']['data'] = $_POST;
		$res['req']['uri'] = $_SERVER['REQUEST_URI'];
		//$res['request']['headers'] = getallheaders();
		$res['req']['headers'] = [];
		$res['req'] = array_merge($res['req'],self::$req);
		
		header('Content-Type: application/json; charset=utf8');
		header('X-Content-Type-Options: nosniff');
		
		foreach(self::$headers as $k=>$v){
			header("$k:$v",true);
		}
		
		$res = json_encode($res,
			JSON_FORCE_OBJECT|
			JSON_HEX_QUOT|
			JSON_HEX_TAG|
			JSON_HEX_AMP|
			JSON_HEX_APOS
		);
		echo $res;
	}

}