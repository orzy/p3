<?php
/**
 *  P3_Controller
 *
 *  require
 *      * P3_Abstract
 *      * P3_Cache
 *      * P3_Db
 *      * P3_ErrorHandler
 *      * P3_Filter
 *      * P3_Form
 *      * P3_Http
 *      * P3_Session
 *
 *  @version 3.7.0
 *  @see     https://github.com/orzy/p3
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
class P3_Controller extends P3_Abstract {
	private $_handler = null;
	private $_filter = null;
	private $_db = null;
	private $_baseUrl = '';
	private $_urlArr = array();
	private $_template = '';
	private $_params = array();
	
	/**
	 *  コンストラクタ
	 *  @param	string	$htmlCharset	HTMLの文字コード
	 */
	public function __construct($htmlCharset = self::ENCODING) {
		$this->_handler = new P3_ErrorHandler($this);
		$this->_filter = new P3_Filter();
		
		ini_set('mbstring.strict_detection', true);	// encoding_translationには適用されない
		$this->_import($htmlCharset);
		mb_internal_encoding(self::ENCODING);
		ini_set('default_charset', $htmlCharset);
		
		//出力文字コードの変換
		if ($htmlCharset !== self::ENCODING) {
			mb_http_output($htmlCharset);
			ob_start('mb_output_handler');
		}
	}
	/**
	 *  入力データの受け取り
	 *  @param	string	$htmlCharset	HTMLの文字コード
	 */
	private function _import($htmlCharset) {
		$this->_params = array_merge($_GET, $_POST);
		
		if (ini_get('mbstring.encoding_translation') &&
		    ini_get('mbstring.http_input') !== 'pass') {
			$inputEncoding = mb_internal_encoding();	//これに変換されている
			
			if (strCaseCmp($inputEncoding, self::ENCODING) === 0) {	//同じなら
				return;	//変換済み
			}
		} else {
			$inputEncoding = $htmlCharset;
		}
		
		mb_convert_variables(self::ENCODING, $inputEncoding, $this->_params);
	}
	/**
	 *  デバグモードにする
	 *  @param	boolean	$flg	(Optional)
	 */
	public function debug($flg = true) {
		$this->_handler->debug($flg);
	}
	/**
	 *  アプリの基本となるURLをセット/取得する
	 *  @param	string	$url	(Optional) 取得する時は不要
	 *  @param	mixed	$scheme	(Optional) 戻り値に付けるスキーム（trueだと引き継ぐ）
	 *  @return	string	基本となるURL
	 */
	public function baseUrl($url = null, $scheme = false) {
		if ($url) {
			$this->_baseUrl = $url;
		} else {
			$url = $this->_baseUrl;
		}
		
		$http = new P3_Http();
		
		if ($scheme) {
			if (is_bool($scheme)) {
				$scheme = $http->scheme();
			}
			
			$url = "$scheme://" . $http->host() . $url;
		}
		
		return $url;
	}
	/**
	 *  URLの一部を取得する
	 *  @param	integer	$index
	 *  @return	string
	 */
	public function url($index) {
		if (!$this->_urlArr) {
			$pattern = '@(^' . $this->_baseUrl . '/|\\?.*\z)@';
			$url = preg_replace($pattern, '', $_SERVER['REQUEST_URI']);
			
			foreach (explode('/', $url) as $part) {
				$this->_urlArr[] = mb_convert_encoding(urldecode($part), self::ENCODING);
			}
		}
		
		return $this->_arrayValue($index, $this->_urlArr);
	}
	/**
	 *  コンテンツをキャッシュする
	 *  @param	mixed	$actions	キャッシュ対象のアクション名の文字列 or 配列
	 *  @param	string	$dir	キャッシュ用ディレクトリのパス
	 *  @param	integer	$duration	(Optional) キャッシュ期間
	 */
	public function cache($actions, $dir, $duration = 86400) {
		if (in_array($this->url(0), (array)$actions)) {
			$cache = new P3_Cache();
			$cache->cleanUp("$dir/*", $duration);
			$cache->cache($dir, '', $duration);
		}
	}
	/**
	 *  テンプレートファイルを指定する
	 *  @param	string	$name	(Optional)
	 */
	public function template($name = '_template') {
		$this->_template = $name;
	}
	/**
	 *  エラーページを指定する
	 *  @param	integer	$statusCode	HTTPステータスコード
	 *  @param	string	$path	(Optional) エラーページのファイルパス
	 */
	public function errorPage($statusCode, $path = '') {
		$this->_handler->errorPage($statusCode, $path);
	}
	/**
	 *  パラメータを取得/セットする
	 *  @param	string	$key
	 *  @param	mixed	$value	(Optional)
	 *  @return	mixed
	 */
	public function param($key, $value = null) {
		if (func_num_args() === 2) {
			$this->_params[$key] = $value;
		} else {
			return $this->_arrayValue($key, $this->_params);
		}
	}
	/**
	 *  全パラメータを取得する
	 *  @return	array
	 */
	public function params() {
		return $this->_params;
	}
	/**
	 *  入力データフィルターを取得する
	 *  return	P3_Filter
	 */
	public function filter() {
		return $this->_filter;
	}
	/**
	 *  必須のパラメータを指定する
	 *  @param	mixed	$keys	パラメータ名の文字列 or 配列
	 *  @param	string	$msg	(Optional) エラー時のメッセージ
	 */
	public function required($keys, $msg = '') {
		foreach ((array)$keys as $key) {
			$this->_filter->required($key, $this->param($key), $msg);
		}
	}
	/**
	 *  パラメータの変換とチェックのルールを指定する
	 *  @param	string	$key	パラメータの名前
	 *  @param	string	$rule	変換・チェックのルール
	 *  @param	mixed	$param	(Optional) ルールに関する指定値
	 *  @param	string	$msg	(Optional) エラー時のメッセージ
	 *  @see P3_Filter
	 */
	public function rule($key, $rule, $param = null, $msg = '') {
		$value = $this->param($key);
		$this->param($key, $this->_filter->rule($value, $rule, $param, $key, $msg));
	}
	/**
	 *	フォームのCSRF・二重送信防止用のワンタイムトークンをチェックする
	 *  @param	string	$msg	(Optional) エラー時のメッセージ
	 */
	public function token($msg = 'ページ遷移エラー') {
		$session = new P3_Session();
		
		if (!$session->token(false)) {
			$this->error(P3_Session::FORM_TOKEN, $msg);
		}
	}
	/**
	 *  パラメータに関するエラーを追加する
	 *  @param	string	$key	パラメータの名前
	 *  @param	string	$msg	エラーメッセージ
	 */
	public function error($key, $msg) {
		$this->_filter->error($key, $msg);
	}
	/**
	 *  エラー有無を取得する
	 *  @return	string	エラーがある場合はその旨のメッセージのHTML、無ければ空文字
	 */
	public function hasError() {
		return $this->_filter->hasError();
	}
	/**
	 *  DBアクセスヘルパーを取得する
	 *  @param	string	$dbName	(Optional) DB名（接続時は必須）
	 *  @param	string	$user	(Optional) DB接続のユーザー名
	 *  @param	string	$password	(Optional) DB接続のパスワード
	 *  @param	string	$others	(Optional) DB接続のその他のパラメータ
	 *  @param	mixed	$createdAt	(Optional) レコード作成日時列有無、または列名
	 *  @param	mixed	$updatedAt	(Optional) レコード更新日時列有無、または列名
	 *  @return	P3_Db
	 */
	public function db($dbName = '', $user = '', $password = '', $others = ''
	                 , $createdAt = false, $updatedAt = false) {
		if ($dbName) {
			$db = new P3_Db($dbName, $user, $password, $others);
			
			if ($createdAt) {
				if (is_bool($createdAt)) {
					$db->columnCreatedAt();
				} else {
					$db->columnCreatedAt($createdAt);
				}
			}
			
			if ($updatedAt) {
				if (is_bool($updatedAt)) {
					$db->columnUpdatedAt();
				} else {
					$db->columnUpdatedAt($updatedAt);
				}
			}
			
			$this->_db = $db;
		}
		
		return $this->_db;
	}
	/**
	 *  form生成ヘルパーを取得する
	 *  @param	boolean	$alertFlg	(Optional) form要素にエラーメッセージを表示するかどうか
	 *  @param	boolean	$html5	(Optional) HTML5のrequired属性・pattern属性を使うか
	 *  @return	P3_Form
	 */
	public function form($alertFlg = true, $html5 = true) {
		$errors = $alertFlg ? $this->_filter->allErrors() : array();
		return new P3_Form($this->_params, $errors, $this->_filter->rules(), $html5);
	}
	/**
	 *  リダイレクトする
	 *  @param	string	$url	絶対URL・相対URLのどちらでも可
	 *  @param	integer	$statusCode	(Optional) リダイレクト時のHTTPステータスコード
	 *  @param	string	$scheme	(Optional) "http", "https"など
	 */
	public function redirect($url, $statusCode = 302, $scheme = '') {
		$http = new P3_Http();
		$http->redirect($url, $statusCode, $scheme);
	}
	/**
	 *  アクションを実行する
	 *  @param	string	$path	(Optional) アクション名、デフォルトはURLから取得
	 */
	public function run($path = '') {
		$urlFirst = $this->url(0);
		$urlLen = count($this->_urlArr);
		
		if ($urlFirst && !$this->url($urlLen - 1)) {
			$url = '../' . $this->url($urlLen - 2);
			
			if ($_SERVER['QUERY_STRING']) {
				$url .= '?' . $_SERVER['QUERY_STRING'];
			}
			
			$this->redirect($url, 301);
		}
		
		$path = ($path ? $path : ($urlFirst ? $urlFirst : 'index')) . '.php';
		
		if (substr($path, 0, 1) === '_') {	//アンダーバーで始まるパスは禁止
			throw new UnexpectedValueException("'$path'は読み込み禁止です");
		}
		
		if (is_file($path)) {
			ob_start();
			$status = $this->_renderContent($path);
			$content = ob_get_clean();
		} else {
			$status = 404;
			$content = '';
		}
		
		if ($status && $status !== 1) {
			$content = $this->_handler->httpError($status, $content);
		}
		
		if ($this->_template) {
			$this->_renderTemplate($content);
		} else {
			echo $content;
		}
	}
	
	private function _renderContent($path) {
		return require($path);
	}
	
	private function _renderTemplate($content) {
		require($this->_template . '.php');
	}
}
