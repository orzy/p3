<?php
/**
 *  P3_Filter
 *
 *  require
 *      * P3_Abstract
 *
 *  @version 3.3.2
 *  @see     https://github.com/orzy/p3
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
class P3_Filter extends P3_Abstract {
	private $_rules = array();
	private $_errors = array();
	
	/**
	 *  必須入力チェック
	 *  @param	string	$key	パラメータ名
	 *  @param	mixed	$value
	 *  @param	string	$msg	エラー時のメッセージ
	 */
	public function required($key, $value, $msg) {
		$this->_rules[$key]['required'] = array(true, $msg);
		
		if ($this->_isBlank($value)) {
			$this->error($key, $msg ? $msg : '入力してください');
		}
	}
	/**
	 *  パラメータの変換と入力チェック
	 *  @param	mixed	$value
	 *  @param	string	$rule	変換・チェックのルール
	 *  @param	mixed	$param	(Optional) ルールに関する指定値
	 *  @param	string	$key	(Optional) パラメータの名前
	 *  @param	string	$msg	(Optional) エラー時のメッセージ
	 *  @return	mixed	変換後の値
	 */
	public function rule($value, $rule, $param = null, $key = '', $msg = '') {
		$this->_rules[$key][$rule] = array($param, $msg);
		
		if ($this->_isBlank($value)) {
			if ($rule === 'default') {
				return $param;
			} else {
				return '';
			}
		}
		
		$error = '';
		
		switch ($rule) {
			case 'default':
				break;
			case 'trim':
				$value = trim($value);
				break;
			case 'type':
				list($value, $error) = $this->_ruleOfType($value, $param);
				break;
			case 'char_width':
				switch ($param) {
					case 'han':
						$value = mb_convert_kana($value, 'askh', self::ENCODING);
						break;
					case 'zen':
						$value = mb_convert_kana($value, 'ASKV', self::ENCODING);
						break;
					default:
						throw new InvalidArgumentException("未定義のparam '$param'");
				}
				
				break;
			case 'char_height':
				switch ($param) {
					case 'lower':
						$value = mb_strToLower($value, self::ENCODING);
						break;
					case 'upper':
						$value = mb_strToUpper($value, self::ENCODING);
						break;
					default:
						throw new InvalidArgumentException("未定義のparam '$param'");
				}
				
				break;
			case 'min_length':
				$len = mb_strlen($value, self::ENCODING);
				
				if ($len < $param) {
					$error = $param . "文字以上で入力してください （現在 $len 字）";
				}
				
				break;
			case 'max_length':
				$len = mb_strlen($value, self::ENCODING);
				
				if ($len > $param) {
					$error = $param . "文字以内で入力してください （現在 $len 字）";
				}
				
				break;
			case 'pattern':
				if (!preg_match('/^' . $param . '\z/', $value)) {
					$error = '正しい形式で入力してください';
				}
				
				break;
			case 'zero':
				$value = str_pad($value, $param, '0', STR_PAD_LEFT);
				break;
			case 'youbi':
				if ($value instanceof DateTime) {
					$dt = $value;
				} else {
					$dt = new DateTime($value);
				}
				
				$days = array('日', '月', '火', '水', '木', '金', '土');
				$value = $days[$dt->format('w')];
				
				if (is_null($param)) {
					$value = $dt->format("Y/m/d ($value)");
				}
				break;
			default:
				throw new InvalidArgumentException("未定義のrule '$rule'");
		}
		
		if ($error) {
			$this->error($key, $msg ? $msg : $error);
		}
		
		return $value;
	}
	
	private function _ruleOfType($value, $type) {
		$value = trim(mb_convert_kana($value, 'asKV', self::ENCODING));
		$value = strtr($value, array('．' => '.', '－' => '-'));
		list($pattern, $msg) = $this->pattern($type);
		$pattern = '/^' . str_replace('/', '\\/', $pattern) . '\z/';
		
		switch ($type) {
			case 'date':
				if (preg_match($pattern, $value)) {
					list($y, $m, $d) = explode('/', $value);
					
					if (checkdate($m, $d, $y)) {
						$m = str_pad($m, 2, '0', STR_PAD_LEFT);
						$d = str_pad($d, 2, '0', STR_PAD_LEFT);
						$value = "$y/$m/$d";
						$msg = '';
					} else {
						$msg = '正しい日付を入力してください';
					}
				}
				
				break;
			case 'alpha_number':
				if (ctype_alnum($value)) {
					$msg = '';
				}
				
				break;
			case 'number':
				if (ctype_digit($value)) {
					$msg = '';
				}
				
				break;
			case 'alpha_number_hyphen':
			case 'number_hyphen':
				if (preg_match($pattern, $value)) {
					$msg = '';
				}
				
				break;
			case 'integer':
			case 'integer_minus':
			case 'float':
			case 'float_minus':
				if (is_numeric($value)) {
					if (preg_match($pattern, $value)) {
						$value *= 1;
						$msg = '';
					} else {
						$msg = '正しい数字を入力してください';
					}
				}
				
				break;
		}
		
		return array($value, $msg);
	}
	/**
	 *  typeごとの正規表現パターン（HTML5互換）とエラーメッセージ
	 *  @param	string	$type
	 *  @return	array
	 */
	public function pattern($type) {
		switch ($type) {
			case 'regular':
				return array(null, '');
			case 'date':
				return array(
					'[0-9]{4}/(0?[1-9]|1[0-2])/(0?[1-9]|[12][0-9]|3[01])',
					'日付を入力してください（例 ' . date('Y') . '/01/01）'
				);
				break;
			case 'alpha_number':
				return array('[a-zA-Z0-9]+', '英数字で入力してください');
			case 'alpha_number_hyphen':
				return array(
					'[a-zA-Z0-9](-?[a-zA-Z0-9])*',
					'英数字かハイフンで入力してください'
				);
			case 'number_hyphen':
				return array('[0-9](-?[0-9])*', '数字かハイフンで入力してください');
			case 'number':
				$pattern = '[0-9]+';
				break;
			case 'integer':
				$pattern = '(0|[1-9][0-9]*)';
				break;
			case 'integer_minus':
				$pattern = '(0|-?[1-9][0-9]*)';
				break;
			case 'float':
				$pattern = '(0|[1-9][0-9]*)(\\.[0-9]+)?';
				break;
			case 'float_minus':
				$pattern = '(0|-?[1-9][0-9]*)(\\.[0-9]+)?';
				break;
			default:
				throw new InvalidArgumentException("未定義のtype '$type'");
		}
		
		return array($pattern, '数字で入力してください');
	}
	/**
	 *  アップロードされたファイルを取得する
	 *  @param	string	$key	パラメータ名
	 *  @param	boolean	$required	(Optional) 必須かどうか
	 *  @param	mixed	$savePath	(Optional) ファイルを保存するパス
	 *  @param	string	$msg	(Optional) 必須入力の場合のエラーメッセージ
	 *  @return	mixed	$savePathあり：処理結果（true/false）、なし：ファイルの中身/false
	 */
	public function file($key, $required = true, $savePath = '', $msg = null) {
		if ($required) {
			$this->_rules[$key]['required'] = array(true, $msg);
		}
		
		$file = $this->_arrayValue($key, $_FILES);
		$errorMsg = '';
		
		if ($file) {
			$error = $this->_arrayValue('error', $file);
			
			switch ($error) {
				case UPLOAD_ERR_OK:
					break;
				case UPLOAD_ERR_INI_SIZE:
				case UPLOAD_ERR_FORM_SIZE:
					$this->error($key, 'ファイルサイズが大きすぎます');
					return false;
				case UPLOAD_ERR_NO_FILE:
					$errorMsg = 'ファイルを選択してください';
					break;
				default:
					throw new RuntimeException("ファイルのアップロードに失敗しました $error");
			}
		} else {
			$errorMsg = 'ファイルがアップロードされていません';
		}
		
		if ($errorMsg) {	// ファイルなし
			if ($required) {
				$this->error($key, $msg ? $msg : $errorMsg);
				return false;
			} else {
				return $savePath ? true : '';
			}
		}
		
		if (!is_uploaded_file($file['tmp_name'])) {
			throw new RuntimeException('アップロードされたファイルがありません ' . $file['tmp_name']);
		}
		
		if ($savePath) {
			if (move_uploaded_file($file['tmp_name'], $savePath)) {
				return true;
			} else {
				throw new RuntimeException("ファイルを保存できませんでした $savePath");
			}
		} else {
			return file_get_contents($file['tmp_name']);
		}
	}
	/**
	 *  ルールを全て取得する
	 *  @return	array
	 */
	public function rules() {
		return $this->_rules;
	}
	/**
	 *  エラーを追加する
	 *  @param	string	$key
	 *  @param	string	$msg
	 */
	public function error($key, $msg) {
		$this->_errors[$key][] = $msg;
	}
	/**
	 *  エラー有無を取得する
	 *  @return	string	エラーがある場合はその旨のメッセージのHTML、無ければ空文字
	 */
	public function hasError() {
		if ($this->_errors) {
			return '<span class="error">入力エラーがあります。</span>';
		} else {
			return '';
		}
	}
	/**
	 *  エラーメッセージを取得する
	 *  @param	string	$key
	 *  @param	boolean	$asHtml	(Optional) HTMLにするかどうか
	 *  @return	mixed
	 */
	public function errors($key, $asHtml = true) {
		$errors = $this->_arrayValue($key, $this->_errors);
		
		if ($errors && $asHtml) {
			$s = '<span class="error">';
			$s .= implode("<br />\n", $errors);
			$s .= "</span><br />\n";
			return $s;
		} else {
			return $errors;
		}
	}
	/**
	 *  全てのエラーを取得する
	 *  @param	boolean	$asHtml	(Optional) HTMLにするかどうか
	 *  @return	array	パラメータ名とエラーメッセージの配列
	 */
	public function allErrors($asHtml = true) {
		$arr = array();
		
		foreach (array_keys($this->_errors) as $key) {
			$arr[$key] = $this->errors($key, $asHtml);
		}
		
		return $arr;
	}
	/**
	 *  文字コードをUTF-8にする
	 *  @param	string	$value
	 *  @param	string	$from	(Optional) 変換前の文字コード
	 *  @return	string
	 */
	public function utf8($value, $from = 'SJIS-WIN') {
		return mb_convert_encoding($value, 'UTF-8', $from);
	}
	/**
	 *  文字コードをShift_JISにする
	 *  @param	string	$value
	 *  @return	string
	 */
	public function sjis($value) {
		return mb_convert_encoding($value, 'SJIS-WIN', self::ENCODING);
	}
}
