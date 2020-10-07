<?php

class DbSearch{

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
