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
	
	public static function init(string $path,string $lang){
		self::$lang = $lang;
		self::setpath($path);
		lang::init($lang);
	}
	
	public static function setpath($path){
		$array = explode('/',$path);
		$action = array_pop($array);
		$name = implode('/',$array);
		self::$parts = explode('/',$path);
		self::$action = $action;
		self::$path = $path;
		self::$name = $name;
		self::$fullpath = $_SERVER['REQUEST_METHOD']==='POST'?"{$path}.post":$path;
	}
	
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
	
	public static function isCacheExpired():bool{
		return (bool) (v::expired() || self::$redirected);
	}

	public static function match(string $path,string $lang):array {
		
		$route = self::$routes[$path] ?? false;

		if(!$route){
			$code = 1000;
			$desc = "The URL requested does not match any rules";
			$link = $_SERVER['_404_URI'];
			return [$code,$desc,$link];
		}

		if (!isset($route['langs'][$lang])){
			$code = 2000;
			$desc = "The lang requested does not exist in the route config";
			$link = _BASEURL._PATH;
			return [$code,$desc,$link];
		}

		$link = self::$links[$lang][$route['linkkey']];

		if (_AMP && !$route['amp']) {
			$code = 3000;
			$desc = "The requested AMP link does not exist for this route";
			$link = $link['url'];
			return [$code,$desc,$link];
		}

		$route['home'] = (bool) ($route['flag'] & 2);
		$route['canon'] = (bool) !($route['flag'] & 1);
		$route['cachable'] = (bool) ($route['flag'] & 16);
		$route['executable'] = (bool) ($route['flag'] & 32);
		$route['link'] = $link;
		$route['lang'] = $lang;

		// add links.en.(id) and if selected language exists merge it in links.(id) directly because it's primary
		self::$link = $link;
		self::$links = array_merge(self::$links,self::$links[$lang]);
		self::$route = $route;
		return [0,0,0];
	}
	
	public static function dispatch(string $path,string $lang){
		list($code,$desc,$link) = self::match($path,$lang);
		if($code){
			$params = [$path,$lang,$link,$code,$desc];
			$debug = vsprintf("path: %s [%s] link:%s, code: %s, desc:%s",$params);
			error_log("\n*** [ROUTER] /!\\ {$debug}\n");
			switch($code){
				case 1000:
					http_response_code(404);
				case 2000:
					http_response_code(301);
				case 3000:
					http_response_code(302);
				default:
					header("Location: $link");
					exit(0);
			}
		}

		self::$lang = $lang;
		self::setpath(self::$route['path']);
		lang::init($lang);
	}

	public static function dispatchJson(string $path,string $lang){
		list($code,$desc,$link) = self::match($path,$lang);
		if($code){
			$params = [$path,$lang,$link,$code,$desc];
			$debug = vsprintf("path: %s [%s] link:%s, code: %s, desc:%s",$params);
			error_log("\n*** [ROUTER] /!\\ {$debug}\n");
			switch($code){
				case 1000:
					json::error("NOT_FOUND","File not found (1)");
					json::send(404);
				case 2000:
					json::error("NOT_FOUND","File not found (2)");
					json::send(301);
				case 3000:
					json::error("NOT_FOUND","File not found (3)");
					json::send(302);
				default:
					exit(0);
			}
		}

		self::$lang = $lang;
		self::setpath(self::$route['path']);
		lang::init($lang);
	}


}
