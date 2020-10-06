<?php

class btngrp{
	public static $has_data = false;
	public static $data = array(
		'top'=>[],
		'bottom'=>[],
	);

	public static function addLink($label,$group=0,$pos='top',$classname,$link){
		$id = str_replace(' ','',$label);
		self::$data[$pos][$group][$id] = [
			'type' => 'btn-link',
			'pos' => $pos,
			'group' => $group,
			'link' => $link,
			'label' => $label,
			'id' => $id,
			'classname' => $classname,
		];
		self::$has_data = true;
	}

	public static function addJsClick($label,$group=0,$pos='top',$classname,$onclick){
		$id = str_replace(' ','',$label);
		self::$data[$pos][$group][$id] = [
			'type' => 'btn-js-click',
			'pos' => $pos,
			'group' => $group,
			'link' => $onclick,
			'label' => $label,
			'classname' => $classname,
		];
		self::$has_data = true;
	}

	public static function addFormId($label,$group=0,$pos='top',$classname,$form_id){
		$id = str_replace(' ','',$label);
		self::$data[$pos][$group][$id] = [
			'type' => 'btn-form-id',
			'pos' => $pos,
			'group' => $group,
			'link' => $form_id,
			'label' => $label,
			'classname' => $classname,
		];
		self::$has_data = true;
	}

	public static function addModal($label,$group=0,$pos='top',$classname,$link){
		$id = str_replace(' ','',$label);
		self::$data[$pos][$group][$id] = [
			'type' => 'btn-modal',
			'pos' => $pos,
			'group' => $group,
			'link' => $link,
			'label' => $label,
			'id' => $id,
			'classname' => $classname,
		];
		self::$has_data = true;
	}

	public static function addForm($label,$group=0,$pos='top',$classname,$action,$method,array $inputs=array()){
		$id = str_replace(' ','',$label);
		self::$data[$pos][$group][$id] = [
			'type' => 'btn-form',
			'pos' => $pos,
			'group' => $group,
			'label' => $label,
			'id' => $id,
			'classname' => $classname,
			'inputs' => $inputs,
			'link' => $action,
			'method' => $method,
		];
		self::$has_data = true;
	}

	protected static function getHtml(array $buttons){
		if(empty($buttons)) {
			return false;
		}

		//$res ='<div class="btn-toolbar" role="toolbar" aria-label="...">';
		$res = '';
		foreach($buttons as $button){

			$res .= '<div class="btn-group" role="group" aria-label="...">';
			foreach($button as $name=>$array){
				$label = htmlentities($array['label'],ENT_QUOTES);
				$class = $array['classname'];
				$link = $array['link'];
				if($array['type'] === 'btn-form'){
					if(empty($array['inputs'])){
						continue;
					}
					$res .= '<form action="'.$link.'" method="'.$array['method'].'">';
					foreach($array['inputs'] as $k=>$v){
						$k = htmlentities($k,ENT_QUOTES);
						$v = htmlentities($v,ENT_QUOTES);
						$res .= '<input type="hidden" name="'.$k.'" value="'.$v.'">';
					}
					$res .= '<button type="submit" class="btn '.$class.'">'.$label.'</button></form>';
				}elseif($array['type'] === 'btn-link'){

					$res .= '<a class="btn '.$class.'" href="'.$link.'">'.$label.'</a>';

				}elseif($array['type'] === 'btn-modal'){
					$res .= '<button class="btn '.$class.'" data-toggle="modal" ';
					$res .= 'data-target="#'.$link.'">'.$label.'</button>';

				}elseif($array['type'] === 'btn-js-click'){
					$res .= '<button class="btn '.$class.'" onclick="'.$link.'">'.$label.'</button>';

				}elseif($array['type'] === 'btn-form-id'){
					$res .= '<button class="btn '.$class.'" ';
					$res .= 'onclick="_formSubmit(\''.$link.'\');return false;">'.$label.'</button>';
				}

			}
			$res .= '</div>';
		}
		//$res .= '</div>';
		return $res;
	}

	public static function toArray(){
		$res = [
			'top'=>self::getHtml(self::$data['top']),
			'bottom'=>self::getHtml(self::$data['bottom']),
		];

		return $res;
	}
}

