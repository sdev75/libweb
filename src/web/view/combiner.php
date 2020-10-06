<?php

class ViewCombiner{
	public $buf;
	private $_includes = [];
	public $includes = [];
	private function parseIncludes($filename,string $inpath,$data=false){
		$buf = file_get_contents($filename);
		if($data){
			$this->buf = str_replace($data[0],$buf,$this->buf);
		}else{
			$this->buf .= $buf;
		}

		if(preg_match_all('/{%[ ]*include[ ]*"([^"]+)"[ ]*%}/',$buf,$matches)){
			$len = count($matches[0]);
			for($i=0;$i<$len;$i++){
				$filename = "$inpath/shared/{$matches[1][$i]}.phtml";
				$filename = str_replace('..','',$filename);
				if(!is_file($filename)){
					throw new Exception("Combine failed due to missing file: $filename");
				}
				$name = $matches[1][$i];
				$data = [
					$matches[0][$i],
					$name,
					$filename,
				];
				$this->_includes[] = $data;
				$this->includes[$name] = true;
				$this->parseIncludes($filename,$inpath, $data);
			}
		}
	}

	public function parse(string $filename, string $inpath){
		$this->buf = '';
		$this->_includes = [];
		$this->includes = [];
		$this->parseIncludes($filename, $inpath);
		return $this->buf;
	}
}
