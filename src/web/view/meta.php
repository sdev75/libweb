<?php

class ViewMeta {
	public static $key;
	public static $filename;
	public static $data = [
		'title'=>null,
		'desc'=>null,
		'robots'=>null,
	];
	public static $keys = [
		'title' => 'meta.title',
		'desc' => 'meta.desc',
		'robots' => 'meta.robots',
	];
	public static $init = false;

	public static function set($k,$v){
		//error_log("META: set($k,$v) fired");
		self::$data[$k] = $v;
	}
	
	public static function setAllIfNotEmpty(){
		$key = self::$key;
		$keys = self::$keys;
		foreach($keys as $k=>$v){
			if(!empty(self::$data[$k])){
				continue;
			}
			
			$val = lang::val("{$key}.{$v}");
			if(empty($val)){
				continue;
			}
			self::$data[$k] = $val;
		}
	}

	public static function getHtml(){
		$res = '';
		$data = self::$data;
		foreach($data as $k=>$v){
			if(empty($v)) {
				continue;
			}
			$v = htmlentities($v,ENT_QUOTES);
			switch($k){
				case 'title':
					$res .= "<title>{$v}</title>";
					break;
				case 'desc':
					$res .= "<meta name=\"description\" content=\"{$v}\">";
					break;
				default:
					$res .= "<meta name=\"{$k}\" content=\"{$v}\">";
			}
		}
		return $res;
	}

	public static function loadFile(){
		if(self::$init){
			return;
		}
		//error_log("META: loadFromFile fired");
		$metas = include self::$filename;
		$key = c::$path;
		if(isset($metas[$key])){
			$array = $metas[$key];
			if(self::$data['title'] === null && isset($array['meta_title'])){
				self::$data['title'] = str_replace(array("\n","\t"),'',$array['meta_title']);
			}
			if(self::$data['desc'] === null && isset($array['meta_desc'])){
				self::$data['desc'] = str_replace(array("\n","\t"),'',$array['meta_desc']);
			}
			if(self::$data['robots'] === null && isset($array['meta_robots'])){
				self::$data['robots'] = $array['meta_robots'];
			}
		}
		if(self::$data['title'] === null){
			self::$data['title'] = str_replace(array("\n","\t"),'',$metas['def']['meta_title']);
		}
		self::$init = true;
	}
}
