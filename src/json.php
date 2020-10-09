<?php

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
		self::$error_msg = 'An error has occurred';
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
		$line = dechex($err['line']);
		json::error("CRITICAL_ERROR","An unrecoverable error has occurred. Error code 0x{$line}");
		json::send(500);
		exit(1);
	}

	json::error("INTERNAL_ERROR");
	json::send(500);
	exit;
}

function _except($e){
	if($e->getCode() == 42000){
		error_log("[except] DB Error 42000: ".db::$query);
	}

	$msg = $e->getMessage();
	$ln = $e->getLine(); 
	trigger_error("[except] [line: $ln] $msg", E_USER_ERROR);
	json::error("INTERNAL_SERVICE_ERROR");
	json::send(500);
	exit;
}

// function _error_handler($error_level, $error_message, $error_file, $error_line,$error_context){
// 	$request_id = isset($_SERVER['_REQ_ID'])?$_SERVER['_REQ_ID']:'0';
// 	$ip = $_SERVER['REMOTE_ADDR'];
// 	$str = "$request_id > $ip - ";
// 	$str .= "[JSON SERVICE] $error_message - [$error_line] $error_file -  $error_level";
// 	error_log($str);
// }

// set_error_handler('_error_handler');
set_exception_handler('_exception_handler');
register_shutdown_function('_shutdown_handler');

define('_BASEURL',$_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].'{{ APP_BASEURI }}');
define('_BASEURI','{{ APP_BASEURI }}');
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
define('_LANG','{{ LANG_DEF }}');
