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