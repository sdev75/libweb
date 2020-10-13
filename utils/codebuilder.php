<?php

class CodeRoute {
	public string $id;
	public string $path;
	public array $patterns = [];
	public array $methods = [];
	public array $formats = [];
	public string $view;

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
			'view' => $this->view,
		];
	}
}

class CodeFunctionCalls {
	public array $data = [];
	public function parse(string $buf){

		$lines = explode("\n", $buf);
		foreach($lines as $line){
			if(mb_substr($line, 0, 2) === '//'){
				continue;
			}

		}

		// $offset = 0;
		// while(($pos = mb_strpos($buf, "\n", $offset)) !== FALSE){
		// 	$eol = mb_strpos($buf, "\n", $pos+1);
		// 	if($eol === FALSE){
		// 		$offset = $pos + 1;
		// 		continue;
		// 	}


			
		// 	$line = mb_substr($buf,$pos,$eol);
		// 	var_dump(["line: '$line'"]);

		// 	$offset = $pos + 1;
		// }
	}
}

class InlineFunctions {
	public array $data = [];
	
	public function parse(string $buf){
		$offset = 0;
		while(($pos = mb_strpos($buf, "#inline\n", $offset)) !== FALSE){
			
			$parentesis1 = mb_strpos($buf, '(', $pos);
			if($parentesis1 === FALSE){
				$offset = $pos + 1;
				continue;
			}


			$fn_name = mb_substr($buf, $pos+16, $parentesis1-$pos-16);
			$fn_name = trim($fn_name);
			$this->data[$fn_name] = [
				'offset_beg' => $pos,
				'offset_end' => $pos,
				'valid' => false,
			];
			
			$parentesis2 = mb_strpos($buf, ')', $parentesis1);
			if($parentesis2 === FALSE){
				$offset = $pos + 1;
				continue;
			}

			$params = mb_substr($buf,$parentesis1+1, $parentesis2-$parentesis1-1);
			$params_arr = explode(',', $params);
			$this->data[$fn_name]['params'] = [];
			if(count($params_arr)){
				foreach($params_arr as $param){
					$this->data[$fn_name]['params'][]= $param;
				}
			}

			$len = strlen("#{$fn_name}_beg");
			$body_beg = mb_strpos($buf, "#{$fn_name}_beg", $parentesis2);
			if($body_beg === FALSE){
				continue;
			}

			$body_end = mb_strpos($buf, "#{$fn_name}_end", $body_beg);
			if($body_end === FALSE){
				$offset = $pos + 1;
				continue;
			}

			$body = mb_substr($buf, $body_beg+$len, $body_end-$body_beg-$len);
			$this->data[$fn_name]['body'] = $body;

			$pos_end = mb_strpos($buf, '}', $body_end);
			if($pos_end === FALSE){
				$offset = $pos + 1;
				continue;
			}

			$this->data[$fn_name]['offset_end'] = $pos_end;
			$this->data[$fn_name]['valid'] = true;

			$t = mb_substr($buf, $pos, $pos_end-$pos+1);
			$buf = str_replace($t, '', $buf);
			$offset = $pos + 1;
		}

		return $buf;

	}

	public function parseFunctionCalls(string $buf){
		if(preg_match_all('~^([a-zA-Z_]+ ?\()~m',$buf,$matches)){
			$len = count($matches[0]);
			for($i=0;$i<$len;$i++){
				$needle = $matches[0][$i];
				$fn_name = $matches[1][$i];

				$this->calls[$fn_name] = 
				var_dump($fn_name);
			}
		}
	}