class FormFields implements ArrayAccess {
	public $data = [];

	public function offsetSet($k,$value) {
		if(is_array($value)){
			switch($value[0]){
				case 'email':
				case 'password':
				case 'phone':
				case 'number':
				case 'text':
					$this->data[$k] = new FormInput($value[1],$value[2],$value[3],$value[4]??false);
					$this->data[$k]['type'] = $value[0];
					break;
				case 'textarea':
					$this->data[$k] = new FormTextArea($value[1],$value[2],$value[3],$value[4]??false);
					break;
				case 'select':
					$this->data[$k] = new FormSelect($value[1],$value[2],$value[3],$value[4]??false);
					break;
				case 'selects':
					$this->data[$k] = new FormSelectMultiple($value[1],$value[2],$value[3],$value[4]??false);
					break;
				case 'dropdown':
					$this->data[$k] = new FormDropdownBtn($value[1],$value[2],$value[3],$value[4]??false);
					break;
				case 'checkbox':
					$this->data[$k] = new FormCheckbox($value[1],$value[2],$value[3]??false);
					break;
				case 'radio':
					$this->data[$k] = new FormRadio($value[1],$value[2],$value[3],$value[4]??false);
					break;
				case 'radio-inline':
					$this->data[$k] = new FormRadioInline($value[1],$value[2],$value[3],$value[4]??false);
					break;
				case 'file':
					$this->data[$k] = new FormFile($value[1],$value[2],
						$value[3]??false,$value[4]??false);
					break;
				default:
					throw new Exception("Field type invalid: {$value[0]}");
			}
		}
	}
	
	public function offsetExists($k) {
		return isset($this->data[$k]);
	}

	public function offsetUnset($k) {
		unset($this->data[$k]);
	}

	public function offsetGet($k) {
		return isset($this->data[$k]) ? $this->data[$k] : null;
	}
}

class FormField implements ArrayAccess {
	public $data = array(
		'name'=>'',
		'value'=>'',
		'label'=>'',
		'id'=>'',
		'desc'=>'',
		'placeholder'=>'',
		'class'=>'form-control',
		'required'=>false,
		'disabled'=>false,
		'checked'=>false,
		'error'=>false,
		'groupclass'=>'',
		'classname'=>'',
		'html'=>'',
	);

	public function __construct($type,$name,$value,$label=false,$required=false){
		$this->data['type'] = $type;
		$this->data['name'] = $name;
		$this->data['value'] = $value;
		$this->data['required'] = $required;
		if(!empty($label)){
			if(is_array($label)){
				$this->data['label'] = $this->val($label);	
			}else{
				$this->data['label'] = $label;
			}
		}
	}

	public function offsetSet($k,$value) {
		$this->data[$k] = $value;
	}

	public function offsetExists($k) {
		return isset($this->data[$k]);
	}

	public function offsetUnset($k) {
		unset($this->data[$k]);
	}

	public function offsetGet($k) {
		switch($k){
			case 'groupclass'; // todo: remove for next version
				$str = 'form-group';
				if(isset($this->data['required'])){
					$str .= ' required';
				}
				if(isset($this->data['hide'])) {
					$str .= ' hide';
				}
				$str .= "<?=isset(\$this->_errorfields['{$this->data['name']}'])?' has-error':''?>";
				return $str;
			case 'classname';
				$str = '';
				if($this->data['required']){
					$str .= ' required';
				}
				if(isset($this->data['hide'])) {
					$str .= ' hide';
				}
				$str .= "<?=isset(\$this->_errorfields['{$this->data['name']}'])?' has-error':''?>";
				return $str;
			case 'html':
				$str = $this->__toString();
				return $str;	
			default:
				return isset($this->data[$k]) ? $this->data[$k] : null;
		}
	}
	
