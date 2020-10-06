<?php

class ViewParserVar{
	public $flag = 0;
	public $base;
	public $name;
	public $value;
	public $params = [];
}

class ViewParser{

	const PROHIBITED = [
		'RecursiveIteratorIterator',
		'RecursiveDirectoryIterator',
		'exec',
		'passthru',
		'shell_exec',
		'system',
		'proc_open',
		'popen',
		'curl_exec',
		'curl_multi_exec',
		'parse_ini_file',
		'show_source',
		'file_get_contents',
		'file_put_contents',
		'file',
		'fopen',
		'fwrite',
		'ftruncate',
		'move_uploaded_file',
		'unlink',
		'PDO',
		'mysqli_open',
		'include',
		'include_once',
		'require',
		'require_once',
		'var_dump',
		'print_r',
		'syslog',
		'mysql_pconnect',
		'mysql_connect',
		'ini_get_all',
		'fput',
		'eval',
		'rmdir',
		'chown',
		'chmod',
		'chgrp',
		'copy',
		'delete',
		'fgets',
		'fgetss',
		'feof',
		'fgetcsv',
		'fgetc',
		'link',
		'umask',
		'symlink',
		'touch',
		'mkdir',
		'lchgrp',
		'lchown',
		'clearstatcache',
		'readfile',
		'readlink',
		'rename',
		'rewind',
		'ini_get',
		'ini_get_all',
		'ini_set',
		'ini_alter',
		'phpinfo',
		'putenv',
		'getenv',
		'dl',
		'var_export',
		'get_cfg_var',
	];

	const TAG_BLOCK = 1 << 0;
	const TAG_VAR = 1 << 1;
	const TAG_COMMENT = 1 << 2;
	const TAG_IF = 1 << 3;
	const TAG_FOREACH = 1 << 4;
	const TAG_CLOSE = 1 << 5;
	const TAG_ELSE = 1 << 6;
	const TAG_ELSEIF = 1 << 7;

	const VAR_TAG_OPEN = '{{';
	const VAR_TAG_CLOSE = '}}';
	const VAR_TAG_LEN = 2;
	const VAR_TAG_MOD = '|';

	const VAR_FLAG_RAW = 1 << 0;
	const VAR_FLAG_DATE = 1 << 1;
	const VAR_FLAG_TOLOWER = 1 << 2;
	const VAR_FLAG_TOUPPER = 1 << 3;
	const VAR_FLAG_STRING = 1 << 4;
	const VAR_FLAG_ARRAY = 1 << 5;
	const VAR_FLAG_ARRAY_DOT = 1 << 6;
	const VAR_FLAG_OBJECT = 1 << 7;
	const VAR_FLAG_ARGS = 1 << 8;
	const VAR_FLAG_DOTTED_KEY = 1 << 9;
	const VAR_FLAG_ESCAPE = 1 << 10;
	const VAR_FLAG_COUNT = 1 << 11;
	const VAR_FLAG_SCOPEVAR = 1 << 12;

	const COMMENT_TAG_OPEN = '{#';
	const COMMENT_TAG_CLOSE = '#}';
	const COMMENT_TAG_LEN = 2;

	public $scopes = [];
	public $scope_vars = [];

	const SCOPE_FOREACH = 1 << 0;
	const SCOPE_IF = 1 << 1;
	
	public $lang;
	public $lang_code;

	public $buf;
	
	public $view_vars;
	
	function __construct($lang_code,array $view_vars){
		$this->lang_code = $lang_code;
		$this->view_vars = $view_vars;
	}

	public function validate($str){
		$tokens = token_get_all($str);
		foreach($tokens as $token){
			if($token[0] == 319 && in_array($token[1],self::PROHIBITED)){
				throw new Exception("Found prohibited '$token[1]' token inside of src template");
			}elseif($token[0] == 361){
				throw new Exception("Found prohibited class constructor inside of src template");
			}
		}
	}
	
