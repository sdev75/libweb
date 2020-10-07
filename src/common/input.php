<?php

class Input {

	public $_data;

	public function __construct(&$array){
		$this->_data = $array;
	}

	public function merge($array){
		if(!empty($array)){
			$this->_data += $array;
		}
	}

	public function __get($name){
		if(isset($this->_data[$name])){
			return $this->_data[$name];
		}
		return null;
	}

	public function __isset($k) {
		return isset($this->_data[$k]);
	}
}