	public function val($val){
		$array = explode('/',$val[0]);
		switch($array[0]){
			case 'req':
				$var = "\$_REQUEST[\"{$array[1]}\"]";
				$res = "<?=isset($var)?htmlentities($var,ENT_QUOTES):''?>";
				break;
			case 'get':
				$var = "\$_GET[\"{$array[1]}\"]";
				$res = "<?=isset($var)?htmlentities($var,ENT_QUOTES):''?>";
				break;
			case 'post':
				$var = "\$_POST[\"{$array[1]}\"]";
				$res = "<?=isset($var)?htmlentities($var,ENT_QUOTES):''?>";
				break;
			case 'this':
				$var = "\$this->{$array[1]}";
				$res = "<?=isset($var)?htmlentities($var,ENT_QUOTES):''?>";
				break;
			case 'lang':
				$res = lang::val($array[1]);
				//$res = htmlentities($res,ENT_QUOTES);
				break;
			case 'var':
				if(isset(v::$vars[$array[1]])){
					$res = v::$vars[$array[1]];
					//$res = htmlentities(view::$vars[$array[1]],ENT_QUOTES);
				}else{
					$res = '';
				}
				break;
			default:
				if(is_array($val)){
					$val = var_export($val,true);
				}
				throw new Exception("invalid val for val: {$val}");
		}
		
		return $res;
	}

	// compare array value with str
	// useful for select options, if(currentIndex === 'test') then SELECT INDEX
	public function selected($array,$str){
		$val = explode('/',$array[0]);
		switch($val[0]){
			case 'req':
				$var = "\$_REQUEST[\"{$val[1]}\"]";
				break;
			case 'get':
				$var = "\$_GET[\"{$val[1]}\"]";
				break;
			case 'post':
				$var = "\$_POST[\"{$val[1]}\"]";
				break;
			case 'this':
				$var = "\$this->{$val[1]}";
				break;
			case 'lang':
				$var = lang::val($val[1]);
				$var = "'{$var}'";
				break;
			case 'var':
				$var = v::$vars[$val[1]] ?? '';
				$var = "'{$var}'";
				break;
			default:
				$msg = "invalid val: {$val[0]}";
				throw new Exception($msg);
		}
		
		$res = "<?={$var}=='{$str}'?' selected':''?>";
		return $res;
	}

	public function val_checked($val){
		$array = explode('/',$val[0]);
		switch($array[0]){
			case 'req':
				$var = "\$_REQUEST[\"{$array[1]}\"]";
				$res = "<?=isset($var)?'checked':''?>";
				break;
			case 'get':
				$var = "\$_GET[\"{$array[1]}\"]";
				$res = "<?=isset($var)?'checked':''?>";
				break;
			case 'post':
				$var = "\$_POST[\"{$array[1]}\"]";
				$res = "<?=isset($var)?'checked':''?>";
				break;
			case 'this':
				$var = "\$this->{$array[1]}";
				$res = "<?=isset($var)?'checked':''?>";
				break;
			default:
				if(is_array($val)){
					$val = var_export($val,true);
				}
				throw new Exception("invalid val_checked for val: {$val}");
		}

		return $res;
	}
}

class FormInput extends FormField{

	public function __construct($name,$value,$label,$required=false){
		parent::__construct('text',$name,$value,$label,$required);
	}

	public function __toString(){
		
		$required = $this->data['required']?' required':'';
		$disabled = $this->data['disabled']?' disabled':'';

		$attr = '';
		$array = ['type','id','name','value','class','placeholder','step','min','max','autocomplete'];
		foreach($array as $k=>$v){
			if(empty($this->data[$v])){
				continue;
			}
			
			$val = $this->data[$v];
			if(is_array($val)){
				$val = $this->val($val);
				$attr .= " {$v}=\"{$val}\"";
				continue;
			}
			
			$val = htmlentities($val,ENT_QUOTES);
			$attr .= " {$v}=\"{$val}\"";
		}
	
		$res = "<input{$attr}{$disabled}{$required}>";
		if(!empty($this->data['desc'])){
			$desc = htmlentities($this->data['desc'],ENT_QUOTES);
			$res .= "<p class=\"help-block\">{$desc}</p>";
		}
		return $res;
	}

}

class FormTextArea extends FormField{

	public function __construct($name,$value,$label,$required=false){
		parent::__construct('textarea',$name,$value,$label,$required);
	}