	public function str_replace_first($haystack,$replace,$needle){
		$pos = mb_strpos($haystack,$needle);
		if ($pos !== false) {
			$res = substr_replace($haystack,$replace,$pos,mb_strlen($needle));
		}
		return $res;
	}
	
	public function getVarsTokens($buf){
		$res =[];
		$pos = 0;
		preg_match_all("~{{[^}]+}}~m",$this->buf,$matches);
		foreach($matches[0] as $token){
			$val = trim(mb_substr($token,2,-2));
			$pos = mb_strpos($this->buf,$token,$pos);
			$res[$pos] = [$token,$val];
			$pos++;
		}
		return $res;
	}
	
	public function parse($buf){
	
		$this->buf = $buf;
		$this->buf = str_replace(["\t","\n","\r"],'',$this->buf);
		$this->buf = preg_replace('/<!--(.*)-->/Uis','',$this->buf);

		$this->validate($this->buf);
		
		$blocks = [];

		$pos = 0;
		preg_match_all("~{{[^}]+}}~m",$this->buf,$matches);
		foreach($matches[0] as $token){
			$val = trim(mb_substr($token,2,-2));
			$pos = mb_strpos($this->buf,$token,$pos);
			$rep = $this->parseVar($val);
			$this->buf = str_replace($token,$rep,$this->buf);
			$pos++;
		}
		
		$this->buf = preg_replace("~{% ?else ?%}~","<?else:?>",$this->buf);
		
		// parse all END tags
		$pos = 0;
		preg_match_all("~{% ?(if|elseif)[^%]+%}~m",$this->buf,$matches);
		foreach($matches[0] as $token){
			$val = trim(mb_substr($token,2,-2));
			$pos = mb_strpos($this->buf,$token,$pos);
			$blocks[$pos] = [self::TAG_IF,$token,$val];
			$pos++;
		}
		
		// parse all FOREACH tags
		$pos = 0;
		preg_match_all("~{% ?i?foreach[^%]+%}~m",$this->buf,$matches);
		foreach($matches[0] as $token){
			$val = trim(mb_substr($token,2,-2));
			$pos = mb_strpos($this->buf,$token,$pos);
			$blocks[$pos] = [self::TAG_FOREACH,$token,$val];
			$pos++;
		}

		// parse all END tags
		$pos = 0;
		preg_match_all("~{% ?end ?%}~m",$this->buf,$matches);
		foreach($matches[0] as $token){
			$pos = mb_strpos($this->buf,$token,$pos);
			$blocks[$pos] = [self::TAG_CLOSE,$token];
			$pos++;
		}

		ksort($blocks);
		
		foreach($blocks as $pos => $block){
			switch($block[0]){
				case self::TAG_CLOSE:
					$scope = array_pop($this->scopes);
					switch($scope[0]){
						case self::SCOPE_FOREACH:
							array_pop($this->scope_vars);
							$this->buf = $this->str_replace_first($this->buf,"<?endforeach;?>",$block[1]);
							break;
						case self::SCOPE_IF:
							$this->buf = $this->str_replace_first($this->buf,"<?endif;?>",$block[1]);
							break;
						default:
							throw new Exception("Invalid scope: $scope");
					}
					break;
				case self::TAG_IF:
					if($block[2][0] === 'e'){
						$rep = $this->parseElseIf($block[2]);
					}else{
						$this->scopes [] = [self::SCOPE_IF,$block,$pos];
						$rep = $this->parseIf($block[2]);
					}
					
					$this->buf = str_replace($block[1],$rep,$this->buf);
					break;
				case self::TAG_FOREACH:
					$this->scopes [] = [self::SCOPE_FOREACH,$block,$pos];
					$rep = $this->parseForeach($block[2]);
					$this->buf = str_replace($block[1],$rep,$this->buf);
					break;
				default:
					throw new Exception("Invalid token: $token");
			}
		}
		
		$this->buf  = str_replace("\t","",$this->buf );
		$this->buf  = preg_replace('!\s+!',' ',$this->buf );
		$this->buf  = trim($this->buf);
		//var_dump($this->buf);die;
		return $this->buf;
	}

