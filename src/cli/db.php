<?php

class db {

	public static $pdo;
	public static $sth;
	public static $query;
	public static $error;
	public static $params = array();

	public static function init($host,$name,$user,$pass){
		$dsn = "mysql:host=$host;dbname=$name;charset=utf8";
		self::$pdo = new PDO($dsn,$user,$pass,[
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
	}

	public static function exec($query){
		self::$query = $query;
		return self::$pdo->exec($query); // RETURNS ROW AFFECTED (USEFUL FOR DELETE;UPDATE)
	}

	public static function lastInsertId(){
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
		$binds = array();
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
		$binds = array();
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

	public static function select2($table,$by,$value){
		$sth = self::$pdo->prepare("
			SELECT * FROM `{$table}`
			WHERE `{$by}` = ?
			LIMIT 1
		");
		self::$query = $sth->queryString;
		$sth->execute(array($value));

		$row = new SkDatabaseRow($table,$by,$value);
		$row->assign($sth->fetch(PDO::FETCH_ASSOC));
		return $row;
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

class SkDatabaseRow implements ArrayAccess { // ALPHA EXPERIMENTAL NOT DONE!
	private $table;
	private $pk;
	private $pk_val;
	private $data = array();
	private $new_data = array();

	public function __construct($table,$pk,$pk_val) {
		$this->data = array();
		$this->table = $table;
		$this->pk = $pk;
		$this->pk_val = $pk_val;
	}

	public function assign($data){
		$this->data = $data;
	}

	public function offsetSet($offset, $value) {
		if(is_null($offset)){
			$this->data[] = $value;
		}else{
			$this->data[$offset] = $value;
			$this->new_data[$offset] = $value;
		}
	}

	public function offsetExists($offset) {
		return isset($this->data[$offset]);
	}

	public function offsetUnset($offset) {
		unset($this->data[$offset]);
	}

	public function offsetGet($offset){
		return isset($this->data[$offset]) ? $this->data[$offset] : null;
	}

	public function save(){
		$array = $this->new_data;
		unset($array[$this->pk]);
		$res = db::update($this->table,$this->pk,$this->pk_val,$array);
		return $res;
	}
}

class SkDatabaseSearch{

	public $query;
	public $where = array();
	public $joins = array();
	public $binds = array();
	public $group_by = '';
	public $order_by = '';
	public $limit_start;
	public $limit_end;
	public $limit;
	public $page;
	public $items_per_page;
	public $pagination;

	function __construct($pagination=false){
		if($pagination){
			$this->setPagination($pagination);
		}
	}

	public function setPagination(SkPagination $pagination){
		$this->page = $pagination->page;
		$this->items_per_page = $pagination->items_per_page;
		$this->pagination = $pagination;
	}

	public function setLimit($start,$end=null){
		$this->limit_start = $start;
		$this->limit_end= $end;
	}

	public function setOrderBy($value){
		$this->order_by = $value;
	}

	public function setGroupBy($value){
		$this->group_by = $value;
	}

	public function addLeftJoin($value){
		$this->joins[] = 'LEFT JOIN '.$value;
	}

	public function addJoin($value,$type='JOIN'){
		$this->joins[] = $type.' '.$value;
	}

	public function addWhereValue($value){
		$this->where[] = "{$value}";
	}

	public function addWhereLike($fieldname,$value){
		if(empty($value)){
			return;
		}
		$value = str_replace('*','%',$value);
		$this->addWhere($fieldname,$value,'LIKE');
	}

	public function addWhereAutoLike($fieldname,$value){
		if(empty($value)){
			return;
		}
		$value = str_replace('*','%',$value);
		$this->addWhere($fieldname,"%$value%",'LIKE');
	}

	public function addWhere($fieldname,$value,$operator='=',$suffix=''){
		if(is_null($value) || (is_string($value) && empty($value))){
			return;
		}

		$array = explode('.',$fieldname);
		if(count($array)>1){
			$bindname = "{$array[0]}_{$array[1]}";
			$bind = ":{$bindname}{$suffix}";
		}else{
			$bind = ":{$fieldname}{$suffix}";
		}

		$this->where[] = "({$fieldname} {$operator} {$bind})";
		$this->binds[$bind] = $value;
	}

	public function addWhereNum($fieldname,$value,$operator='=',$suffix=''){
		if(is_null($value)){
			return;
		}
		// force value to be a number
		$value = $value + 0;

		$array = explode('.',$fieldname);
		if(count($array)>1){
			$bindname = "{$array[0]}_{$array[1]}";
			$bind = ":{$bindname}{$suffix}";
		}else{
			$bind = ":{$fieldname}{$suffix}";
		}

		$this->where[] = "({$fieldname} {$operator} {$bind})";
		$this->binds[$bind] = $value;
	}

	public function getWhereCond(array $params){
		$array = array();
		$last_cond = '';
		foreach($params as $param){
			if($param[1] === '%%'){
				continue;
			}

			if(!isset($param[4])){
				$param[4] = '';
			}

			$last_cond = $param[3];
			$where = $this->setWhereCond($param[0],$param[1],$param[2],$param[3],$param[4]);
			if(!$where){
				continue;
			}
			$array []= $where;
		}

		$len = strlen($last_cond)+1;
		$string = implode(' ',$array);
		$string = substr($string,0,strlen($string)-$len);
		$string = trim($string);
		return $string;
	}

	private function setWhereCond($fieldname,$value,$operator='=',$cond='AND',$suffix=''){
		$test = str_replace('%','',$value);
		if(is_null($test) || (is_string($test) && empty($test))){
			return false;
		}

		$array = explode('.',$fieldname);
		if(count($array)>1){
			$bindname = "{$array[0]}_{$array[1]}";
			$bind = ":{$bindname}{$suffix}";
		}else{
			$bind = ":{$fieldname}{$suffix}";
		}

		$this->binds[$bind] = $value;
		$res = "{$fieldname} {$operator} {$bind} {$cond}";
		return $res;
	}

	public function addWhereCond(array $params){
		$array = array();
		$last_cond = '';
		foreach($params as $param){
			if($param[1] === '%%'){
				continue;
			}

			if(!isset($param[4])){
				$param[4] = '';
			}

			$last_cond = $param[3];
			$where = $this->setWhereCond($param[0],$param[1],$param[2],$param[3],$param[4]);
			if(!$where){
				continue;
			}
			$array []= $where;
		}

		$len = strlen($last_cond)+1;
		$string = implode(' ',$array);
		$string = substr($string,0,strlen($string)-$len);
		$string = trim($string);
		if(!empty($string)){
			$this->where[] = "($string)";
		}
	}

	public function addBind($name,$value){
		$key = ":{$name}";
		$this->binds[$key] = $value;
	}

	public function getQueryString(){

		$res = $this->query;

		if(!empty($this->joins)){
			$res .= ' ' . implode(' ',$this->joins);
		}

		if(!empty($this->where)){
			$res .= ' WHERE ' . implode(' AND ',$this->where);
		}

		if(!empty($this->group_by)){
			$res .= ' GROUP BY ' . $this->group_by;
		}

		if(!empty($this->order_by)){
			$res .= ' ORDER BY ' . $this->order_by;
		}

		if($this->items_per_page && $this->page){
			$offset = ($this->page - 1) * $this->items_per_page;
			$res .= " LIMIT {$offset}, {$this->items_per_page}";
		}elseif($this->limit_start){
			if($this->limit_end){
				$res .= " LIMIT {$this->limit_start}, {$this->limit_end} ";
			}else{
				$res .= " LIMIT {$this->limit_start} ";
			}
		}

		return $res;
	}

	public function getPagination($uri,array $array,$found_rows){

		$this->pagination->setItemsCount(count($array));
		$this->pagination->setTotalItems($found_rows);
		if($pages = $this->pagination->getPagesArray()){
			foreach($pages as $name){
				$this->pagination->pages[] = array(
					'link' => $uri._query(array('page'=>$name),true),
					'label' => (string) $name,
					'active'=> (bool) ((int)$this->pagination->page == (int)$name),
				);
			}
			if($this->pagination->page > 1){
				$this->pagination->page_prev = $uri._query(array('page'=>$this->pagination->page-1),true);
			}
			if($this->pagination->page < $this->pagination->total_pages){
				$this->pagination->page_next = $uri._query(array('page'=>$this->pagination->page+1),true);
			}
			$this->pagination->page_first = $uri._query(array('page'=>1),true);
			$this->pagination->page_last = $uri._query(array('page'=>$this->pagination->total_pages),true);
		}

		return $this->pagination;
	}

}

class SkPagination{

	public $pages = array();
	public $max_items;
	public $max_pages;
	public $items_per_page;

	public $total_items = 0;
	public $total_pages = 1;
	public $page = 1;
	public $items_count = 0;
	public $page_prev = false;
	public $page_next = false;
	public $page_first = false;
	public $page_last = false;

	function __construct($items_per_page=false,$current_page=false){
		if($items_per_page){
			$this->setItemsPerPage($items_per_page);
		}
		if($current_page){
			$this->setCurrentPage($current_page);
		}
	}

	public function setTotalItems($value){
		$this->total_items = abs($value);
		$this->total_pages = $this->getTotalPages();
	}

	public function setItemsCount($value){
		$this->items_count = $value;
	}

	public function setMaxItems($value){
		$this->max_items = abs($value);
	}

	public function setMaxPages($value){
		$this->max_pages = abs($value);
	}

	public function setItemsPerPage($value){
		$this->items_per_page = $value;
	}

	public function setCurrentPage($page){
		$page = abs($page);
		if($page < 1) {
			$page = 1;
		}
		if($this->max_pages){
			if($this->max_pages < $page){
				$page = $this->max_pages;
			}
		}
		$this->page = $page;
	}

	public function getTotalItems(){
		return $this->total_items;
	}

	public function getTotalPages(){
		if(isset($this->max_pages)) {
			if($this->total_items <= $this->max_pages * $this->items_per_page) {
				$res = round(ceil($this->total_items / $this->items_per_page));
			} else {
				$res = round(ceil($this->max_pages*$this->items_per_page / $this->items_per_page));
			}
		} else {
			$res = round(ceil($this->total_items / $this->items_per_page));
		}

		if($res <= 0) {
			$res = 1;
		}

		return $res;
	}

	public function getPagesArray(){

		$adjacents = 1;
		$lastpage = $this->total_pages;

		$lpm1 = $lastpage - 1; //last page minus 1

		$res = array();

		if($lastpage > 1) {
			//pages
			if ($lastpage < 7 + ($adjacents * 2))	//not enough pages to bother breaking it up
			{
				for ($counter = 1; $counter <= $lastpage; $counter++)
				{
					$res[] = $counter;
				}
			}
			elseif($lastpage >= 7 + ($adjacents * 2))	//enough pages to hide some
			{
				//close to beginning; only hide later pages
				if($this->page < 1 + ($adjacents * 3))
				{
					for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++)
					{
						$res[] = $counter;
					}
					$res[] = '...';
					$res[] = $lpm1;
					$res[] = $lastpage;
				}
				//in middle; hide some front and some back
				elseif($lastpage - ($adjacents * 2) > $this->page && $this->page > ($adjacents * 2))
				{
					$res[] = 1;
					$res[] = 2;
					$res[] = '...';
					for ($counter = $this->page - $adjacents; $counter <= $this->page + $adjacents; $counter++)
					{
						$res[] = $counter;
					}

					$res[] = '...';
					$res[] = $lpm1;
					$res[] = $lastpage;
				}
				//close to end; only hide early pages
				else
				{
					$res[] = 1;
					$res[] = 2;
					$res[] = '...';
					for ($counter = $lastpage - (1 + ($adjacents * 3)); $counter <= $lastpage; $counter++)
					{
						$res[] = $counter;
					}
				}
			}
		}

		return $res;
	}

}