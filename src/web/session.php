<?php
// session['
class sess {
	
	public static $prefix = '';
	
	public static $redirected = false;

	public static function init($name,$ttl=3600){
		session_name($name);
		session_start(['cookie_lifetime'=>$ttl]);
		$time = time();

		if(isset($_SESSION['time_updated']) && ($time - $_SESSION['time_updated'] > $ttl)){
			_destroySession();
		}
		$_SESSION['time_updated'] = $time;
		
		$prefix = self::$prefix;
		$messages = "{$prefix}_MESSAGES";
		$errorfields = "{$prefix}_ERRORFIELDS";
		$request = "{$prefix}_REQUEST";

		if(isset($_SESSION[$request])){
			$_REQUEST += $_SESSION[$request];
			unset($_SESSION[$request]);
			self::$redirected = true;
		}

		if(isset($_SESSION[$errorfields]) && !empty($_SESSION[$errorfields])){
			c::$errorfields = $_SESSION[$errorfields];
			unset($_SESSION[$errorfields]);
		}

		if(isset($_SESSION[$messages])){
			c::$messages = $_SESSION[$messages] + c::$messages;
			unset($_SESSION[$messages]);
		}
	}
	
	public static function setData($key,$data){
		$prefix = self::$prefix;
		$_SESSION["{$prefix}_{$key}"] = $data;
	}

}