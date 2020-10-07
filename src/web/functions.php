<?php

function _query(array $params,$autofill=false){
	// build http_query using REQUEST_URI
	if($autofill){
		$array = explode('?',$_SERVER['REQUEST_URI'],2);
		if(count($array)===2){
			parse_str($array[1],$query_arr);
			$params += $query_arr;
		}
	}

	// build http_query and append it to the result
	$http_query = http_build_query((array)$params);
	if($http_query){
		$http_query = '?'.$http_query;
	}

	return $http_query;
}

function _querystr($http_query){
	return urldecode($http_query);
}

function _https($uri,$query=false,$autofill=false){
	$uri = _uri($uri,$query,$autofill);
	return "https://{$_SERVER['HTTP_HOST']}{{ APP_BASEURI }}{$uri}";
}

function _http($uri,$query=false,$autofill=false){
	$uri = _uri($uri,$query,$autofill);
	return "http://{$_SERVER['HTTP_HOST']}{{ APP_BASEURI }}{$uri}";
}

function _url($uri,$query=false,$autofill=false){
	$uri = _uri($uri,$query,$autofill);
	return _BASEURI."{$uri}";
}

function _uri($uri,$query=false,$autofill=false){
	$res = trim("$uri",'/');
	
	if($query || $autofill){
		if(is_array($query)){
			$query = _query($query,$autofill);
		}
		$res .= $query;
	}
	return urldecode($res);
}

function _h($string){
	return htmlentities($string,ENT_QUOTES,'UTF-8');
}

function _get_uploaded_file($array){

	$res = array();
	$res['name'] = $array['name'];
	$res['size'] = $array['size'];
	//$res['type'] = $array['type'];
	$res['type'] = mime_content_type($array['tmp_name']);
	$res['ext'] = pathinfo($array['name'],PATHINFO_EXTENSION);
	$res['path'] = pathinfo($array['tmp_name'],PATHINFO_DIRNAME);
	$res['filename'] = $array['tmp_name'];
	$res['error'] = false;

	if(!is_uploaded_file($res['filename'])){
		$res['error'] = true;
		$res['error_message'] = 'is_uploaded_file returned false';
		return $res;
	}

	if(isset($array['error']) && $array['error']){
		$data['error'] = true;
		$data['error_message'] = $array['error'];
		return $res;
	}

	return $res;
}

function _get_uploaded_files($array){
	$res = array();

	if($array['error'] == 4){
		return false;
	}
	
	if(!is_array($array['name'])){
		$res [] = _get_uploaded_file($array);
		return $res;
	}

	$len = count($array['name']);
	for($i=0; $i<$len; $i++){

		$data = array();

		$data['name'] = $array['name'][$i];
		$data['size'] = $array['size'][$i];
		$data['type'] = mime_content_type($array['tmp_name'][$i]);
		$data['ext'] = pathinfo($array['name'][$i],PATHINFO_EXTENSION);
		$data['path'] = pathinfo($array['tmp_name'][$i],PATHINFO_DIRNAME);
		$data['filename'] = $array['tmp_name'][$i];

		if(!is_uploaded_file($data['filename'])){
			$data['error'] = true;
			$data['error_message'] = 'is_uploaded_file returned false';
			continue;
		}

		if(isset($array['error'][$i]) && $array['error'][$i]){
			$data['error'] = true;
			$data['error_message'] = $array['error'][$i];
			continue;
		}

		$res[]=$data;
	}

	return $res;
}

function _getAjaxUploadedFile(){
	$file = file_get_contents('php://input');
	$filename = tempnam("/tmp","ajaxfile");
	file_put_contents($filename,$file);

	$filesize = (int) $_SERVER['CONTENT_LENGTH'];
	if(isset($_SERVER['HTTP_X_FILENAME'])){
		$name = $_SERVER['HTTP_X_FILENAME'];
	}else{
		$name = false;
	}

	$res = array();
	$res['name'] = $name;
	$res['size'] = $filesize;
	$res['type'] = mime_content_type($filename);
	$res['ext'] = pathinfo($name,PATHINFO_EXTENSION);
	$res['path'] = pathinfo($filename,PATHINFO_DIRNAME);
	$res['filename'] = $filename;

	if(!$filesize){
		$data['error'] = true;
		$data['error_message'] = 'File uploaded is empty';
	}

	return $res;
}

function _makedir($path){
	if(!is_dir($path)){
		mkdir($path,0775,true);
		if(!is_dir($path)){
			$reason = sprintf('FAIL - is_dir() - permission errors for: "%s"',$path);
			throw new Exception($reason);
		}
	}
}

function _guid(){
	$res = sprintf('%04x%04x%04x%04x%04x%04x%04x%04x',
		mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
		mt_rand( 0, 0x0fff ) | 0x4000,
		mt_rand( 0, 0x3fff ) | 0x8000,
		mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ) );
	return $res;
}

