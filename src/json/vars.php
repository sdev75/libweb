<?php

mb_internal_encoding('UTF-8');
date_default_timezone_set('{{ TIMEZONE_DEF }}');
set_include_path('{{ INCLUDE_PATH }}');

define('_BASEURL',$_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].'{{ APP_BASEURI }}');
define('_BASEURI','{{ APP_BASEURI }}');
define('_PATH',!empty($_SERVER['REDIRECT_C'])?$_SERVER['REDIRECT_C'].'.json':'{{ PATH_DEF }}.json');
define('_LANG_DEF','{{ LANG_DEF }}');

define('_TIME_B',microtime(1));
define('_MEM_B',memory_get_usage());
define('_REQ_ID',str_pad(rand(1,1000000).time(),10,'0',STR_PAD_RIGHT));
define('_REQ_TS',time());

define('_AMP',false);

$uri = $_SERVER['REQUEST_URI'];

// remove query string
if(false !== ($pos=mb_strrpos($uri,'?'))){
	$uri = mb_substr($uri,0,$pos);
}

// remove basepath from uri
if(mb_strpos($uri,'{{ APP_BASEURI }}')===0){
	$uri = mb_substr($uri,mb_strlen('{{ APP_BASEURI }}'));
}

if(!empty($_SERVER['REDIRECT_LANG'])){
	define('_URI',mb_substr($uri,3));
	define('_LANG',$_SERVER['REDIRECT_LANG']);
}else{
	define('_URI',$uri);
	define('_LANG','{{ LANG_DEF }}');
}
