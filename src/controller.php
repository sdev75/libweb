<?php

class c {
	
	public static $files;
	public static $routes;
	public static $route;
	
	public static $path;
	public static $name;
	public static $action;
	public static $lang;
	public static $fullpath;
	
	public static $parts = [];
	public static $errors = [];
	public static $messages = [];
	
	public static $link = [];
	public static $links = [];
	public static $breadcrumb = [];
	public static $errorfields = [];
	public static $fields = [];
	public static $field = [];
	public static $data = [];
	
	public static $redirected = false;
	
	public static function redirect($uri,$query=false,$autofill=false){
		$_SESSION['{{session_prefix}}_REQUEST'] = $_REQUEST;
		$_SESSION['{{session_prefix}}_ERRORFIELDS']  = self::$errorfields;
		$_SESSION['{{session_prefix}}_MESSAGES'] = self::$messages;

		if(mb_substr($uri,0,4)!=='http'){
			$url = _url($uri,$query,$autofill);
		}else{
			$url = $uri;
		}
		session_write_close();
		header('Location: '.$url);
	}
	
	public static function setErrorFields(){
		//$arr = [];
		foreach(self::$errorfields as $k=>$v){
			if(isset(self::$fields[$v])){
				self::$fields[$v]['error'] = true;
			}
			//$arr[$v]=1;
		}
		//self::$errorfields = $arr;
	}


}
