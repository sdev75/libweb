<?php

// code similar to SrcParser with a few modifications
// code duplication is favored for easy maintenance IMO

class ViewBuilder {

	public static $env = [];
	public static $views = [];
	public static $include_path = "";
	public static $includes = "";
	public static $pp = null;

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

	public static function stripHtmlSpaces($buf){
		$buf = str_replace("> <","><",$buf);
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
		return isset(self::$includes[$name]) ? self::$includes[$name] : [];
	}

	public static function doesIncludeExist(array $includes, string $val){
		$res = array_search($val, $includes);
		return $res !== FALSE;
	}

	public static function writeOutputToFile(string $filename, string $buf){
		$dirname = pathinfo($filename,PATHINFO_DIRNAME);
		if(!is_dir($dirname) && !mkdir($dirname,0755,true)){
			throw new Exception("Cannot create directory: '$dirname'");
		}

		if(!file_put_contents($filename,$buf)){
			throw new Exception("Cannot save to '$filename'");
		}
	}

	public static function writeOutputToStdout(
			string $in_filename, string $out_filename, float $time=0){
	
		if($time){
			fprintf(STDOUT,"\e[37m\t+ %-80s\t>> %-40s (took: %.6fs)\e[0m\n",
			$in_filename,$out_filename,$time);
			return;
		}
		
		fprintf(STDOUT,"\e[37m\t+ %-80s\t>> %-40s\e[0m\n",$in_filename,$out_filename);
	}

	public static function build(array $view, string $path_code, string $inpath, string $outpath){
	
		$code_filename = "{$path_code}/{$view['controller']}";
		$buf = file_get_contents($code_filename);
		if($buf === FALSE){
			throw new Exception("Failed to open '{$code_filename}");
		}

		$buf = self::stripHashComments($buf);

		$layout_filename = "{$inpath}/layout/{$view['layout']}.phtml";
		$script_filename = "{$inpath}/script/{$view['script']}.phtml";
		$view_filename = "{$view['layout']}:{$view['script']}";
		$output_filename = "{$outpath}/{$view['controller']}";

		// Check if view exists, otherwise output the buffer as it is
		$view_exists = true;
		if(!file_exists($layout_filename)){
			fprintf(STDERR,
			"\e[0;93mWARNING: VIEW LAYOUT not found: $layout_filename\e[0m\n");
			$view_exists = false;
		}
		if($view_exists && !file_exists($script_filename)){
			fprintf(STDERR,
			"\e[0;93mWARNING: VIEW SCRIPT not found: $script_filename\e[0m\n");
			$view_exists = false;
		}
		if(!$view_exists){
			self::writeOutputToFile($output_filename, $buf);
			self::writeOutputToStdout($view_filename, $output_filename);
			return;
		}

		$_b=microtime(1);
		// combine view files to buf
		$t = new ViewCombiner();
		$buf_layout = $t->parse($layout_filename, $inpath);
		$buf_script = $t->parse($script_filename, $inpath);
		$buf_view = str_replace("{% include script %}",$buf_script,$buf_layout);

		// parse combined view files buf
		$t = new ViewParser('',[]);
		$buf_view = $t->parse($buf_view);
		$_e=microtime(1);

		unset($buf_layout,$buf_script,$t);

		$buf_view = self::stripSpaces($buf_view);
		$buf_view = self::stripHtmlSpaces($buf_view);

		// TODO: clean up and improve the quality of code (still good though)
		$includes = self::getIncludesByControllerName($view['controller']);
		if(self::doesIncludeExist($includes,'lib/libweb/session')){
			// definitely using the view
			$t = <<< EOT
				if(!empty(\$_SESSION['userdata'])){
					\$_view_vars['_user'] = \$_SESSION['userdata'];
				}
			EOT;
			$buf .= $t;
		}

		
		$prolog = file_get_contents(self::$include_path.'/lib/libweb/view.prolog.php');
		$buf .= substr($prolog,5);
		if(self::$pp){
			$buf = self::$pp->parseIfElseCond($buf);
		}

		$buf .= "\n?>\n";
		$buf .= $buf_view;

		$epilog = file_get_contents(self::$include_path.'/lib/libweb/view.epilog.php');
		$buf .= $epilog;
		if(self::$pp){
			$buf = self::$pp->parseIfElseCond($buf);
		}
		
		self::writeOutputToFile($output_filename, $buf);
		self::writeOutputToStdout($view_filename, $output_filename, $_e-$_b);
	}

}