function _randnum21(){
	$r = hexdec(uniqid('',true));
	$a = str_split($r,2);
	$a[0] = mt_rand(1,9).$a[0];
	$a[1] = mt_rand(0,9).$a[1];
	$a[2] = mt_rand(1,9).$a[2];
	$a[3] = mt_rand(0,9).$a[3];
	$a[4] = mt_rand(1,9).$a[4];
	$a[5] = mt_rand(0,9).$a[5];
	$x = implode('',$a);
	$x = str_replace(array('E+','.'),'',$x);
	$x = mt_rand(1,9).strrev($x);
	$x = str_pad($x,21,'0',STR_PAD_RIGHT);
	return $x;
}

function _randnum28(){
	$r = hexdec(uniqid('',true));
	$a = str_split($r,2);
	$a[0] = mt_rand(1,9).$a[0];
	$a[1] = mt_rand(0,9).$a[1];
	$a[2] = mt_rand(1,9).$a[2];
	$a[3] = mt_rand(0,9).$a[3];
	$a[4] = mt_rand(1,9).$a[4];
	$a[5] = mt_rand(0,9).$a[5];
	$a[6] = mt_rand(1,9).$a[4];
	$a[7] = mt_rand(0,9).$a[3];
	$x = implode('',$a);
	$x = str_replace(array('E+','.'),'',$x);
	$x = mt_rand(1,9).strrev($x);
	$x = str_pad($x,28,'0',STR_PAD_RIGHT);
	return $x;
}

function _parseEmailTpl($buf,array $vars=[]){
	foreach($vars as $k=>$v){
		$buf = preg_replace("/{{[ ]*{$k}[ ]*}}/i",$v,$buf);
	}
	$buf = nl2br($buf);
	return $buf;
}

function _parseEmailName($string){
	$array = explode(':',$string);
	if(count($array)===2){
		$string = $array[0];
		$y = $array[1];
	}else{
		$y = $array[0];
	}
	return array($string,$y);
}

function _queueEmail($from,$to,$subject,$body,array $headers=array()){
	$data = array();
	$data['time_created'] = time();
	$data['ip_created'] = ip2long($_SERVER['REMOTE_ADDR']);

	list($from_email,$from_name) = _parseEmailName($from);
	list($to_email,$to_name) = _parseEmailName($to);

	$data['sender_email'] = $from_email;
	$data['sender_name'] = $from_name;
	$data['receiver_email'] = $to_email;
	$data['receiver_name'] = $to_name;
	$data['subject'] = $subject;
	$data['body'] = $body;
	$data['headers'] = json_encode($headers);
	$res = db::insert('email_queue',$data);
	return $res;
}

function _destroySession(){
	session_unset();
	session_destroy();
	if(session_status() == PHP_SESSION_ACTIVE){
		session_regenerate_id(true);
	}
}

function _timetostr($ts,$show_hours=true){
	if(!ctype_digit($ts)) {
		$ts = strtotime($ts);
	}
	$hours = '';
	if($show_hours){
		$hours = $_SERVER['_TIME_AT'] .' '. strftime('%R',$ts);
	}
	$diff = time() - $ts;
	if($diff == 0) {
		return 'now';
	} elseif($diff > 0) {
		$day_diff = floor($diff / 86400);
		if($day_diff == 0) {
			return $_SERVER['_TIME_TODAY'].' '. $hours;
		}
		if($day_diff == 1) {
			return $_SERVER['_TIME_YESTERDAY'].' '. $hours;
		}
		if($day_diff > 1){
			$now_n = date('N');
			$ts_n = date('N',$ts);
			if($now_n > $ts_n){
				return ucfirst(strftime('%A',$ts)).' '. $hours;
			}
		}
	}
	if(date('Y') !== date('Y',$ts)){
		// printing a different year
		$res = strftime('%B %e, %Y',$ts).' '. $hours;
	}else{
		$res = strftime('%B %e',$ts).' '. $hours;
	}

	return ucfirst($res);
}

function _rewriteString($string,$limit=0){
	$len = mb_strlen($string);
	if($limit){
		$len = $len>$limit?$limit:$len;
	}

	$pos = mb_strpos($string,' ',$len);
	if($pos===false){$pos=$len;}
	$res = mb_substr($string,0,$pos);
	$res = preg_replace('/[^a-z0-9]+/i','-',$res);
	$res = str_replace('---','-',$res);
	$res = str_replace('--','-',$res);
	$res = trim($res,'-');
	$res = mb_strtolower($res);
	return $res;
}

function _intersect(array $array,array $keys){
	$x = array();
	foreach($keys as $key){
		$x[$key] = 1;
	}
	$res = array_intersect_key($array,$x);
	return $res;
}

function _varfmt($str,array $params=array()){
	foreach($params as $k=>$v){
		$str = str_replace("{{{$k}}}",$v,$str);
	}
	return $str;
}

function _passMoreThan(&$ts,$sec){
	$time = time();
	if(($time-$ts) > $sec){
		$ts = $time;
		return true;
	}
	return false;
}

function _passLessThan(&$ts,$sec){
	$time = time();
	if(($time-$ts) < $sec){
		return true;
	}
	$ts = $time;
	return false;
}