	public function __toString(){
		
		$required = $this->data['required']?' required':'';
		$disabled = $this->data['disabled']?' disabled':'';

		$attr = '';
		$array = array('type','id','name','rows','class','placeholder');
		foreach($array as $k=>$v){
			if(empty($this->data[$v])){
				continue;
			}

			$val = $this->data[$v];
			if(is_array($val)){
				$val = $this->val($val);
				$attr .= " {$v}=\"{$val}\"";
				continue;
			}
			
			$val = htmlentities($val,ENT_QUOTES);
			$attr .= " {$v}=\"{$val}\"";
		}

		if(is_array($this->data['value'])){
			$value = $this->val($this->data['value']);
		}else{
			$value = htmlentities($this->data['value'],ENT_QUOTES);
		}
		
		$res = "<textarea{$attr}{$disabled}{$required}>{$value}</textarea>";
		if(!empty($this->data['desc'])){
			$desc = htmlentities($this->data['desc'],ENT_QUOTES);
			$res .= "<p class=\"help-block\">{$desc}</p>";
		}
		return $res;
	}

}

class FormSelectOption {
	public $data = array();
	public function __construct($label,$value,$selected){
		$this->data['value']=$value;
		$this->data['label']=$label;
		$this->data['selected']=$selected;
		$this->data['attr'] = [];
	}

	public function addAttribute($name,$value){
		$this->data['attr'][$name] = $value;
	}
}

class FormSelect extends FormField{

	public $options;

	public function __construct($name,$value,$label,$required=false){
		parent::__construct('select',$name,$value,$label,$required);
	}

	public function addOptions(array $array){
		foreach($array as $data){
			$value = $data['value'];
			$label = $data['label'];
			if(isset($data['selected'])){
				$selected = (bool) $data['selected'];
			}else{
				$selected = (bool) ($this['value'] == $value);
			}
			$this->options[$value] = array(
				'value'=>$value,
				'label'=>$label,
				'selected'=>$selected,
			);
		}
	}

	public function addOption($value,$label,$selected=null){
		if(is_null($selected)){
			$selected = (bool) ($this['value'] == $value);
		}else{
			$selected = (bool) $selected;
		}
		$this->options[$value] = array(
			'value'=>$value,
			'label'=>$label,
			'selected'=>$selected,
		);
	}

	public function getSelectedOption(){
		$key = $this['value'] ;
		if(isset($this->options[$key])){
			$res = $this->options[$key];
		}else{
			$res = reset($this->options);
		}
		return $res;
	}

	public function __toString(){
		$attr = '';
		$required = $this['required']?' required':'';
		$disabled = $this['disabled']?' disabled':'';
		$multiple = $this['multiple']?' multiple':'';
		if(!$multiple){
			// required has some rules to follow, read the docs
			$required = '';
		}
		$array = array('id','name','size','class','placeholder');
		foreach($array as $k=>$v){
			if(!empty($this->data[$v])){
				$value = htmlentities($this->data[$v],ENT_QUOTES);
				$attr .= " {$v}=\"{$value}\"";
			}
		}

		$res = "<select{$attr}{$disabled}{$required}{$multiple}>";
		if($this->options){
			foreach($this->options as $o){
				$value = htmlentities($o['value'],ENT_QUOTES);
				$label = htmlentities($o['label'],ENT_QUOTES);
				
				if(is_array($this->data['value'])){
					$selected = $this->selected($this->data['value'],$o['value']);
				}else{
					$selected = $o['selected']?' selected':'';
				}
				
				$attr = '';
				if(!empty($o['_data'])){
					foreach($o['_data'] as $k=>$v){
						if(!strlen($v)) {
							continue;
						}
						$_val = htmlentities($v,ENT_QUOTES);
						$attr .= " {$k}=\"{$_val}\"";
					}
				}

				$res .= "<option value=\"{$value}\"{$attr}{$selected}>{$label}</option>";
			}
		}
		$res .= "</select>";
		if(!empty($this->data['desc'])){
			$desc = htmlentities($this->data['desc'],ENT_QUOTES);
			$res .= "<p class=\"help-block\">{$desc}</p>";
		}
		return $res;
	}

}

