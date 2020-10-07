<?php

class Acl {
	public static $rules = [];
	
	public static function init($rules){
		if(is_array($rules)){
			self::$rules = $rules;
		}else{
			self::$rules = include $rules;
		}
	}

	public static function allowed(string $path):bool {
		if (!isset(self::$rules[$path])) {
			return false;
		}
		return (bool) self::$rules[$path];
	}

}