	public function parseComment($token,$val){
		$this->buf = str_replace($token,"",$this->buf);
	}
	
	public function parseForeach($token){
		$array = explode(' ',$token);
		array_shift($array);
		$len = count($array);
		
		$bexp = [
			'',
			'',
		];
		if($token[0] === 'i'){
			$token = mb_substr($token,1);
			$bexp = [
				'$i=0;',
				'$i++;',
			];
		}

		$chunks = 0;
		if(mb_strpos($array[0],'|') !== false){
			// array chunks
			$a = explode('|',$array[0]);
			$chunks = $a[1];
			$array[0] = $a[0];
			unset($a);
		}

		switch($len){
			case 3:
				$var = $this->getVar($array[0]);
				$k = $array[2];
				$v = false;
//				$this->scope_vars[] = [
//					'k' => $k,
//					'v' => $v,
//				];

				if($chunks){
					$res = "<?\$_array = array_chunk($var->value,$chunks);";
					$res.= "{$bexp[0]}foreach(\$_array as \$$k):{$bexp[1]}?>";
				}else{
					$res = "<?{$bexp[0]}foreach($var->value as \$$k):{$bexp[1]}?>";
				}

				break;
			case 5:
				$var = $this->getVar($array[0]);
				$k = $array[2];
				$v = $array[4];
//				$this->scope_vars[] = [
//					'k' => $k,
//					'v' => $v,
//				];
				if($chunks){
					$res = "<?\$_array = array_chunk($var->value,$chunks);";
					$res.= "{$bexp[0]}foreach(\$_array as \$$k => \$$v):{$bexp[1]}?>";
				}else{
					$res = "<?{$bexp[0]}foreach($var->value as \$$k => \$$v):{$bexp[1]}?>";
				}
				break;
			default:
				throw new Exception("Invalid foreach: $token");
		}

		return $res;
	}

	public function getVarFromScope($var,$flag=0){
//		foreach($this->scope_vars as $vars){
//			if($vars['k'] == $var){
//				return "\${$vars['k']}";
//			}elseif($vars['v'] == $var){
//				return "\${$vars['v']}";
//			}
//		}
		if($flag & self::VAR_FLAG_SCOPEVAR){
			$res = "\$$var";
		}else{
			$res = "\$this->$var";
		}
		
		return $res;
	}
	
	public function parseVarModifiers(ViewParserVar &$var){
		$val = &$var->value;

		if($val[0] === '!'){
			$var->flag |= self::VAR_FLAG_RAW;
			$val = mb_substr($val,1);
		}

		if($val[0] === '.'){
			$var->flag |= self::VAR_FLAG_SCOPEVAR;
			$val = mb_substr($val,1);
		}
		
		if(($pos = mb_strpos($val,self::VAR_TAG_MOD)) === false){
			return;
		}
		
		$str = mb_substr($val,$pos+1);
		$mods = explode(self::VAR_TAG_MOD,$str);
		foreach($mods as $mod){
			switch($mod){
				case 'r':
					$var->flag |= self::VAR_FLAG_RAW;
					break;
				case 'upper':
					$var->flag |= self::VAR_FLAG_TOUPPER;
					break;
				case 'lower':
					$var->flag |= self::VAR_FLAG_TOLOWER;
					break;
				case 'date':
					$var->flag |= self::VAR_FLAG_DATE;
					break;
				case 'e':
					$var->flag |= self::VAR_FLAG_ESCAPE;
					break;
				case 'count':
					$var->flag |= self::VAR_FLAG_COUNT;
					break;
				default:
					throw new Exception("Unknown modifier '{$mod}' for val: {$var->value}");
			}
		}

		$var->value = mb_substr($val,0,$pos);
	}
	
