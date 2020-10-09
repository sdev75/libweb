<?php

class CodeRoute {
	public string $id;
	public string $path;
	public array $patterns = [];
	public array $methods = [];
	public array $formats = [];
	public string $layout;
	public string $script;

	public function toArray(){
		return [
			'id' => $this->id,
			'path' => $this->path,
			'patterns' => $this->patterns,
			// 'langs' => $this->langs,
			'methods' => $this->methods,
			'formats' => $this->formats,
			// 'flags' => $this->flags,
			// 'controller' => $this->controller,
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
	public array $data = [];

	public function addMetadata(CodeControllerMeta $meta){
		$id = $meta->id;
		$format = $meta->format;

		if(!isset($this->array[$id]['patterns'])){
			$this->data[$id]['patterns'] = [];
		}
		
		if(!isset($this->array[$id]['formats'])){
			$this->data[$id]['formats'] = [];
		}

		if(!empty($meta->patterns)){
			$this->data[$id]['patterns'] =
				array_merge($this->data[$id]['patterns'],  $meta->patterns);
			//$this->data[$id]['patterns'] []= $meta->patterns;
		}
		

		if(!isset($this->array[$id]['formats'][$format])){
			$this->array[$id]['formats'][$format] = [];
		}

		$this->data[$id]['formats'][$format] = [
			'layout' => $meta->layout,
			'script' => $meta->script,
			'path' => $meta->path,
			'has_view' => $meta->has_view,
		];

		if(!isset($this->data[$id]['formats'][$format]['methods'])){
			$this->data[$id]['formats'][$format]['methods'] = [];
		}

		$this->data[$id]['formats'][$format]['methods'] [$meta->method] = 1;
	}

	public function getMetadata(){
		return $this->data;
	}
}

class CodeControllerMeta {
	public string $path;
	public string $format = 'html';
	public string $method = 'get';
	public string $layout = 'html';
	public string $script = '';
	public array $patterns = [];
	public bool $valid = false;
	public bool $has_view = false;

	public function setFormat(string $format){
		$this->format = strtolower($format);
	}

	public function setMethod(string $method){
		$this->method = strtolower($method);
	}

	public function setView(string $script, string $layout){	

		if(empty($script)){
			$this->script = '';
			$this->has_view = false;
			$layout = '';
			return;
		}
		if(empty($layout)){
			$layout = $this->layout;
		}

		$this->layout = $layout;
		$this->script = $script;
		$this->has_view = true;
	}

	public function addPattern(string $pattern){
		if($pattern[0]==='/'){
			$this->patterns []= substr($pattern,1);
			return;
		}
		$this->patterns []= $pattern;
	}

	// name = page.format-method (from page.json-post.php)
	public function parse(string $filename, string $name){
		// Overwrite method if exists
		$t = explode('-', $name);
		if(count($t) === 2){
			$this->method = $t[1];
			$name = $t[0];
		}
		
		// Overwrite format if exists
		$t = explode('.', $name);
		if(count($t) === 2){
			$this->format = $t[1];
			$name = $t[0];
		}
		
		$this->id = $name;
		$this->path = $filename;
		$this->valid = true;
	}
		
	public function parseFromFilename(string $filename) : string{
		$name = pathinfo($filename,PATHINFO_FILENAME);
		$this->parse($filename, $name);
		return $name;
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
	public static $libver = 0;

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

	public static function getMetadataFromAnnotations(string $buf, CodeControllerMeta $metadata) : CodeControllerMeta{

		$meta = clone $metadata;

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
					break;
				case 'view': 
					$arr = explode('/',$val);
					if(!count($arr) > 2){
						throw new Exception("Invalid view value: '$val'");
					}
					$layout = array_shift($arr);
					$script = implode('/',$arr);
					$meta->setView($script,$layout);
					break;
				default:
					break;
			}
		}

		return $meta;
	}

	public static function printExport($val){
		$export = var_export($val,true);
		$res = str_replace(['array (','),',');'],['[','],','];'],$export);
		if(mb_substr($res,-1) === ')'){
			$res = mb_substr($res,0,-1).']';
		}
		return $res;
	}

	public static function buildRoutesFromMetadata(){
		$metadata = self::$metadata->getMetadata();
		foreach($metadata as $id => $data){
			$route_data = [
				'id' => $id,
				'formats' => $data['formats'],
			];
			self::$routes[$id] = $route_data;
			foreach($data['patterns'] as $pattern){
				self::$routes_rmap[$pattern] = $route_data;
			}
		}
	}

	public static function buildViewsFromMetadata(){
		$metadata = self::$metadata->getMetadata();
		
		foreach($metadata as $id => $data){
			foreach($data['formats'] as $fmt => $arr){
				if($arr['has_view']){
					self::$views[$id] = [
						'controller' => $arr['path'],
						'layout' => $arr['layout'],
						'script' => $arr['script'],
					];
				}
			}
		}
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

	public static function build(string $filename, string $inpath, string $outpath){
		$c_path = self::extractControllerPath($filename, $inpath);
		$c_meta = new CodeControllerMeta();
		$c_meta->parseFromFilename($c_path);
		if(!$c_meta->valid){
			throw new Exception("Invalid controller meta#1 for '$filename'");
		}

		$buf = '';
		$buf .= "# web.prolog\n";
		$buf .= file_get_contents($filename);
		$buf .= "# web.epilog\n";
		$buf = self::parseAndPatch($buf,$c_meta->path);
		$c_meta = self::getMetadataFromAnnotations($buf, $c_meta);
		if(!$c_meta->valid){
			throw new Exception("Invalid controller meta#2 for '$filename'");
		}

		self::$metadata->addMetadata($c_meta);
		
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
		if(self::$libver){
			$version = self::$libver;
			$buf = "<?php\n/* Built with libweb v$version on $date */$buf";
		}else{
			$buf = "<?php\n/* Built with libweb on $date */$buf";
		}
		
		return $buf;
	}

	public static function replaceViewAssignments(string $buf){
		preg_match_all("~view::set *\(([^\)]+)\);~",$buf,$matches);
		$len = count($matches[0]);
		if($len){
			for($i=0;$i<$len;$i++){
				$needle = $matches[0][$i];
				$val = $matches[1][$i];

				$pair = explode(',',$val);
				$quote = $pair[0][0];
				$sub = "\$_view_vars[$quote" . substr($pair[0], 1, -1) . "$quote]";

				// It might be string literals or simply variables
				if($pair[1][0] == "\'" || $pair[1][0] === "\""){
					$quote = $pair[1][0];
					$sub .= "=$quote" . substr($pair[1],1,-1) . "$quote;";
				}else{
					$sub .= "={$pair[1]};";
				}
				
				$buf = str_replace($needle, $sub, $buf);
			}
		}

		return $buf;
	}

	public static function parseAndPatch(string $buf, string $controller_path) {
		
		$buf = self::patchEnvVars($buf);
		$buf = self::replaceViewAssignments($buf);

		if(preg_match_all('~@include "([^"]+)";?~',$buf,$matches)){
		//if(preg_match_all('~# ?include <([^>]+)>~',$buf,$matches)){
			$len = count($matches[0]);
		
			for($i=0;$i<$len;$i++){
				$needle = $matches[0][$i];
				$path_arr = explode('/',$matches[1][$i]);
				$type = array_shift($path_arr);
				$path = implode('/',$path_arr);

				$include_path = self::$include_path;
				$include_filename = "{$include_path}/$type/$path";

				if(!file_exists($include_filename)){
					throw new Exception("Include missing: $include_filename ($needle)");
				}
				
				self::$includes[$controller_path] []= "$type/$path";
			
				$buf_include = file_get_contents($include_filename);
				$buf = str_replace($needle,$buf_include,$buf);
			}

			// support recursive parsing and patching
			$buf = self::parseAndPatch($buf, $controller_path);
		}

	
		if(self::doesIncludeExist($controller_path,'lib/libweb/web.php')){
			$t = file_get_contents(self::$include_path.'/lib/libweb/include/web.prolog.php');
			$buf = str_replace("# web.prolog\n", $t, $buf);
			$t = file_get_contents(self::$include_path.'/lib/libweb/include/web.epilog.php');
			$buf = str_replace("# web.epilog\n", $t, $buf);
		}
	
		$buf = self::patchEnvVars($buf);
		return $buf;
	}

	public static function doesIncludeExist(string $path, string $val){
		if(!isset(self::$includes[$path])){
			return false;
		}

		$res = array_search($val, self::$includes[$path]);
		return $res !== FALSE;
	}

}