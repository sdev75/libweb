<?php

class CodeAnnotation {
	public string $id;
	public array $patterns = [];
	public string $view;

	public function setId(string $id){
		$this->id = $id;
	}

	public function addPattern(string $pattern){
		$this->patterns []= $pattern;
	}

	public function setView(string $view){
		$this->view = $view;
	}

	// public function addLang($code){
	// 	$code = strtolower($code);
	// 	$this->langs[$code] = $code;
	// }

	// public function addMethod($method){
	// 	$method = strtolower($method);
	// 	$this->methods[$method] = $method;
	// }

	// public function addFormat($format){
	// 	$format = strtolower($format);
	// 	$this->formats[$format] = $format;
	// }

}

class CodeRoute {
	public string $id;
	public array $patterns = [];
	// public array $langs = [];
	// public array $methods = [];
	// public array $formats = [];
	// public array $flags = [];
	public string $controller;
	public string $layout;
	public string $script;

	public function toArray(){
		return [
			'id' => $this->id,
			'patterns' => $this->patterns,
			'langs' => $this->langs,
			// 'methods' => $this->methods,
			// 'formats' => $this->formats,
			// 'flags' => $this->flags,
			'controller' => $this->controller,
			'layout' => $this->layout,
			'script' => $this->script,
		];
	}
}

/*
controller
	page
		patterns
			/client/[a-z]
			/clientx/[a-z]
		data
			amp
				layout = amp
				script = client/amp/client.phtml
				method = get [default]
			html [default]
				layout = html
				script = client/client.phtml
				method = get post
			json
				layout = null [default]
				method get post

	page2
		patterns
			/client2/[a-z]
			/client2x/[a-z]
		data
			amp
				layout = amp
				script = client/amp/client.phtml
				method = get [default]
			html [default]
				layout = html
				script = client/client.phtml
				method = get post
			json
				layout = null [default]
				method get post	
*/
class CodeControllerMetaCollection {
	public array $array = [];

	public function addPattern(string $id, $pattern){
		if(!isset($this->array[$id]['patterns'])){
			$this->array[$id]['patterns'] = [];
		}
		$this->array[$id]['patterns'] []= $pattern;
	}

	public function setMetadata(string $id, CodeControllerMeta $data){
		$format = $data->format;

		if(!isset($this->array[$id][$format])){
			$this->array[$id][$format] = [];
		}

		$this->array[$id][$format] = [
			'layout' => $data->layout,
			'view' => $data->view,
		];

		if(!isset($this->array[$id][$format]['methods'])){
			$this->array[$id][$format]['methods'] = [];
		}

		$this->array[$id][$format]['methods'] [$data->method] = 1;
	}
}

class CodeControllerMeta {
	public string $path;
	public string $format = 'html';
	public string $method = 'get';
	public string $layout = 'html';
	public string $script = '';
	public bool $valid = false;
	public bool $has_view = false;

	public function setFormat(string $format){
		$this->format = strtolower($format);
	}

	public function setMethod(string $method){
		$this->method = strtolower($method);
	}

	public function setView(string $script, string $layout=null){	
		if(empty($script)){
			$this->script = '';
			$this->has_view = false;
			$layout = '';
			return;
		}

		$layout = $layout ?? $this->$layout;
		$this->script = $script;
		$this->has_view = true;
	}
}

class CodeBuilder {

	public static $env = [];
	public static $files = [];
	public static $annotations = [];
	public static $include_path = "";
	public static $routes = [];
	public static $routes_rmap = [];
	public static $views = [];
	public static $includes = [];
	public static CodeControllerMetaCollection $metadata;

	public static function setEnvVars(array $env){
		self::$env = $env;
	}

	public static function setIncludePath(string $include_path){
		self::$include_path = $include_path;
	}

	public static function patchEnvVars(string $buf){
		foreach(self::$env as $k=>$v){
			$buf = preg_replace("/{{[ ]*{$k}[ ]*}}/i",$v,$buf);
		}
		return $buf;
	}

	public static function stripDoubleLines($buf){
		$buf = str_replace("\n\n\n","\n",$buf);
		return $buf;
	}

	public static function stripSpaces($buf){
		$buf = str_replace("\t","",$buf);
		$buf = preg_replace('!\s+!',' ',$buf);
		$buf = trim($buf);
		return $buf;
	}

