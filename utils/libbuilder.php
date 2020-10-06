<?php

class LibBuilder {
	public static $files = [];
	public static $version = "0";

	public static function exportArrayToString($var):string{
		$export = var_export($var,true);
		$res = str_replace(['array (','),',');'],['[','],','];'],$export);
		if(mb_substr($res,-1) === ')'){
			$res = mb_substr($res,0,-1).'];';
		}
		return $res;
	}

	public static function parse(string $libpath, array $files, array $vars, string $output){
		$buf = '';
		self::$files = 0;
		foreach($files as $file){
			$filename = "{$libpath}/$file";
			$buf .= file_get_contents($filename);
			// echo "Processing: $filename\n";
			self::$files++;
		}
	
		// uncomment comments with special tag //!
		$buf = preg_replace("/^\/\/!(.*)$/m","$1",$buf);

		// global vars
		// foreach($vars as $k=>$v){
		// 	$buf = preg_replace("/{{[ ]*{$k}[ ]*}}/i",$v,$buf);
		// }

		$buf = str_replace('<?php','',$buf);
		$buf = str_replace("\nn","\n",$buf);

		$buf = preg_replace('!/\*.*?\*/!s', '',$buf);
		$buf = preg_replace('/\n\s*\n/', "\n",$buf);
		$buf = preg_replace('/^[\t]*\/\/.*$\n/m','',$buf);

		$date = date('m/d/Y h:i:s a',time());
		$version = self::$version;
		$buf = "<?php\n/* Created by libweb $version on $date */\n$buf";

		$dir = pathinfo($output,PATHINFO_DIRNAME);
		$t = explode("/", $dir);
		$basepath = $t[count($t)-1];
		$t[count($t)-1] = "$basepath-$version";
		$newdir = implode("/", $t);

		if(!is_dir($newdir)){
			if(!mkdir($newdir,0755,false)){
				throw new Exception(error_get_last());
			}
		}

		$output = str_replace($dir,$newdir,$output);
		if(!file_put_contents($output,$buf)){
			$msg = "\e[0;31mUnable to save file to: $output\e[0m\n";
			throw new Exception($msg);
		}
	}
}