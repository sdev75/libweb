<?php

class lang {
	public static $langs;
	public static $data;
	public static $def_code;
	public static $code;

	public static function init($lang_code){
		self::$data = self::$langs[$lang_code];
	}

	public static function get($code){
		return self::$langs[$code] ?? false;
	}

	/*********************************************
	 * Parse variables from db using params
	 *
	 * lang::val('getstarted.title','cnt'=>number_format(3500,0)]);
	 *
	 *************************************************/
	public static function val($k,array $params=[]){
		$val = langvar::getval($k,lang::$data['id']);
		if(!$val){
			return null;
		}

		foreach($params as $k=>$v){
			$val = str_replace("{{{$k}}}",$v,$val);
		}

		return $val;
	}

	/*********************************************
	 * Parse variables from db using params
	 *
	 * lang::vals('getstarted',[
	 * 	'desc'=>['cnt'=>number_format(3500,0)]
	 * ]);
	 *
	 *************************************************/

	public static function loadvals($k,array $params=[]){
		$vals = langvar::getvals("$k.%",lang::$data['id']);
		if(!$vals){
			return [];
		}
		foreach($params as $key => $reps){
			$name = "{$k}_{$key}";
			if(!isset($vals[$name])){
				throw new Exception("vals: key does not exist: $name");
			}
			$val = $vals[$name];
			foreach($reps as $rk => $rv){
				$val = str_replace("{{{$rk}}}",$rv,$val);
			}
			$vals[$name] = $val;
		}
		return $vals;
	}

	public static function vals($k,array $params=[]){
		$vals = langvar::getvals("$k.%",lang::$data['id']);
		if(!$vals){
			return [];
		}
		foreach($vals as &$val){
			foreach($params as $key => $v){
				$val = str_replace("{{{$key}}}",$v,$val);
			}
		}
		return $vals;
	}

	public static function replaceEachVal(array &$vals,array $array){
		foreach($array as $k => $reps){
			if(!isset($vals[$k])){
				continue;
			}
			$val = $vals[$k];
			foreach($reps as $rk => $rv){
				$val = str_replace("{{{$rk}}}",$rv,$val);
			}
			$vals[$k] = $val;
		}
	}

	public static function replaceAllVals(array &$vals,array $array){
		foreach($vals as &$val){
			foreach($array as $k => $v){
				$val = str_replace("{{{$k}}}",$v,$val);
			}
		}
	}

	public static function number($val,$decimals=0,$lang_code=null){
		if($lang_code !== NULL){
			$data = self::$langs[$lang_code];
			$dec_point = $data[$lang_code]['dec_point'];
			$thousands_sep = $data[$lang_code]['thousands_sep'];
			$res = number_format($val,$decimals,$dec_point,$thousands_sep);
		}else{
			$res = number_format($val,$decimals,self::$data['dec_point'],self::$data['thousands_sep']);
		}

		return $res;
	}

}

class langvar {

	// if lang_id doesnt hold value, it will attempt to load val from alt_lang_id
	// 1 is english, so if you are looking for a value in spanish (2) and is not existing
	// automatically it will look for english (1) and return the val
	public static function get($key,$lang_id=1,$alt_lang_id=1){
		$lang_id = (int) $lang_id;
		$alt_lang_id = (int) $alt_lang_id;
		db::$params = [$key,'{{app_ns}}'];
		$array = db::selectOne("
			SELECT 
				lv_id, lang_id, k, v
			FROM lang_var2
			WHERE k = ? AND lang_id IN ($lang_id,$alt_lang_id)
			ORDER BY lang_id = $lang_id AND ns = ? DESC
			LIMIT 1
		");
		if(!$array){
			return false;
		}
		$res = [];
		$res['id'] = $array['lv_id'];
		$res['val'] = $array['v'];
		return $res;
	}

	public static function getval($key,$lang_id=1,$alt_lang_id=1){
		$lang_id = (int) $lang_id;
		$alt_lang_id = (int) $alt_lang_id;
		db::$params = [$key,'{{app_ns}}'];
		$array = db::selectOne("
			SELECT v
			FROM lang_var2
			WHERE k = ? AND lang_id IN ($lang_id,$alt_lang_id)
			ORDER BY lang_id = $lang_id AND ns = ? DESC
			LIMIT 1
		");
		if(!$array){
			return false;
		}
		$res = $array['v'];
		return $res;
	}

	public static function getvals($key,$lang_id=1,$alt_lang_id=1){
		$lang_id = (int) $lang_id;
		$alt_lang_id = (int) $alt_lang_id;
		db::$params = [':k'=>$key,':ns'=>'{{app_ns}}'];
		$rows = db::selectAll("
			SELECT k,v FROM (
				SELECT k,v FROM lang_var2 WHERE k LIKE :k AND lang_id = $lang_id AND ns = :ns
				UNION
				SELECT k,v FROM lang_var2 WHERE k LIKE :k AND lang_id = $alt_lang_id AND ns = :ns
			) x GROUP BY k
		");
		if(!$rows){
			return false;
		}
		$res = [];
		foreach($rows as $row){
			$k = str_replace('.','_',$row['k']);
			$res[$k] = $row['v'];
		}

		return $res;
	}
	
	public static function updateTODO($key,$lang_id,$val){
		$lang_id = (int) $lang_id;
		db::$params = [$key,'{{app_ns}}'];
		$array = db::selectOne("
			SELECT 
				lv_id, lang_id, k, v
			FROM lang_var2
			WHERE k = ? AND lang_id = $lang_id AND ns = ?
			LIMIT 1
		");
		if($array){
			db::update('lang_var2','lv_id',$array['lv_id'],[
				'v' => $val,
			]);
			$res = $array['lv_id'];
		}else{
			$res = db::insert('lang_var2',[
				'k' => $key,
				'v' => $val,
				'lang_id' => $lang_id,
			]);
		}
		return $res;
	}

}