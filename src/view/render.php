<?php

class ViewScope{
	public $_vars = array();

	public function __get($k){
		if(isset($this->_vars[$k])){
			return $this->_vars[$k];
		}
		return null;
	}

	public function __set($k,$v){
		$this->_vars[$k] = $v;
	}

	public function __isset($k){
		return isset($this->_vars[$k]);
	}

	public function render($filename){
		include $filename;
	}
}