	public function replace(string $buf){
		foreach($this->data as $key => $a){
			var_dump($key,$a);
		}
	}
}

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
			'view' => $meta->view,
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
	public string $view = '';
	public array $patterns = [];
	public bool $valid = false;
	public bool $has_view = false;
	public array $viewvars = [];

	public function setFormat(string $format){
		$this->format = strtolower($format);
	}

	public function setMethod(string $method){
		$this->method = strtolower($method);
	}

	public function setView(string $view){	

		if(empty($view)){
			$this->has_view = false;
			return;
		}

		$this->view = $view;
		$this->has_view = true;
	}

	public function addViewVar(string $key){
		$this->viewvars[$key] = 1;
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
	public static InlineFunctions $inlines;

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
					break;
				case 'route': 
					$meta->addPattern($val);
					break;
				case 'view': 
					$meta->setView($val);
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
						'view' => $arr['view'],
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

	public static function replaceGlobalVariable(string $buf, string $keyword){
		
		$k = "\$_{$keyword}";
		
		$offset = 0;
		while(($beg = mb_strpos($buf, $k, $offset)) !== FALSE){

			$end = mb_strpos($buf, "\n", $beg);
			if($end === FALSE){
				$offset = $beg + 1;
				continue;
			}

			$len = strlen($k);
			$t = mb_substr($buf, $beg+$len, $end-$beg-$len);
			$t_end = mb_strrpos($t,';',0);
			

			if($t_end === FALSE){
				// if (fn($_val))
				$t_end = mb_strrpos($t,')',0);
				if($t_end === FALSE){
					$offset = $beg + 1;
					continue;
				}
	
				$t_del = ')';
				$needle = mb_substr($buf, $beg, $end - $beg -1 );
			}else{
				$t_del = ';';
				$needle = mb_substr($buf, $beg, $end - $beg );
			}

			$t = mb_substr($t,0,$t_end);
			$replace = "\$_SERVER['_{$keyword}']";
			$buf = str_replace($needle, "{$replace}{$t}{$t_del}", $buf);
		
			$offset = $beg + 1;
		}

		return $buf;
	}

	public static function replaceGlobalAssignments(string $buf){
		$buf = self::replaceGlobalVariable($buf, 'msg');
		$buf = self::replaceGlobalVariable($buf, 'errors');
		$buf = self::replaceGlobalVariable($buf, 'redirect');
		return $buf;
	}

	public static function getIncludeInfo(string $type, string $path, array $path_arr){
		if($type === 'lib'){
			$t = explode('-',$path_arr[0]);
			if($t[0] === 'libw' && isset($t[1])){
				return [
					'include' => "$type/$path",
					'code' => "lib/libw/".end($path_arr),
					'ver' => $t[1],
				];
			}
		}

		return [
			'include' => "$type/$path",
			'code' => "$type/$path",
			'ver' => '',
		];
	}

	public static function getIncludeByCode(string $code) : array{
		foreach(self::$includes as $k => $includes){
			foreach($includes as $a){
				if($a['code'] === $code){
					return $a;
				}
			}
		}

		return [];
	}

	// TODO: under development
	public static function parseInlineFunctions(string $buf){
		$x = new CodeFunctionCalls();
		$x->parse($buf);
		$buf = self::$inlines->parse($buf);
		$buf = self::$inlines->replace($buf);
		return $buf;
	}

	public static function collectViewAssignments(string $buf, CodeControllerMeta $meta){
		preg_match_all("~_view\[.([a-zA-Z_.-]+).\] ?=~",$buf,$matches);
		$len = count($matches[0]);
		if($len){
			var_dump($matches);
			for($i=0;$i<$len;$i++){
				$needle = $matches[0][$i];
				$key = $matches[1][$i];
				$meta->addViewVar($key);
			}
		}

		return $buf;
	}

	public static function parseAndPatch(string $buf, string $controller_path) {
		
		//$buf = self::parseInlineFunctions($buf);
		$buf = self::patchEnvVars($buf);
		//$buf = self::replaceViewAssignments($buf);
		$buf = self::replaceGlobalAssignments($buf);
		//$buf = self::collectViewAssignments($buf);

		if(preg_match_all('~^@include "([^"]+)";?~m',$buf,$matches)){
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

				$t = self::getIncludeInfo($type,$path,$path_arr);
				self::$includes[$controller_path] []= $t;
			
				$buf_include = file_get_contents($include_filename);
				$buf = str_replace($needle,$buf_include,$buf);
			}

			// support recursive parsing and patching
			$buf = self::parseAndPatch($buf, $controller_path);
		}

		$inc = self::getIncludeByCode('lib/libw/web.php');
		if($inc){
			$t = file_get_contents(self::$include_path."/lib/libw-{$inc['ver']}/include/web.prolog.php");
			$buf = str_replace("# web.prolog\n", $t, $buf);
			$t = file_get_contents(self::$include_path."/lib/libw-{$inc['ver']}/include/web.epilog.php");
			$buf = str_replace("# web.epilog\n", $t, $buf);
		}
	
		$buf = self::patchEnvVars($buf);
		return $buf;
	}

	public static function doesIncludeExist(string $path, string $val){
		var_dump($val);die;
		if(!isset(self::$includes[$path])){
			return false;
		}

		$res = array_search($val, self::$includes[$path]);
		return $res !== FALSE;
	}

}