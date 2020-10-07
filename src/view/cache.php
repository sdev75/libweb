<?php

class ViewCache {
	public $lang;
	public $layout;
	public $script;
	public $tags;

	public $layout_filename;
	public $script_filename;

	public $hash;
	public $expired;
	public $path;
	public $filename;
	public $time_parsed =0;

	public function init($layout,$script,$lang,$tags=[]){

		$tags []= $_SERVER['REQUEST_SCHEME'];
		$tags []= _URI;
		$this->tags = implode(',',$tags);
		$this->lang = $lang;
		$this->layout = $layout;
		$this->script = $script;
		
		$this->layout_filename = "{{ PATH_VIEW }}/layout/{$layout}.php";
		$this->script_filename = "{{ PATH_VIEW }}/script/{$script}.php";
		
		$tmp = implode('',[$this->layout_filename,$this->script_filename,$this->lang,$this->tags]);
		$this->hash = hash('md4',$tmp);

		$relpath = rtrim(chunk_split($this->hash,3,'/'),'/');

		$this->path = "{{ PATH_CACHE_VIEW }}/{$relpath}";
		$this->filename = "{$this->path}/{$this->hash}.php";
		
		if(!file_exists($this->filename)){
			$this->expired = true;
		}else{
			$this->expired = false;
		}
	}
}