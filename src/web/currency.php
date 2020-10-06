<?php

class currency {
	public static $data=[];
	public static function fmt($amt,$cur_code='USD'){
		$data = self::$data[$cur_code];
		$str = number_format($amt,2,$data['cur_dec_point'],$data['cur_thousands_sep']);
		if($cur_code === 'USD'){
			$res = "{$data['cur_short_symbol']}{$str}";
		}elseif($cur_code === 'EUR'){
			$res = "{$data['cur_short_symbol']}{$str}";
		}elseif($cur_code === 'GBP'){
			$res = "{$data['cur_short_symbol']}{$str}";
		}elseif($cur_code === 'RON'){
			$res = "{$str} {$data['cur_short_symbol']}";
		}
		return $res;
	}
	public static function sprintf($format,$amt,$cur_code='USD'){
		$data = self::$data[$cur_code];
		$str = number_format($amt,2,$data['cur_dec_point'],$data['cur_thousands_sep']);
		$res = vsprintf($format,array($str,$data['cur_code'],$data['cur_symbol'],$data['cur_short_symbol']));
		return $res;
	}
}