	public static function stripHashComments($buf){
		$buf = preg_replace("~^[#]{1}[^\\n]*\\n~m",'',$buf);
		return $buf;
	}

	public static function stripMultilineComments($buf){
		$buf = preg_replace('!/\*.*?\*/!s', '', $buf);
		$buf = preg_replace('/\n\s*\n/', "\n", $buf);
		return $buf;
	}

	public static function collectFiles(string $inpath){
		$files = [];
		$path = realpath($inpath);
		$objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path),
			RecursiveIteratorIterator::SELF_FIRST);
		foreach($objects as $name => $object){
			$ext = pathinfo($name,PATHINFO_EXTENSION);
			if($ext !== 'php'){
				continue;
			}
			$files[]=$name;
		}

		self::$files = $files;
	}

	public static function extractControllerPath(string $filename, string $inpath){
		$buf = str_replace($inpath.'/', '', $filename);
		return $buf;
	}

	public static function parseAnnotations(string $buf, CodeControllerMeta $meta) : CodeControllerMeta{

		//$ann = new CodeAnnotation();

		preg_match_all("~# @([a-zA-Z]+)[ \t]*([^\n]+)~",$buf,$matches);

		$len = count($matches[0]);
		for($i=0;$i<$len;$i++){
			$needle = $matches[0][$i];
			$key = $matches[1][$i];
			$val = $matches[2][$i];
		
			switch($key){
				case 'id':
					$meta->id = $val;
					//$ann->setId($val);
					break;
				case 'route': 
					$meta->addPattern($val);
					//$ann->addPattern($val);
					break;
				case 'view': 
					$arr = explode('/',$val);
					if(!count($arr) > 2){
						throw new Exception("Invalid view value: '$val'");
					}
					$layout = array_shift($arr);
					$script = implode('/',$arr);
					$meta->setView($val);
					break;
				default:
					break;
			}
		}

		// If no ID, use ID passed in params
		if(!isset($ann->id)){
			$ann->setId($meta->id);
		}

		if(!isset($ann->view)){
			$ann->setView($meta->view);
		}

		return $ann;
	}

	public static function printExport($val){
		$export = var_export($val,true);
		$res = str_replace(['array (','),',');'],['[','],','];'],$export);
		if(mb_substr($res,-1) === ')'){
			$res = mb_substr($res,0,-1).']';
		}
		return $res;
	}

	public static function getRouteFromAnnotation(CodeAnnotation $ann) : CodeRoute {
		$route = new CodeRoute();
		$route->id = $ann->id;
		$route->patterns = $ann->patterns;

		$view = explode("/",$ann->view);
		$layout = array_shift($view);
		$route->layout = $layout;
		$route->script = implode('/',$view);
		return $route;
	}

	// todo detect post & json as well as other flags
	public static function buildRoutesFromAnnotation(CodeAnnotation $ann){
		$route = self::getRouteFromAnnotation($ann);
		self::$routes[$route->id] = $route->toArray();
		foreach($route->patterns as $pattern){
			self::$routes_rmap[$pattern] = $route->id;
		}
	}

	public static function buildViewsFromAnnotation(CodeAnnotation $ann){
		$route = self::getRouteFromAnnotation($ann);
		self::$views[$route->id] = [
			'controller' => $route->controller,
			'layout' => $route->layout,
			'script' => $route->script,
		];
	}

	public static function writeRoutesToFile(string $filename){
		$dir = pathinfo($filename,PATHINFO_DIRNAME);
		if(!is_dir($dir) && !mkdir($dir,0755,true)){
			throw new Exception(error_get_last());
		}

		$buf = self::printExport(self::$routes);
		if(!file_put_contents($filename,$buf)){
			throw new Exception(error_get_last());
		}

		fprintf(STDOUT, "\e[37m \t+ %-80s\t>> %-40s\e[0m\n","Routes",$filename);
	}

	public static function writeViewsToFile(string $filename){
		$dir = pathinfo($filename,PATHINFO_DIRNAME);
		if(!is_dir($dir) && !mkdir($dir,0755,true)){
			throw new Exception(error_get_last());
		}

		$buf = self::printExport(self::$views);
		if(!file_put_contents($filename,$buf)){
			throw new Exception(error_get_last());
		}

		fprintf(STDOUT, "\e[37m \t+ %-80s\t>> %-40s\e[0m\n","Views",$filename);
	}

	public static function writeIncludesToFile(string $filename){
		$dir = pathinfo($filename,PATHINFO_DIRNAME);
		if(!is_dir($dir) && !mkdir($dir,0755,true)){
			throw new Exception(error_get_last());
		}

		$buf = self::printExport(self::$includes);
		if(!file_put_contents($filename,$buf)){
			throw new Exception(error_get_last());
		}

		fprintf(STDOUT, "\e[37m \t+ %-80s\t>> %-40s\e[0m\n","Includes",$filename);
	}

	public static function getControllerMetadata(string $filename) : CodeControllerMeta{
		$meta = new CodeControllerMeta();
		$name = pathinfo($filename,PATHINFO_FILENAME);
		$t = explode('.', $name);
		$len = count($t);

		switch($len){
			case 1: // ID
				$meta->id = $t[0];
				$meta->method = "get";
				$meta->format = "html";
				$meta->valid = true;
				break;
			case 2: // ID.FORMAT
				$meta->id = $t[0];
				$meta->method = "get";
				$meta->format = $t[1];
				$meta->valid = true;
				break;
			case 3: // ID.FORMAT.METHOD
				$meta->id = $t[0];
				$meta->method = $t[2];
				$meta->format = $t[1];
				$meta->valid = true;
				break;
			default:		
		}
		
		$meta->path = $filename;
		return $meta;
	}

	public static function build(string $filename, string $inpath, string $outpath){
		$c_path = self::extractControllerPath($filename, $inpath);
		$c_meta = self::getControllerMetadata($c_path);
		if(!$c_meta->valid){
			throw new Exception("Invalid controller meta for '$filename'");
		}

		$buf = file_get_contents($filename);
		$buf = self::parseAndPatch($buf,$c_meta->path);
		$ann = self::parseAnnotations($buf, $c_meta);
		var_dump($ann);exit(1);

		if(!isset($ann->id)){
			throw new Exception("Missing annotation attribute: id for '$controller'");
		}

		
		$ann->controller = $controller;
		
		self::buildRoutesFromAnnotation($ann);
		self::buildViewsFromAnnotation($ann);

		var_dump(self::$routes_rmap);exit(1);
		
		$buf = self::stripHashComments($buf);
		$buf = self::stripDoubleLines($buf);
		$buf = self::patch($buf);
		$finalname = str_replace($inpath, $outpath, $filename);
		$dir = pathinfo($finalname,PATHINFO_DIRNAME);
		if(!is_dir($dir) && !mkdir($dir,0755,true)){
			throw new Exception("Failed to create directory: '$dir'");
		}

		file_put_contents($finalname,$buf);
		fprintf(STDOUT, "\e[37m \t+ %-80s\t>> %-40s\e[0m\n",$filename,$finalname);
	}

	public static function patch($buf){
		$buf = str_replace('<?php','',$buf);
		$buf = str_replace("\nn","\n",$buf);
		$date = date('m/d/Y h:i:s a',time());
		$buf = "<?php\n/* Built with libweb on $date */$buf";
		return $buf;
	}

	public static function parseAndPatch(string $buf, string $controller_path) {
		
		$buf = self::patchEnvVars($buf);

		if(preg_match_all('~#include "([^"]+)"~',$buf,$matches)){
			$len = count($matches[0]);
		
			for($i=0;$i<$len;$i++){
				$needle = $matches[0][$i];
				$path_arr = explode('/',$matches[1][$i]);
				$type = array_shift($path_arr);
				$path = implode('/',$path_arr);

				$include_path = self::$include_path;
				$include_filename = "{$include_path}/$type/$path";

				if(!file_exists($include_filename)){
					throw new Exception("file_exists(): $include_filename ($needle)");
				}
				
				self::$includes[$controller_path] []= "$type/$path";
			
				$buf_include = file_get_contents($include_filename);
				$buf = str_replace($needle,$buf_include,$buf);
			}

			// support recursive parsing and patching
			$buf = self::parseAndPatch($buf, $controller);
		}

		$buf = self::patchEnvVars($buf);
		return $buf;
	}

}