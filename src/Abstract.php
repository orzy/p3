<?php
/**
 *  P3_Abstract
 *
 *  require
 *      * (none)
 *
 *  @version 3.1.0
 *  @see     https://github.com/orzy/p3
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
abstract class P3_Abstract {
	/** 内部文字コードはUTF-8 */
	const ENCODING = 'UTF-8';
	
	/**
	 *  宣言していないpublic変数へのアクセスを防止
	 *  @param	string	$key
	 */
	public function __get($key) {
		throw new LogicException(get_class($this) . "に無い変数をget $key");
	}
	/**
	 *  宣言していないpublic変数へのアクセスを防止
	 *  @param	string	$key
	 *  @param	mixed	$value
	 */
	public function __set($key, $value) {
		throw new LogicException(get_class($this) . "に無い変数をset $key");
	}
	/**
	 *  HTMLエスケープ
	 *  @param	string	$str
	 *  @return	string
	 */
	protected function _h($str) {
		return htmlSpecialChars($str, ENT_QUOTES, self::ENCODING);
	}
	/**
	 *  空かどうかを判断する
	 *  @param	mixed	$value
	 *  @return	boolean
	 */
	protected function _isBlank($value) {
		return is_null($value) || $value === '';
	}
	/**
	 *  配列から値を取得する
	 *  @param	string	$key
	 *  @param	array	$arr
	 *  @param	mixed	$default	(Optional)
	 *  @return	mixed
	 */
	protected function _arrayValue($key, array $arr, $default = null) {
		return isset($arr[$key]) ? $arr[$key] : $default;
	}
}
