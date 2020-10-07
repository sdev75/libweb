<?php

// code similar to SrcParser with a few modifications
// code duplication is favored for easy maintenance IMO

class ViewBuilder {

	public static $env = [];
	public static $views = [];
	public static $include_path = "";
	public static $includes = "";

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

	public static function stripLineComments($buf){
		$buf = preg_replace('~[ \t]*//[^\n]*~', '', $buf);
		return $buf;
	}

	public static function stripBlankLines($buf){
		$buf = preg_replace('~(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+~', "\n", $buf);
		return $buf;
	}
	public static function stripDoubleLines($buf){
		$buf = preg_replace('/^\s+/m', '', $buf);
	
		return $buf;
	}

	public static function stripMultilineComments($buf){
		$buf = preg_replace('!/\*.*?\*/!s', '', $buf);
		//$buf = preg_replace('/\n\s*\n/', "\n", $buf);
		return $buf;
	}

	public static function loadViewsToParse(string $inpath){
		eval('$views = ' . file_get_contents($inpath).';');
		self::$views = $views;
	}

	public static function loadCodeIncludes(string $inpath){
		eval('$includes = ' . file_get_contents($inpath).';');
		self::$includes = $includes;
	}

	public static function getFinalFilename(string $filename, string $inpath, string $outpath){
		$res = str_replace($inpath, $outpath, $filename);
		return $res;
	}

	public static function getIncludesByControllerName(string $name){
		return isset(self::$includes[$name]) ? self::$includes[$name] : false;
	}

	public static function build(array $view, string $path_code, string $inpath, string $outpath){
	
		$buf = file_get_contents("{$path_code}/{$view['controller']}");
		$buf = self::stripHashComments($buf);
		//$buf = self::stripLineComments($buf); // bugfix: can break code like '//' 
		//$buf = self::stripMultilineComments($buf);
		//$buf = self::stripDoubleLines($buf);
		if($buf === FALSE){
			throw new Exception(error_get_last());
		}

		$layout_filename = "{$inpath}/layout/{$view['layout']}.phtml";
		$script_filename = "{$inpath}/script/{$view['script']}.phtml";
	
		if(!file_exists($layout_filename)){
			fprintf(STDERR,"layout_filename not found: $layout_filename\n");
			return $buf;
		}
		if(!file_exists($script_filename)){
			fprintf(STDERR,"script_filename not found: $script_filename\n");
			return $buf;
		}

		$buf .= "\n?>\n";

		$t = new ViewCombiner();
		$buf_layout = $t->parse($layout_filename, $inpath);
		$buf_script = $t->parse($script_filename, $inpath);
		$buf_view = str_replace("{% include script %}",$buf_script,$buf_layout);
		unset($buf_layout,$buf_script,$t);
		

		$t = new ViewParser('',[]);
		$b=microtime(1);
		$buf .= $t->parse($buf_view);
		$e=microtime(1);


		$includes = self::getIncludesByControllerName($view['controller']);
		var_dump($includes);die;


		$view_filename = "{$layout_filename}/{$script_filename}";
		$output_filename = "{$outpath}/{$view['controller']}";


		$dir = $outpath;
		if(!is_dir($dir) && !mkdir($dir,0755,true)){
			throw new Exception("Cannot create directory: '$dir'");
		}

		if(!file_put_contents($output_filename,$buf)){
			throw new Exception("Cannot save to '$output_filename'");
		}

		unset($buf);
		fprintf(STDOUT, "\e[37m \t+ %-80s\t>> %-40s (took: %.6fs)\e[0m\n",$view_filename,$output_filename,$e-$b);
	}

	public static function patch($buf){
		$buf = str_replace('<?php','',$buf);
		$buf = str_replace("\nn","\n",$buf);
		$date = date('m/d/Y h:i:s a',time());
		$buf = "<?php\n/* Built with libweb on $date */$buf";
		return $buf;
	}

	public static function parseAndPatch(string $buf) {
		
		$buf = self::patchEnvVars($buf);

		if(preg_match_all("#@include '([a-zA-Z_.]+)/([^']+)';[ ]*#",$buf,$matches)){
			$len = count($matches[0]);
			for($i=0;$i<$len;$i++){
				$needle = $matches[0][$i];
				$type = $matches[1][$i];
				$path = $matches[2][$i];

				$include_path = self::$include_path;
				$include_filename = "{$include_path}/$type/$path";

				if(!file_exists($include_filename)){
					throw new Exception("file_exists(): $include_filename ($needle)");
				}

				$buf = str_replace($needle,file_get_contents($include_filename),$buf);
				$buf = self::parseAndPatch($buf);
			}
		}
		
		$buf = self::patchEnvVars($buf);
		return $buf;
	}

}