	public function getVar($val){
		$res = new ViewParserVar();
		$res->value = $val;
		
		$this->parseVarModifiers($res);
		$val = $res->value;
		
		// support string literal and variables in it
		if($val[0] == '"' || $val[0] == "'"){
			if(preg_match_all('/%([^%]+)%/',$val,$matches)){
				$len = count($matches);
				for($i=0;$i<$len;$i++){
					$var = $this->getVar($matches[1][$i]);
					$val = str_replace($matches[0][$i],"{{$var->value}}",$val);
				}
			}
			$res->flag |= self::VAR_FLAG_STRING;
			$res->name = $val;
			$res->value = $val;
			return $res;
		}
	
//		if($flag & self::VAR_FLAG_DOTTED_KEY){
//			$res->base = $val;
//			$res->name = $val;
//			$res->value = "\$this->_vars['{$val}']";
//			return $res;
//		}
		
		if(mb_strpos($val,'.')){
			// dotted array array.sub
			$arr = explode('.',$val);
			$var = array_shift($arr);
			$value = "";
			foreach($arr as $x){
				preg_match('/^([a-zA-Z0-9_]{1}[a-zA-Z0-9_]{0,})/',$x,$matches);
				if(empty($matches[1])){
					throw new Exception("Invalid variable: $x");
				}
				$value .= '["'. $matches[1] .'"]'.mb_substr($x,mb_strlen($matches[1]));
			}
			$res->flag |= self::VAR_FLAG_ARRAY_DOT;
		}elseif(mb_strpos($val,'[')){
			// array var array[
			$arr = explode('[',$val);
			$var = array_shift($arr);
			$value = '['. implode('',$arr);
			$res->flag |= self::VAR_FLAG_ARRAY;
		}elseif(mb_strpos($val,'->')){
			// object var object->
			$arr = explode('->',$val);
			$var = array_shift($arr);
			$value = '->'. implode('->',$arr);
			$res->flag |= self::VAR_FLAG_OBJECT;
		}else{
			// simple var $variable
			$var = $val;
			$value = "";
		}

		$res->base = $var;
		$var = $this->getVarFromScope($var,$res->flag);
		$res->name = $var;
		$res->value = "{$var}{$value}";
		return $res;
	}

	// expand dynamic var from ~_meta_title to "meta title here can be cached easily"
	public function getExpVar($val,$flag=0){

		$val = mb_substr($val,1);
		
		if($val[0] === '!'){
			$flag |= self::VAR_FLAG_RAW;
			$val = mb_substr($val,1);
		}
		$var = $this->getVar($val);
		$extra = mb_substr($var->value,mb_strlen($var->name));
		$viewvar = "\$this->view_vars['{$var->base}']{$extra}";
		
		$isset = false;
		$code = "\$isset = isset($viewvar);";
		$this->validate($code);
		eval($code);

		if(!$isset){
			error_log("[DEBUG] Possible error: $viewvar is empty");
			return '';
		}
		
		if($flag){
			$var->flag |= $flag;
		}

		$rep = false;
		eval("\$rep = $viewvar;");
		if(is_array($rep)){
			error_log("getExpVar : IS ARRAY ERROR: '\$rep = $viewvar;'");
		}
		if(!($var->flag & self::VAR_FLAG_ESCAPE) && $var->flag & self::VAR_FLAG_RAW){
			if($var->flag & self::VAR_FLAG_TOUPPER){
				$rep = mb_strtoupper($rep);
			}elseif($var->flag & self::VAR_FLAG_TOLOWER){
				$rep = mb_strtolower($rep);
			}
		}else{
			if($var->flag & self::VAR_FLAG_TOUPPER){
				$rep = htmlentities(mb_strtoupper($rep),ENT_QUOTES);
			}elseif($var->flag & self::VAR_FLAG_TOLOWER){
				$rep = htmlentities(mb_strtolower($rep),ENT_QUOTES);
			}else{
				$rep = htmlentities($rep,ENT_QUOTES);
			}
		}
		return $rep;
	}
	
