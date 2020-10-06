<?php

class Bread {
	public static $show_home = false;
	public static $data = array(
		//'Home'=>array('label'=>'Home','link'=>''),
	);

	public static function get(){
		if(self::$show_home){
			array_unshift(self::$data,array('label'=>'Home','link'=>''));
		}
		return self::$data;
	}

	public static function getHtml(){
		if(self::$show_home){
			array_unshift(self::$data,array('label'=>'Home','link'=>''));
		}
		$res = "<nav aria-label=\"breadcrumb\"><ol class=\"breadcrumb\">";
		foreach(self::$data as $item){
			$res.= "<li class=\"breadcrumb-item\"><a href=\"{$item['link']}\">{$item['label']}</a></li>";
		}
		$res.= "</ol></nav>";
		return $res;
	}

	public static function add($label,$link){
		self::$data[$label] = array('label'=>$label,'link'=>$link);
	}
}