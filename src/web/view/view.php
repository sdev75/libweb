<?php

class View {
	
	public static $vars = [];
	public static $lang;
	public static $route;
	public static $layout;
	public static $script;
	
	public static $title = '';
	public static $subtitle = '';
	
	public static $tags = [];

	public static $stylesheets = [];
	public static $javascripts = [];
	
	public static $cache;
	public static $includes = [];
	
	public static $redis;
	public static $cached_files = [];

	public static function addJs($href,$type='base',$async=true,$defer=true){
		if(!isset(self::$javascripts[$type])){
			self::$javascripts[$type] = [];
		}
		self::$javascripts[$type][] = [$href,$async,$defer];
	}

	public static function addCss($href,$type='base',$media=''){
		if(!isset(self::$stylesheets[$type])){
			self::$stylesheets[$type] = [];
		}
		self::$stylesheets[$type][] = [$href,$media];
	}
	
	public static function getJs(){
		$res = [];
		foreach(self::$javascripts as $type => $items){
			$res[$type] = "";
			foreach($items as $array){
				$href = $array[0];
				$async = $array[1];
				$defer = $array[2];
				
				$x = str_replace('asset/js','',$href);
				$filename ="{{ ASSET_JS_PATH }}/$x.js";
				$filetime = filemtime($filename);
				$href .= ".$filetime.js";
				
				$async = $async?' async ':' ';
				$defer = $defer?' defer ':' ';
				
				$res[$type] .= "<script{$async}{$defer} src=\"$href\"></script>";
			}
		}
		return $res;
	}

	public static function getCss(){
		$res = [];
		foreach(self::$stylesheets as $type => $items){
			$res[$type] = "";
			foreach($items as $data){
				$href = $data[0];
				$media = $data[1];
				$x = str_replace('asset/css','',$href);
				$filename ="{{ PATH_ASSET_CSS }}/$x.css";
				$filetime = filemtime($filename);
				$href .= ".$filetime.css";
				if(!empty($media)){
					$media = " media=\"$media\"";
				}
				$res[$type] .= "<link async href=\"$href\" rel=\"stylesheet\"{$media}>";
			}
		}
		return $res;
	}
	
	public static function getScriptElem($href,$async=true,$defer=true){
		$x = str_replace('asset/js','',$href);
		$filename ="{{ PATH_ASSET_JS }}/$x.js";
		$filetime = filemtime($filename);
		$href .= ".$filetime.js";
		if($async){
			$async = ' async ';
		}else{
			$async = ' ';
		}
		if($defer){
			$defer = ' defer ';
		}else{
			$defer = ' ';
		}
		$res = "<script{$async}{$defer}src=\"$href\"></script>";
		return $res;
	}

	public static function getAltLangLinks($route,$path){
		$langs = $route['langs'];
		if(count($langs) === 1){
			return '';
		}

		// if accessing /amp url and ROUTE has amp enabled
		// BUG fixed: amp has own entrypoint, uri path already has /amp at the end
		if(_AMP && $route['amp']){
			//$path .= '/amp';
		}
		$path = str_replace('//','/',$path);
		
		$res = [];
		$href = _BASEURL.$path;
		$res []= "<link rel=\"alternate\" hreflang=\"x-default\" href=\"$href\">";
		$res []= "<link rel=\"alternate\" hreflang=\"en\" href=\"$href\">";
		foreach($langs as $lang){
			if('{{ LANG_DEF }}' === $lang){
				continue;
			}
			$href = rtrim(_BASEURL."{$lang}/{$path}",'/');
			$res []= "<link rel=\"alternate\" hreflang=\"{$lang}\" href=\"{$href}\">";
		}
		if(count($res) === 2){
			return '';
		}
		return implode('',$res);
	}