	public function parseVar($val){
		
		if($val[0] === '~'){
			//$rep = $this->getExpVar($val,self::VAR_FLAG_RAW);
			$rep = $this->getExpVar($val);
			return $rep;
		}

		$var = $this->getVar($val);
		$this->parseVarModifiers($var);

		if($var->flag & self::VAR_FLAG_DATE){
			if(empty($params)){
				$params = [0=>"'%c'"];
			}
			$param = $params[0];
			if($param[0] == '"' || $param[0] == "'"){
				$pos = mb_strpos($param,$param[0],1);
				if(!$pos){
					throw new Exception("Missing last quote for modifier: {$var->value} param: $param");
				}

				$fmt = mb_substr($param,1,$pos-1);
				$rep = "<?=date({$var->value},\"$fmt\")?>";

			}else{
				throw new Exception("Invalid date format for modifier: {$var->value}");
			}

		}elseif($var->flag & self::VAR_FLAG_RAW){
			if($var->flag & self::VAR_FLAG_TOUPPER){
				$rep = "<?=mb_strtoupper({$var->value})?>";
			}elseif($var->flag & self::VAR_FLAG_TOLOWER){
				$rep = "<?=mb_strtolower({$var->value})?>";
			}else{
				$rep = "<?={$var->value}?>";
			}
		}else{
			if($var->flag & self::VAR_FLAG_TOUPPER){
				$rep = "<?=htmlentities(mb_strtoupper({$var->value}),ENT_QUOTES)?>";
			}elseif($var->flag & self::VAR_FLAG_TOLOWER){
				$rep = "<?=htmlentities(mb_strtolower({$var->value}),ENT_QUOTES)?>";
			}elseif($var->flag & self::VAR_FLAG_COUNT){
				$rep = "<?=count({$var->value})?>";
			}else{
				$rep = "<?=htmlentities({$var->value},ENT_QUOTES)?>";
			}
		}

		return $rep;
	}
	
	// example of IF expression:
	// if test.variable == ' \'  testing' && testing->some.test['shouldwork'].withthistest || x.y.z.finaldot
	public function parseIf($token){
		$tokens = explode(' ',$token);
		array_shift($tokens);
		foreach($tokens as &$token){
			if(is_numeric($token)){
				continue;
			}
			if(empty($token[0]) || $token[0] == "'" || $token[0] == '"'){
				continue;
			}
			$prefix = "";
			if($token[0] === '!'){
				if(isset($token[1]) && $token[1] === '='){
					continue;
				}
				$prefix = "!";
				$token = mb_substr($token,1);
			}
			if($token[0] !== '.' && $token[0] !== '_' && !ctype_alpha($token[0])){
				continue;
			}
			switch($token){
				case 'true':
				case 'false':
				case 'null':
					continue 2;
				default:
					$var = $this->getVar($token);
					$token = "{$prefix}{$var->value}";
			}
		}

		$res = implode('',$tokens);
		$res = "<?if($res):?>";
		return $res;
	}

	public function parseElseIf($token){
		$tokens = explode(' ',$token);
		array_shift($tokens);
		foreach($tokens as &$token){
			if(is_numeric($token)){
				continue;
			}
			if(empty($token[0]) || $token[0] == "'" || $token[0] == '"'){
				continue;
			}
			$prefix = "";
			if($token[0] === '!'){
				if(isset($token[1]) && $token[1] === '='){
					continue;
				}
				$prefix = "!";
				$token = mb_substr($token,1);
			}
			if($token[0] !== '.' && $token[0] !== '_' && !ctype_alpha($token[0])){
				continue;
			}
			switch($token){
				case 'true':
				case 'false':
				case 'null':
					continue 2;
				default:
					$var = $this->getVar($token);
					$token = "{$prefix}{$var->value}";
			}
		}

		$res = implode('',$tokens);
		$res = "<?elseif($res):?>";
		return $res;
	}

}