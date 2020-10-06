<?php

class worker{
	public static $id;
	public static $name;
	public static $code;
	public static $data;
	public static $locked = 0;
	public static $lock = 0;
	
	public static $batch_id =0;
	public static $item_id =0;
	
	// if $db is set, it will attempt to get worker data from DB
	public static function init($code,$lock=1){
		db::$params = [$code];
		$array = db::selectOne("
		SELECT 
			worker_id,
			status,
			name,
			code,
			sig,
			locked,
			grep,
			cmd 
		FROM 
			worker 
		WHERE 
			code = ? 
		LIMIT 1
		");
		if(!$array){
			throw new Exception("worker does not exist in db");
		}

		$worker_id = (int) $array['worker_id'];
		$lock = (int) $lock;

		self::$lock = $lock;
		self::$id = $worker_id;
		self::$code = $array['code'];
		self::$name = $array['name'];
		self::$data = $array;
		
		if((int)$array['locked']){
			if(self::getPid()){
				throw new Exception("worker already running");
			}
		}
		
		db::exec("UPDATE worker SET sig=NULL,locked=$lock,status='running' WHERE worker_id = $worker_id LIMIT 1");
		register_shutdown_function(array('worker','shutdown'));
	}
	
	public static function unlock(){
		$code = db::quote(self::$code);
		db::exec("UPDATE worker SET locked=0 WHERE code = $code LIMIT 1");
		self::$locked = 0;
	}

	public static function shutdown(){
		$code = db::quote(self::$code);
		db::exec("UPDATE worker SET sig=NULL,locked=0,status='stopped' WHERE code = $code LIMIT 1");
	}
	
	public static function tick(){
		db::$params = [self::$code];
		$array = db::selectOne("SELECT sig FROM worker WHERE code = ? LIMIT 1");
		if($array && $array['sig'] === 'term'){
			exit;
		}
	}
	
	public static function getPid(){
		$arg = escapeshellarg(self::$data['grep']);
		$cmd = "ps aux | grep $arg | awk '{print $2}'";
		exec($cmd,$out);
		if(!isset($out[0])){
			return 0;
		}
		return (int) $out[0];
	}

	//'emergency','alert','critical','error','warning','notice','info','debug'
	public static function log($msg,$severity='debug'){
		$worker_id = self::$id;
		if(!$worker_id){
			return;
		}
		$log_id = db::insert('worker_log',[
			'time_created' => time(),
			'worker_id' => $worker_id,
			'severity' => $severity,
			'msg' => $msg,
			'batch_id' => self::$batch_id,
			'item_id' => self::$item_id,
		]);
		return $log_id;
	}
}

class ts {
	public static $data;

	public static function reset($name,$val=0){
		self::$data[$name]=$val;
	}

	public static function passMoreThan($name,$s){
		return (bool) ((time()-self::$data[$name]) > $s);
	}

	public static function passLessThan($name,$s){
		return (bool) ((time()-self::$data[$name]) < $s);
	}
}