	public static function getAltLangLinksForAmp($route,$path){
		$langs = $route['langs'];
		if(count($langs) === 1){
			return '';
		}
		// _BASEURL = https://www.example.com/
		// $lang = en , es , fr
		// $path  = '' , country/italy , about
		$res = [];
		$x = trim($path.'/amp','/');
		$href = _BASEURL.$x;
		$res []= "<link rel=\"alternate\" hreflang=\"x-default\" href=\"$href\">";
		$res []= "<link rel=\"alternate\" hreflang=\"en\" href=\"$href\">";
		foreach($langs as $lang){
			if('en' === $lang){
				continue;
			}
			$x = ltrim("/{$path}/amp",'/');
			$href = _BASEURL.$lang.'/'.$x;
			$res []= "<link rel=\"alternate\" hreflang=\"{$lang}\" href=\"{$href}\">";
		}
		if(count($res) === 2){
			return '';
		}
		return implode('',$res);
	}

	// ['site'=>'companyllc','creator'=>'companyllc','title'=>'','desc'=>,'image'=>'']
	public static function getTwitterCardHtml(array $array){
		$res = '';
		$title = htmlentities($array['title'],ENT_QUOTES);
		$desc = htmlentities($array['desc'],ENT_QUOTES);
		
		$res .= "<meta name=\"twitter:card\" content=\"summary\">";
		$res .= "<meta name=\"twitter:site\" content=\"@{$array['site']}\">";
		$res .= "<meta name=\"twitter:creator\" content=\"@{$array['creator']}\">";
		$res .= "<meta name=\"twitter:title\" content=\"{$title}\">";
		$res .= "<meta name=\"twitter:description\" content=\"{$desc}\">";
		$res .= "<meta name=\"twitter:image\" content=\"{$array['image']}\">";
		return $res;
	}
	
	// ['title'=>'','desc'=>,'url'=>'CANONICAL_URL_HERE','site_name'=>'Site Name','image'=>'','image:width'=>'640','image:height'=>'480']
	public static function getOpenGraphHtml(array $array){
		$res = '';
		$title = htmlentities($array['title'],ENT_QUOTES);
		$desc = htmlentities($array['desc'],ENT_QUOTES);
		$res .= "<meta property=\"og:type\" content=\"product\">";
		$res .= "<meta property=\"og:title\" content=\"{$title}\">";
		$res .= "<meta property=\"og:description\" content=\"{$desc}\">";
		$res .= "<meta property=\"og:url\" content=\"{$array['url']}\">";
		$res .= "<meta property=\"og:site_name\" content=\"{$array['site_name']}\">";
		$res .= "<meta property=\"og:image\" content=\"{$array['image']}\">";
		$res .= "<meta property=\"og:image:width\" content=\"{$array['image:width']}\">";
		$res .= "<meta property=\"og:image:height\" content=\"{$array['image:height']}\">";
		return $res;
	}
	
	private static function combine(){
		$cache = &self::$cache;
		$c = new ViewCombiner();
		$layout_buf = $c->parse($cache->layout_filename);
		self::$includes = $c->includes;
		$script_buf = $c->parse($cache->script_filename);
		self::$includes += $c->includes;
		$buf = str_replace("{% include script %}",$script_buf,$layout_buf);
		unset($layout_buf,$script_buf,$c);
		return $buf;
	}
	
	public static function parseblock($path,$lang,array $vars){
		$p = new ViewParser($lang,$vars);
		$buf = file_get_contents("{{ PATH_VIEW }}/block/{$path}.php");
		$res = $p->parse($buf);
		return $res;
	}
	
	private static function parse($buf,$lang,array $vars){
		$cache = &self::$cache;
		
		if(!is_dir($cache->path)){
			if(!mkdir($cache->path,0775,true)){
				$writable = (int) is_writable($cache->path);
				$msg = "Render - Unable to create dir: {$cache->path} - writable: $writable";
				throw new Exception($msg);
			}
		}

		$p = new ViewParser($lang,$vars);
		$_bb=microtime(1);
		$buf = $p->parse($buf);
		$_ee=microtime(1);
		unset($p);
		
		if(!file_put_contents($cache->filename,$buf)){
			$writable = (int) is_writable($cache->filename);
			$msg = "Cannot write to {$cache->filename} - file writable: $writable";
			throw new Exception($msg);
		}

		unset($buf);
		
		$cache->time_parsed = sprintf("%4f",$_ee-$_bb);
	}

	public static function initCache(){
		self::$cache = new ViewPageCache();
		self::$cache->init(self::$layout,self::$script,self::$lang,self::$tags);
	}
	