class FormSelectMultiple extends FormSelect{

	public function __construct($name,$label,$required=false){
		parent::__construct($name,'',$label,$required);
		$this['multiple'] = true;
	}

	public function &addOption($value,$label,$selected=null){
		$selected = (bool) $selected;
		$this->options[$value] = array(
			'value'=>$value,
			'label'=>$label,
			'selected'=>$selected,
		);
	}
}

class FormDropdownBtn extends FormSelect{
	public function __toString(){
		$attr = '';
		$required = $this->data['required']?' required':'';
		$disabled = $this->data['disabled']?' disabled':'';
		$multiple = $this->data['multiple']?' multiple':'';
		if(!$multiple){
			// required has some rules to follow, read the docs
			$required = '';
		}
		$array = array('id','name','size','class','placeholder');
		foreach($array as $k=>$v){
			if(!empty($this->data[$v])){
				$value = htmlentities($this->data[$v],ENT_QUOTES,'UTF-8');
				$attr .= " {$v}=\"{$value}\"";
			}
		}

		$res = "<button type=\"button\" class=\"btn btn-default btn-sm dropdown-toggle\"";
		$res .=" {$attr}{$disabled}{$required} data-toggle=\"dropdown\"";
		$res .=" aria-haspopup=\"true\" aria-expanded=\"false\">";
		$res .=" {$this['label']} <span class=\"caret\"></span></button><ul class=\"dropdown-menu\">";
		if($this->options){
			foreach($this->options as $o){
				$value = htmlentities($o['value'],ENT_QUOTES,'UTF-8');
				$label = htmlentities($o['label'],ENT_QUOTES,'UTF-8');
				$selected = $o['selected']?' selected':'';

				$attr = '';
				if(!empty($o['_data'])){
					foreach($o['_data'] as $k=>$v){
						if(!strlen($v)) {
							continue;
						}
						$_val = htmlentities($v,ENT_QUOTES,'UTF-8');
						$attr .= " {$k}=\"{$_val}\"";
					}
				}

				$res .= "<li {$attr}{$selected}><a href=\"\">{$label}</a></li>";
			}
		}
		$res .= "</ul>";
		if(!empty($this->data['desc'])){
			$desc = htmlentities($this->data['desc'],ENT_QUOTES,'UTF-8');
			$res .= "<p class=\"help-block\">{$desc}</p>";
		}
		return $res;
	}
}

class FormCheckbox extends FormField{

	public $checked;

	public function __construct($name,$label,$checked=false){
		parent::__construct('checkbox',$name,'1',$label,false);
		$this->data['checked'] = $checked;
		$this['class'] = '';
	}

	public function __toString(){
		$attr = '';
		$required = $this->data['required']?' required':'';
		$disabled = $this->data['disabled']?' disabled':'';
		$array = array('type','id','name','value','class','placeholder');
		foreach($array as $k=>$v){
			if(!empty($this->data[$v])){
				$val = $this->data[$v];
				if(is_array($val)){
					$val = $this->val($val);
					$attr .= " {$v}=\"{$val}\"";
					continue;
				}
				$value = htmlentities($val,ENT_QUOTES);
				$attr .= " {$v}=\"{$value}\"";
			}
		}

		if(is_array($this->data['checked'])){
			$checked = $this->val_checked($this->data['checked']);
		}else{
			$checked = (bool) $this->data['checked'];
			$checked = $checked?'checked':'';
		}
		
		$res = "<input{$attr}{$disabled}{$required}{$checked}>";
		return $res;
	}

}

class FormRadio extends FormField{

	public function __construct($name,$value,$label,$required=false){
		parent::__construct('radio',$name,$value,$label,false);
		$this['required'] = (bool) $required;
		$this['class'] = '';
	}

	public function &addOption($value,$label,$checked=null){
		if(is_null($checked)){
			$checked = (bool) ($this['value'] == $value);
		}else{
			$checked = (bool) $checked;
		}
		$this->options[$value] = array(
			'value'=>$value,
			'label'=>$label,
			'checked'=>$checked,
		);
		return $this->options[$value];
	}

