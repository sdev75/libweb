<?php

class db {

	public static $pdo;
	public static $sth;
	public static $query;
	public static $params = [];

	public static function init($dsn,$user,$pass){
		db::$pdo = new PDO($dsn,$user,$pass,[
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::MYSQL_ATTR_LOCAL_INFILE => 1,
		]);
	}

	public static function close(){
		if(self::$pdo && self::$pdo->inTransaction()){
			self::$pdo->rollback();
		}
		self::$pdo = null;
		self::$sth = null;
		self::$query = null;
		self::$params = null;
	}

	// RETURNS ROW AFFECTED (USEFUL FOR DELETE;UPDATE)
	public static function exec($query){
		self::$query = $query;
		return self::$pdo->exec($query); 
	}

	public static function insertId(){
		return self::$pdo->lastInsertId();
	}

	public static function beginTransaction(){
		self::$pdo->beginTransaction();
	}
	
	public static function begin(){
		self::$pdo->beginTransaction();
	}

	public static function inTransaction(){
		return self::$pdo->inTransaction();
	}

	public static function commit(){
		self::$pdo->commit();
	}

	public static function rollback(){
		self::$pdo->rollBack();
	}

	public static function prepare($query){
		self::$sth = self::$pdo->prepare($query);
		return self::$sth;
	}

	public static function execute(array $params=null){
		self::$query = self::$sth->queryString;
		$res = self::$sth->execute($params);
		return $res;
	}

	public static function query($query){
		self::$sth = self::$pdo->prepare($query);
		self::$query = self::$sth->queryString;
		$res = self::$sth->execute(self::$params);
		self::$params = array();
		return $res;
	}

	public static function fetch($fetch_style=PDO::FETCH_ASSOC){
		return self::$sth->fetch($fetch_style);
	}

	public static function fetchAll($fetch_style=PDO::FETCH_ASSOC){
		return self::$sth->fetchAll($fetch_style);
	}

	public static function rowCount(){
		return self::$sth->rowCount();
	}

	public static function errorInfo(){
		return self::$sth->errorInfo();
	}

	public static function selectFoundRows(){
		$sth = self::$pdo->prepare('SELECT FOUND_ROWS() AS n');
		$sth->execute();
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		return isset($row['n'])?(int)$row['n']:false;
	}

	public static function insert($table,array $data){
		$binds = array();
		$query = "INSERT INTO `{$table}` SET ";

		$pair = '';
		foreach($data as $k=>$v){
			$pair .= "{$k}=:{$k},";
			$binds[":$k"]=$v;
		}
		$query .= rtrim($pair,',');

		$sth = self::$pdo->prepare($query);
		self::$query = $sth->queryString;
		$sth->execute($binds);
		if($sth && $sth->rowCount()){
			$insert_id = self::$pdo->lastInsertId();
			return $insert_id;
		}

		return false;
	}

	// insert ignore will not throw exception on DUPLICATE KEY
	// Error: 1022 SQLSTATE: 23000 (ER_DUP_KEY)
	public static function insertIgnore($table,array $data){
		$binds = array();
		$query = "INSERT IGNORE INTO `{$table}` SET ";

		$pair = '';
		foreach($data as $k=>$v){
			$pair .= "{$k}=:{$k},";
			$binds[":$k"]=$v;
		}
		$query .= rtrim($pair,',');

		$sth = self::$pdo->prepare($query);
		self::$query = $sth->queryString;
		$sth->execute($binds);
		if($sth && $sth->rowCount()){
			$insert_id = self::$pdo->lastInsertId();
			return $insert_id;
		}

		return false;
	}

	public static function update($table,$pk,$pk_val,array $data){
		$binds = array(':pk'=>$pk_val);
		$query = "UPDATE `{$table}` SET ";

		$pair = '';
		foreach($data as $k=>$v){
			$pair .= "{$k}=:{$k},";
			$binds[":$k"]=$v;
		}
		$query .= rtrim($pair,',');

		$query .= " WHERE `{$pk}`=:pk LIMIT 1";

		$sth = self::$pdo->prepare($query);
		self::$query = $sth->queryString;
		return $sth->execute($binds);
	}