	public static function expired():bool{
		if(!self::$cache){
			self::initCache();
		}

		return self::$cache->expired;
	}
	
	public static function setRoute(array $route){
		self::$layout = $route['layout'];
		self::$script = $route['script'];
		self::$lang = $route['lang'];
		self::$route = $route;
	}
	
	public static function render($callback=false){
		$_SERVER['_VT_B'] = microtime(1);
		$_SERVER['_VM_B'] = memory_get_usage();

		$s = new ViewScope();
		$s->_vars = self::$vars;
		$s->_vars['_links'] = c::$links;
		$s->_vars['_link'] = c::$link;
		$s->_vars['_errorfields'] = c::$errorfields;
		$s->_vars['_messages'] = c::$messages;
	
		if(!empty($_SESSION['userdata'])){
			$s->_vars['_user'] = $_SESSION['userdata'];
		}
		
		if(_LANG !== _LANG_DEF){
			$s->_vars['_base_url'] = _BASEURL._LANG.'/';
			$s->_vars['_base_uri'] = _BASEURI._LANG;
		}else{
			$s->_vars['_base_url'] = _BASEURL;
			$s->_vars['_base_uri'] = '/'.ltrim(_BASEURI,'/');
		}

		// TODO: remove fields from being assigned on every page request
		$s->_vars['_fields'] = c::$fields;
		$s->_vars['_btngrp'] = btngrp::toArray();
		$s->_vars['_title'] = self::$title;
		$s->_vars['_subtitle'] = self::$subtitle;
		$s->_vars['_breadcrumb'] = bread::get();
		
		//ob_start();
		//ob_implicit_flush(1);
		$buf = self::combine();

		if(false !== $callback){
			$callback();
		}
			
		if(self::$route){
			$route = self::$route;
			if(_AMP && $route['amp']){
				$s->_vars['_altlinks'] = self::getAltLangLinksForAmp($route,_URI);
			}else{
				$s->_vars['_altlinks'] = self::getAltLangLinks($route,_URI);
			}
			if($route['viewkey']){
				viewmeta::$key = $route['viewkey'];
				viewmeta::setAllIfNotEmpty();
			}
	
			if($route['amp'] && !empty(self::$route['link']['amp'])){
				if(empty(self::$vars['_linkamp'])){
					self::setAltAmpLink(self::$route['link']['amp']);
				}
			}

			// check if the 'NO canonical' flag is set, then unset the url
			if($route['canon']){
				self::setCanonicalLink(_CANONICAL_URL);
			}else{
				self::setCanonicalLink('');
			}
		}
			
			
		$s->_vars += self::$vars;
		$s->_vars['_lang'] = lang::$data;
		$s->_vars['_stylesheets'] = self::getCss();
		$s->_vars['_javascripts'] = self::getJs();
		$s->_vars['_meta_html'] = viewmeta::getHtml();
		self::parse($buf,self::$lang,$s->_vars);
	
		
		$s->render(self::$cache->filename);
		unset($s);

		$_SERVER['_VT_E'] = microtime(1);
		$_SERVER['_VM_E'] = memory_get_usage();
	}

	public static function setAltAmpLink($link){
		self::$vars['_amplink'] = "<link rel=\"amphtml\" href=\"{$link}\">";
	}

	public static function scriptExists($path){
		$filename = "{{ VIEW_PATH }}/script/{$path}.php";
		$res = is_file($filename);
		return $res;
	}
	
	public static function getScriptFilename($path){
		$res = "{{ VIEW_PATH }}/script/{$path}.php";
		return $res;
	}
	
	public static function stripSpaces($buf){
		$buf = str_replace(["\t","\n","\r"],'',$buf);
		$buf = preg_replace('/<!--(.*)-->/Uis','',$buf);
		return $buf;
	}

	public static function setCanonicalLink($link){
		if(empty($link)){
			self::$vars['_canonlink'] = "";
			return;
		}
		self::$vars['_canonlink'] = "<link rel=\"canonical\" href=\"{$link}\">";
	}

	public static function setViewSubPath($path){
		self::$layout = "$path/".self::$layout;
		self::$script = "$path/".self::$script;
	}
	
}
