<?php

class RedisProxy {
	public $data;
	
	public function get($k){
		return $this->data[$k] ?? null;
	}

	public function mget(array $keys){
		$res = [];
		foreach($keys as $k){
			$res []= $this->data[$k] ?? null;
		}
		return $res;
	}

	public function exists($k){
		return isset($this->data[$k]);
	}

	public function delete($k){
		unset($this->data[$k]);
	}

	public function setex($k,$ttl=0,$v){
		$this->data[$k] = $v;
	}
}

class cache{
	public static $redis;
	public static function init(string $prefix){
		$init = false;
		if(class_exists('Redis')){
			$redis = new Redis();
			if($redis->connect('127.0.0.1',6379)){
				$redis->setOption(Redis::OPT_SERIALIZER,Redis::SERIALIZER_PHP);
				$redis->setOption(Redis::OPT_PREFIX,"{$prefix}:");
				self::$redis = $redis;
				$init = true;
			}else{
				error_log("Unable to connect to redis server/socket");
			}
		}
		if(!$init){
			error_log("Redis init error! Using RedisProxy");
			self::$redis = new RedisProxy();
			return false;
		}
		return true;
	}
	
	public static function get(string $k){
		$res = self::$redis->get($k);
		return $res;
	}
	
	public static function mget(array $k){
		$res = self::$redis->mget($k);
		return $res;
	}

	public static function set(string $k, $v, int $ttl): bool{
		$res = (bool) self::$redis->setex($k,$ttl,$v);
		return $res;
	}
	
	public static function del(string $k): int{
		$res = (int) self::$redis->delete($k);
		return $res;
	}

	public static function exists(string $k): bool{
		$res = (bool) self::$redis->exists($k);
		return $res;
	}
}