	public function getSelectedOption(){
		$key = $this['value'] ;
		if(isset($this->options[$key])){
			$res = $this->options[$key];
		}else{
			$res = reset($this->options);
		}
		return $res;
	}

	public function getAttributes(){
		$res = '';
		$array = array('type','id','name','value','class','placeholder');
		foreach($array as $k=>$v){
			if(empty($this->data[$v])){
				continue;
			}

			$val = $this->data[$v];
			if(is_array($val)){
				$val = $this->val($val);
				$res .= " {$v}=\"{$val}\"";
				continue;
			}

			$val = htmlentities($val,ENT_QUOTES);
			$res .= " {$v}=\"{$val}\"";
		}
		return $res;
	}

	public function __toString(){
		
		$required = $this->data['required']?' required':'';
		$disabled = $this->data['disabled']?' disabled':'';
		
		$res = '';
		if($this->options){
			$option = $this->getSelectedOption();
			foreach($this->options as $o){
				if($o['value'] === $option['value']){
					$o['checked'] = true;
				}
				$value = htmlentities($o['value'],ENT_QUOTES);
				$label = htmlentities($o['label'],ENT_QUOTES);
				$checked = $o['checked']?' checked':'';
				$res .= "<div class=\"radio\"><label>";

				$attr = '';
				if(!empty($o['_data'])){
					foreach($o['_data'] as $k=>$v){
						if(empty($v)) {
							continue;
						}
						$_val = htmlentities($v,ENT_QUOTES);
						$attr .= " {$k}=\"{$_val}\"";
					}
				}

				$res .= "<input type=\"radio\" name=\"{$this['name']}\" value=\"{$value}\" {$attr}";
				$res .= "{$attr}{$disabled}{$required}{$checked}>{$label}";
				if(!empty($o['desc'])){
					$desc = htmlentities($o['desc'],ENT_QUOTES);
					$res .= "<p class=\"help-block\">{$desc}</p>";
				}
				$res .= "</label></div>";
			}
		}
		return $res;
	}

}

class FormRadioInline extends FormRadio{

	public function __construct($name,$value,$label,$required=false){
		parent::__construct($name,$value,$label,$required);
	}

	public function __toString(){
		$attr = $this->getAttributes();
		$required = $this['required']?' required':'';
		$disabled = $this['disabled']?' disabled':'';
		if($this['error']){
			$this['class'] .= ' has-error';
		}

		$res = '';
		if($this->options){
			$option = $this->getSelectedOption();
			foreach($this->options as $o){
				if($o['value'] === $option['value']){
					$o['checked'] = true;
				}
				$value = htmlentities($o['value'],ENT_QUOTES);
				$label = htmlentities($o['label'],ENT_QUOTES);
				$checked = $o['checked']?' checked':'';
				$res .= "<div class=\"radio-inline\"><label>";
				$res .= "<input type=\"radio\" name=\"{$this['name']}\" value=\"{$value}\"";
				$res .= "{$attr}{$disabled}{$required}{$checked}>{$label}";
				$res .= "</label></div>";
			}
		}
		return $res;
	}

}

class FormFile extends FormField{
	
	public $multiple;

	public function __construct($name,$label,$multiple=false,$required=false){
		parent::__construct('file',$name,'',$label,$required);
		$this->multiple = (bool) $multiple;
		$this['class'] = '';
	}

	public function __toString(){
		$attr = '';
		$required = $this['required']?' required':'';
		$disabled = $this['disabled']?' disabled':'';
		if($this['error']){
			$this['class'] .= ' has-error';
		}
		$array = array('type','id','name','value','class','placeholder');
		foreach($array as $k=>$v){
			if(!empty($this->data[$v])){
				$value = htmlentities($this->data[$v],ENT_QUOTES);
				$attr .= " {$v}=\"{$value}\"";
			}
		}
		$multiple = $this->multiple?'multiple':'';
		$res = "<input{$attr}{$disabled}{$required}{$multiple}>";
		if(!empty($this->data['desc'])){
			$desc = htmlentities($this->data['desc'],ENT_QUOTES);
			$res .= "<p class=\"help-block\">{$desc}</p>";
		}
		return $res;
	}
	
}
