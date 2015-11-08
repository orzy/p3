<?php
/**
 *  P3_Html
 *
 *  require
 *      * P3_Abstract
 *
 *  @version 3.0.8
 *  @see     https://github.com/orzy/p3
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
class P3_Html extends P3_Abstract {
	/**
	 *  HTMLタグを生成する
	 *  @param	string	$name
	 *  @param	array	$attr
	 *  @param	string	$text	(Optional)
	 *  @return	string
	 */
	public function tag($name, array $attr, $text = null) {
		$s = "<$name";
		
		foreach ($attr as $key => $value) {
			if (!is_null($value)) {
				$s .= " $key" . '="' . $this->_h($value) . '"';
			}
		}
		
		if (is_null($text) && !preg_match('/^(script|td|textarea|th)\z/', $name)) {
			$s .= " />\n";
		} else {
			$s .= '>' . $this->_h($text) . "</$name>\n";
		}
		
		return $s;
	}
	/**
	 *  リンクを生成する
	 *  @param	string	$href
	 *  @param	string	$text
	 *  @param	array	$attr	(Optional)
	 *  @return	string
	 */
	public function a($href, $text, array $attr = array()) {
		$attr['href'] = $href;
		return $this->tag('a', $attr, $text);
	}
	/**
	 *  画像のHTMLタグを生成する
	 *  @param	string	$src
	 *  @param	string	$alt	(Optional) 指定しない場合はファイル名から取得する
	 *  @param	array	$attr	(Optional)
	 *  @return	string
	 */
	public function img($src, $alt = null, array $attr = array()) {
		if (is_null($alt)) {
			if (preg_match('@([^/]+?)(\\..*)?\z@', $src, $matches)) {
				$alt = $matches[1];
			} else {
				$alt = ' ';
			}
		}
		
		$attr['src'] = $src;
		$attr['alt'] = $alt;
		return $this->tag('img', $attr);
	}
	/**
	 *  CSSのリンクを生成する
	 *  CSS更新後のブラウザキャッシュからの読み込み防止機能付き
	 *  @param	string	$url
	 *  @param	string	$dir	(Optional) CSSのディレクトリパス
	 *  @return	string
	 */
	public function css($url, $dir = '') {
		$attr = array(
			'rel' => 'stylesheet',
			'type' => 'text/css',
			'href' => $this->_addTime($url, $dir),
		);
		return $this->tag('link', $attr);
	}
	/**
	 *  scriptタグを生成する
	 *  JavaScriptファイル更新後のブラウザキャッシュからの読み込み防止機能付き
	 *  @param	string	$url
	 *  @param	string	$dir	(Optional) JavaScriptファイルのディレクトリパス
	 *  @param	array	$attr	(Optional)
	 *  @return	string
	 */
	public function script($url, $dir = '', array $attr = array()) {
		$attr['type'] = 'text/javascript';
		$attr['src'] = $this->_addTime($url, $dir);
		return $this->tag('script', $attr);
	}
	
	private function _addTime($url, $dir) {
		if ($dir) {
			$path = "$dir/" . basename($url);
			
			if (!is_file($path)) {
				throw new RuntimeException("'$path'にファイルがありません");
			}
			
			$url .= '?' . filemtime($path);
		}
		
		return $url;
	}
}
