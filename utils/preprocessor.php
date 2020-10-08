<?php

// This is a simplified version and must be re-written...
class Preprocessor {
	public array $vars = [];
	public function parseIfElseCond(string $buf){
		$if = mb_strpos($buf, "\n#if", 0);
		if($if === FALSE){
			return $buf;
		}

		$ifeol = mb_strpos($buf,"\n",$if+1);
		$varname = mb_substr($buf,$if+4,($ifeol-$if)-4);
		$if_offset = 4 + mb_strlen($varname);
		$varname = strtolower(trim($varname));
		
		
		$endif = mb_strpos($buf, "\n#endif", $if);
		$endif_offset = 7;
		if($endif === FALSE){
			return $buf;
		}

		$else = mb_strpos($buf, "\n#else", $if);
		$else_offset = 0;
		if($else !== FALSE){
			if($else > $endif){
				$else = FALSE;
			}else{
				$else_offset = 6;
			}
		}

		if($else){
			//$if_block = mb_substr($buf, $if+$if_offset, $else-$if - $if_offset);
			$else_block = mb_substr($buf, $else + $else_offset, $endif-$else - $else_offset);
		}else{
			$if_block = mb_substr($buf, $if+$if_offset, $endif-$if-$if_offset);
			//$else_block = '';
		}
		
		$newbuf = "";
		if(isset($this->vars[$varname])){
			// TODO: add comparison feature
			if($this->vars[$varname] == "1"){
				$newbuf .= mb_substr($buf,0,$if);
				$newbuf .= $if_block;
				$newbuf .= mb_substr($buf,$endif+$endif_offset);
			}else{
				$newbuf .= mb_substr($buf,0,$if);
				$newbuf .= $else_block;
				$newbuf .= mb_substr($buf,$endif+$endif_offset);
			}
			$buf = $newbuf;
		}else{
			$newbuf .= mb_substr($buf,0,$if);
			$newbuf .= mb_substr($buf,$endif+$endif_offset);
			$buf = $newbuf;
		}
		var_dump($buf);exit(1);
		return $buf;
	}
}