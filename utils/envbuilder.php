<?php

class EnvBuilder {
	public static $array = [];
	public static function parse(string $data){
		//$data = file_get_contents($filename);
		$lines = explode("\n", $data);
		foreach($lines as $line){
			$pair = explode("=", $line);
			if(count($pair) < 2){
				fprintf(STDERR, "\e[0;93m Skipped $line\e[0m\n");
				continue;
			}
			$t = mb_convert_case($pair[0],MB_CASE_LOWER);
			self::$array[$t] = ['key'=>$pair[0], 'val'=>$pair[1]];
		}

		foreach(self::$array as $k => &$v){
			// find $KEYWORD
			$re = '/\$([a-zA-Z0-9_]+)/i';
			preg_match_all($re,$v['val'],$matches,PREG_SET_ORDER,0);
			if($matches){
				foreach($matches as $match){
					$t = mb_convert_case($match[1],MB_CASE_LOWER);
					if(isset(self::$array[$t])){
						$search = $match[0];
						$replace = self::$array[$t]['val'];
						$replace = str_replace('"','',$replace);
						$subject = $v['val'];
						$v['val'] = str_ireplace($search,$replace,$subject);
					}
				}
			}
		}

		unset($v);
	}

	public static function getVars(){
		return self::$array;
	}

	public static function import(string $filename){
		$data = file_get_contents($filename);
		$lines = explode("\n", $data);
		foreach($lines as $line){
			$pair = explode("=", $line);
			if(count($pair) < 2){
				continue;
			}
			$t = mb_convert_case($pair[0],MB_CASE_LOWER);
			$pair[1] = str_replace('"','',$pair[1]);
			self::$array[$t] = $pair[1];
		}
	}
}