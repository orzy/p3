<?php
/**
 *  グローバル関数
 *
 *  require
 *      * spl_autoload_register (PHP 5.1.2+)
 *      * P3_Abstract
 *
 *  @version 3.1.1
 *  @see     https://github.com/orzy/p3
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 *  ClassのautoloadにPEAR形式のClass名を登録
 */
spl_autoload_register(
	create_function('$className', 'require(strtr($className, "_", "/") . ".php");')
);
/**
 *  配列から値を取得する
 *  @param	string	$key
 *  @param	array	$arr
 *  @param	mixed	$default	(Optional)
 *  @return	mixed
 */
function arrayValue($key, array $arr, $default = null) {
	return isset($arr[$key]) ? $arr[$key] : $default;
}
/**
 *  HTMLエスケープ
 *  @param	string	$value
 *  @return	string
 */
function h($value) {
	return htmlSpecialChars($value, ENT_QUOTES, P3_Abstract::ENCODING);
}
/**
 *  URLエンコード（RFC 3986形式）
 *  @param	mixed	$data
 *  @param	string	$url	(Optional)
 *  @return	string
 */
function ue($data, $url = '') {
	if (is_array($data)) {
		if ($url) {
			$s = $url . (strpos($url, '?') === false ? '?' : '&');
		} else {
			$s = '';
		}
		
		$s .= strtr(http_build_query($data), array('%7E' => '~', '+' => '%20'));
	} else {
		$s = strtr(rawUrlEncode($data), array('%7E' => '~'));
	}
	
	return $s;
}
/**
 *  現在の日時を取得する
 *  @return	string
 */
function now() {
	return date('Y-m-d H:i:s');
}
/**
 *  データを見やすい形で出力、またはログに書き出す
 *  @param	mixed	$value
 *  @param	mixed	$log	ログファイルへ書き出すかどうか or ログファイルのパス
 */
function dump($value, $log = false) {
	if (is_array($value) || is_object($value)) {
		$value = var_export($value, true);
	}
	
	if ($log) {
		if (is_bool($log)) {
			error_log($value);
		} else {
			error_log('[' . now() . "] $value\n", 3, $log);
		}
	} else {
		echo '<pre>' . h($value) . "</pre>\n";
	}
}
