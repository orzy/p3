<?php
/**
 *  P3_Form
 *
 *  require
 *      * P3_Abstract
 *      * P3_Filter
 *      * P3_Html
 *      * P3_Session
 *
 *  @version 3.2.0
 *  @see     https://github.com/orzy/p3
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
class P3_Form extends P3_Abstract {
	private $_filter = null;
	private $_html = null;
	private $_params = null;
	private $_errors = null;
	private $_rules = null;
	private $_html5 = true;
	
	/**
	 *  @param	array	$params	(Optional) パラメータ名と値の配列
	 *  @param	array	$errors	(Optional) パラメータ名とエラーメッセージの配列
	 *  @param	array	$rules	(Optional) パラメータ名とルールの配列
	 *  @param	boolean	$html5	(Optional) HTML5のrequired属性・pattern属性を使うか
	 */
	public function __construct(array $params = array(), array $errors = array()
	                          , array $rules = array(), $html5 = true) {
		$this->_filter = new P3_Filter();
		$this->_html = new P3_Html();
		$this->_params = $params;
		$this->_errors = $errors;
		$this->_rules = $rules;
		$this->_html5 = $html5;
	}
	/**
	 *  @param	string	$type
	 *  @param	string	$name
	 *  @param	array	$attr	(Optional)
	 *  @return	string
	 */
	public function input($type, $name, array $attr = array()) {
		$def = array('type' => $type, 'name' => $name, 'value' => $this->_param($name));
		return $this->_input($def, $attr);
	}
	/**
	 *  @param	string	$name
	 *  @param	array	$attr	(Optional)
	 *  @return	string
	 */
	public function text($name, array $attr = array()) {
		return $this->input('text', $name, $attr);
	}
	/**
	 *  @param	string	$name
	 *  @param	array	$attr	(Optional)
	 *  @return	string
	 */
	public function hidden($name, array $attr = array()) {
		return $this->input('hidden', $name, $attr);
	}
	/**
	 *  @param	string	$name
	 *  @param	array	$attr	(Optional)
	 *  @return	string
	 */
	public function password($name, array $attr = array()) {
		return $this->_input(array('type' => 'password', 'name' => $name), $attr);
	}
	/**
	 *  @param	string	$name
	 *  @param	string	$label
	 *  @param	array	$attr	(Optional)
	 *  @return	string
	 */
	public function checkbox($name, $label, array $attr = array()) {
		$def = array('type' => 'checkbox', 'name' => $name);
		return $this->_checkable($this->_param($name), $label, $def, $attr);
	}
	/**
	 *  @param	string	$name
	 *  @param	string	$label
	 *  @param	string	$value
	 *  @param	array	$attr	(Optional)
	 *  @return	string
	 */
	public function radio($name, $label, $value, array $attr = array()) {
		$checked = ($value === $this->_param($name));
		$def = array('type' => 'radio', 'name' => $name, 'value' => $value);
		return $this->_checkable($checked, $label, $def, $attr);
	}
	
	private function _checkable($checked, $label, $def, $attr) {
		if ($checked) {
			$def['checked'] = 'checked';
		}
		
		$id = $this->_arrayValue('id', $attr, uniqid('lable-id-'));
		$def['id'] = $id;
		
		$s = $this->_arrayValue($def['name'], $this->_errors);
		$s .= '<label for="' . $id . '">' . $this->_input($def, $attr, false);
		$s .= "$label</label>\n";
		return $s;
	}
	/**
	 *  @param	string	$label	(Optional)
	 *  @param	array	$attr	(Optional)
	 *  @return	string
	 */
	public function submit($label = '送信', array $attr = array()) {
		return $this->_input(array('type' => 'submit', 'value' => $label), $attr);
	}
	/**
	 *  @param	array	$attr	(Optional)
	 *  @return	string
	 */
	public function reset(array $attr = array()) {
		return $this->_input(array('type' => 'reset'), $attr);
	}
	/**
	 *  @param	string	$label
	 *  @param	string	$onclick
	 *  @param	array	$attr	(Optional)
	 *  @return	string
	 */
	public function button($label, $onclick, array $attr = array()) {
		$def = array('type' => 'button', 'value' => $label, 'onclick' => $onclick);
		return $this->_input($def, $attr);
	}
	/**
	 *  @param	string	$name
	 *  @param	integer	$maxFileSize	最大ファイルサイズ（単位はバイト）
	 *  @param	array	$attr	(Optional)
	 *  @return	string
	 */
	public function file($name, $maxFileSize, array $attr = array()) {
		$s = $this->hidden('MAX_FILE_SIZE', array('value' => $maxFileSize * 1024 * 1024));
		return $s . $this->_input(array('type' => 'file', 'name' => $name), $attr);
	}
	
	private function _input(array $def, array $attr, $errorFlg = true) {
		$attr = array_merge(array('class' => null), $attr);
		$attr['class'] .= " input-{$def['type']}";
		return $this->_tag('input', $def, $attr, null, $errorFlg);
	}
	/**
	 *  @param	string	$name
	 *  @param	array	$attr	(Optional)
	 *  @return	string
	 */
	public function textarea($name, array $attr = array()) {
		$def = array('name' => $name);
		return $this->_tag('textarea', $def, $attr, $this->_param($name));
	}
	/**
	 *  @param	string	$name
	 *  @param	array	$options	値とテキストの配列 or テキストの配列
	 *  @param	boolean	$valueFlg	(Optional) option要素にvalue属性を入れるかどうか
	 *  @param	array	$attr	(Optional)
	 *  @return	string
	 */
	public function select($name, array $options, $valueFlg = true, array $attr = array()) {
		$select = $this->_tag('select', array('name' => $name), $attr, 'options');
		
		$selected = $this->_param($name);
		$o = '';
		
		foreach ($options as $value => $label) {
			$optAttr = array();
			
			if ($valueFlg) {
				$optAttr['value'] = $value;
			} else {
				$value = $label;
			}
			
			if ((string)$value === $selected) {
				$optAttr['selected'] = 'selected';
			}
			
			$o .= $this->_html->tag('option', $optAttr, $label);
		}
		
		return str_replace('>options<', ">$o<", $select);
	}
	/**
	 *	フォームのCSRF・二重送信防止用のワンタイムトークン
	 *  @return	string
	 */
	public function token() {
		$session = new P3_Session();
		$attr = array('value' => $session->token(true));
		return $this->hidden(P3_Session::FORM_TOKEN, $attr);
	}
	
	private function _param($name) {
		return $this->_arrayValue($name, $this->_params);
	}
	
	private function _tag($name, array $def, array $attr, $text = null, $errorFlg = true) {
		$error = '';
		$attr = array_merge(array('class' => null), $attr);
		
		if (isset($def['name'])) {
			$attr['class'] .= " $name-{$def['name']}";
			
			if ($name === 'input' || $name === 'textarea') {
				$rules = $this->_arrayValue($def['name'], $this->_rules);
				
				if ($rules) {
					foreach ($rules as $rule => $option) {
						list($class, $arr) = $this->_rule($rule, $option);
						$attr['class'] .= $class;
						$def = array_merge($def, $arr);
					}
				}
			}
			
			if ($errorFlg) {
				$error = $this->_arrayValue($def['name'], $this->_errors);
				
				if ($error) {
					$attr['class'] .= ' error';
				}
			}
		}
		
		$attr['class'] = ltrim($attr['class']);
		
		return $error . $this->_html->tag($name, array_merge($def, $attr), $text);
	}
	
	private function _rule($rule, array $option) {
		list($param, $msg) = $option;
		$class = '';
		$attr = array();
		
		switch ($rule) {
			case 'required':
				if ($this->_html5) {
					$attr['required'] = 'required';
				}
				
				break;
			case 'type':
				switch ($param) {
					case 'alpha_number':
					case 'alpha_number_hyphen':
					case 'number_hyphen':
						$class = ' han';
						break;
					case 'number':
						$class = ' han';
						break;
					case 'integer':
					case 'integer_minus':
					case 'float':
					case 'float_minus':
						$class = ' number';
						break;
				}
				
				if ($this->_html5) {
					list($attr['pattern'], $defMsg) = $this->_filter->pattern($param);
					$msg = $msg ? $msg : $defMsg;
				}
				
				break;
			case 'max_length':
				$attr['maxlength'] = $param;
				break;
			case 'pattern':
				if ($this->_html5) {
					$attr['pattern'] = $param;
				}
				
				break;
		}
		
		if ($msg) {
			$attr['title'] = $msg;
		}
		
		return array($class, $attr);
	}
	/**
	 *  入力可能な文字数の表示文を取得する
	 *  @param	string	$name
	 *  @return	string
	 */
	public function lengthNotice($name) {
		$rules = $this->_rules[$name];
		$max = $rules['max_length'][0];
		
		if (isset($rules['min_length'][0])) {
			return $rules['min_length'][0] . "～{$max}字";
		} else {
			return "{$max}字以内";
		}
	}
}
