<?php

class SrcBuilder {

	public static $env = [];
	public static $files = [];
	public static $include_path = "";
	public static $debug = false;
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

	public static function build(string $filename, string $inpath, string $outpath){
		$buf = file_get_contents($filename);
		$buf = self::parseAndPatch($buf);
		$buf = self::patch($buf);
		//$buf = self::stripSpaces($buf);
		$buf = self::stripHashComments($buf);
		$buf = self::stripMultilineComments($buf);

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

	public static function parseAndPatch(string $buf) {
		
		$buf = self::patchEnvVars($buf);

		if(self::$pp){
			$buf = self::$pp->parseIfElseCond($buf);
		}

		if(preg_match_all("#=? ?@include '([a-zA-Z_.:]+)/([^']+)';[ ]*#",$buf,$matches)){
			$len = count($matches[0]);
			for($i=0;$i<$len;$i++){
				$needle = $matches[0][$i];
				$type = $matches[1][$i];
				$path = $matches[2][$i];

				$include_path = self::$include_path;
				$include_filename = "{$include_path}/$type/$path";
				if(substr($include_filename, -4) !== '.php'){
					$include_filename .= '.php';
				}

				if(!file_exists($include_filename)){
					throw new Exception("file_exists(): $include_filename ($needle)");
				}

				if($needle[0] === '='){
					// doing array assignment by inclusion
					// aka. $var = @include(...)
					$t = '=' . file_get_contents($include_filename) .';';
					$buf = str_replace($needle,$t,$buf);

				}else{
					$buf = str_replace($needle,file_get_contents($include_filename),$buf);
				}

				
				$buf = self::parseAndPatch($buf);
			}
		}
		
		$buf = self::patchEnvVars($buf);
		return $buf;
	}

}