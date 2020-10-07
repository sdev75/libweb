<?php

class View {
	
	public static $vars = [];
	public static $lang;
	public static $route;
	public static $layout;
	public static $script;
	
	public static $title = '';
	public static $subtitle = '';

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

