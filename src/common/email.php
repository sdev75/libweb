<?php

function _parse_email_tpl($buf,array $vars=[]){
	foreach($vars as $k=>$v){
		$buf = preg_replace("/{{[ ]*{$k}[ ]*}}/i",$v,$buf);
	}
	$buf = nl2br($buf);
	return $buf;
}

function _parse_email_name($string){
	$array = explode(':',$string);
	if(count($array)===2){
		$string = $array[0];
		$y = $array[1];
	}else{
		$y = $array[0];
	}
	return array($string,$y);
}

function _queue_email($from,$to,$subject,$body,array $headers=array()){
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