	public static function updateIn($table,$in_key,$in_value,array $data){
		$binds = [];
		$query = "UPDATE `{$table}` SET ";

		$pair = '';
		foreach($data as $k=>$v){
			$pair .= "{$k}=:{$k},";
			$binds[":$k"]=$v;
		}
		$query .= rtrim($pair,',');

		$query .= " WHERE `$in_key` IN ($in_value)";

		$sth = self::$pdo->prepare($query);
		self::$query = $sth->queryString;
		return $sth->execute($binds);
	}

	public static function replace($table,array $data){
		$query = "REPLACE INTO `{$table}` SET ";
		$binds = [];
		$pair = '';
		foreach($data as $k=>$v){
			$pair .= "{$k}=:{$k},";
			$binds[":$k"]=$v;
		}
		$query .= rtrim($pair,',');

		$sth = self::$pdo->prepare($query);
		self::$query = $sth->queryString;
		return $sth->execute($binds);
	}

	public static function select($table,$by,$value,$limit=1){
		$limit = abs($limit);

		$sth = self::$pdo->prepare("
			SELECT * FROM `{$table}`
			WHERE `{$by}` = ?
			LIMIT $limit
		");
		self::$query = $sth->queryString;
		$sth->execute(array($value));
		if($limit>1){
			return $sth->fetchAll(PDO::FETCH_ASSOC);
		}

		return $sth->fetch(PDO::FETCH_ASSOC);
	}

	public static function selectOne($query){
		$sth = self::$pdo->prepare($query);
		self::$query = $sth->queryString;
		$sth->execute(self::$params);
		$res = $sth->fetch(PDO::FETCH_ASSOC);
		self::$params = array();
		return $res;
	}

	public static function selectAll($query){
		$sth = self::$pdo->prepare($query);
		self::$query = $sth->queryString;
		$sth->execute(self::$params);
		$res = $sth->fetchAll(PDO::FETCH_ASSOC);
		self::$params = array();
		return $res;
	}

	public static function delete($table,$by,$value,$limit=null){
		$limitstr = '';
		if($limit){
			$limit = abs($limit);
			$limitstr = "LIMIT {$limit}";
		}
		$sth = self::$pdo->prepare("
			DELETE
			FROM `{$table}`
			WHERE {$by}=?
			{$limitstr}
		");
		self::$query = $sth->queryString;
		$sth->execute(array($value));
		return $sth->rowCount();
	}

	public static function bindPairs($data){
		$pair = '';
		foreach($data as $k=>$v){
			$pair .= "{$k}=:{$k},";
			self::$params[":$k"]=$v;
		}
		$res = rtrim($pair,',');
		return $res;
	}
	
	public static function insertOrUpdate($table,array $data1,array $data2){
		$binds = [];
		
		$pair1 = '';
		foreach($data1 as $k=>$v){
			$pair1 .= "{$k}1=:{$k}1,";
			$binds["{$k}1"]=$v;
		}
		$pair1= rtrim($pair1,',');

		$pair2 = '';
		foreach($data2 as $k=>$v){
			$pair2 .= "{$k}2=:{$k}2,";
			$binds["{$k}2"]=$v;
		}
		$pair2 = rtrim($pair1,',');


		$q = "INSERT INTO `{$table}` SET $pair1 ON DUPLICATE KEY SET $pair2";
		$sth = self::$pdo->prepare($q);
		self::$query = $sth->queryString;
		$sth->execute($binds);
		if($sth && $sth->rowCount()){
			$res = self::$pdo->lastInsertId();
			return $res;
		}

		return false;
	}

	//insert into table (fielda, fieldb, ... ) values (?,?...), (?,?...)....
	public static function insertMany($table,$fields,$data){
		$binds = [];
		$query = "INSERT INTO `{$table}` (" . implode(",",$fields) . ") VALUES ";
		foreach($data as $array){
			$pairs = [];
			foreach($array as $v){
				$pairs[]='?';
				$binds[]=$v;
			}
			$query .= '('.implode(',',$pairs).'),';
		}
		$query = rtrim($query,',');
		$sth = self::$pdo->prepare($query);
		self::$query = $sth->queryString;
		$sth->execute($binds);
		if($sth && $sth->rowCount()){
			return (int)$sth->rowCount();
		}

		return false;
	}
}