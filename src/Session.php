<?php
/**
 *  P3_Session
 *
 *  require
 *      * P3_Abstract
 *
 *  @version 3.0.6
 *  @see     https://github.com/orzy/p3
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
class P3_Session extends P3_Abstract {
	const TYPE_SYS = 'system_session';
	const TYPE_APP = 'app_session';
	
	const FORM_TOKEN = 'form_token';
	const HASH_ALGO = 'sha512';
	
	private $_seed = '';
	
	/**
	 *  コンストラクタ
	 *  @param	string	$seed	(Optional) hash生成で使う文字列
	 *  @param	array	$setting	(Optional) 初期設定
	 */
	public function __construct($seed = '', array $setting = array()) {
		$this->_seed = $seed;
		
		if (!session_id()) {
			$setting = array_merge(array(
				'name' => 'sid',
				'gc_maxlifetime' => 60 * 60 * 24 * 3,
				'cookie_httponly' => true,
				'use_only_cookies' => true,
				'entropy_file' => '/dev/urandom',
				'entropy_length' => 32,
				'hash_function' => true,
				'hash_bits_per_character' => 5,
			), $setting);
			
			foreach ($setting as $key => $value) {
				ini_set("session.$key", $value);
			}
			
			session_start();
		}
		
		if (!isset($_SESSION[self::TYPE_SYS])) {
			$_SESSION[self::TYPE_SYS] = array();
			$_SESSION[self::TYPE_APP] = array();
		}
	}
	/**
	 *  セッションに値をセットする
	 *  @param	string	$key
	 *  @param	mixed	$value
	 *  @param	string	$type	(Optional)
	 */
	public function set($key, $value, $type = self::TYPE_APP) {
		$_SESSION[$type][$key] = $value;
	}
	/**
	 *  セッションの値を削除する
	 *  @param	string	$key
	 *  @param	string	$type	(Optional)
	 */
	public function remove($key, $type = self::TYPE_APP) {
		unset($_SESSION[$type][$key]);
	}
	/**
	 *  セッションの値を取得する
	 *  @param	string	$key
	 *  @param	boolean	$unset	(Optional) 値を削除するかどうか
	 *  @param	string	$type	(Optional)
	 *  @return	mixed
	 */
	public function get($key, $unset = false, $type = self::TYPE_APP) {
		$value = $this->_arrayValue($key, $_SESSION[$type]);
		
		if ($unset) {
			unset($_SESSION[$type][$key]);
		}
		
		return $value;
	}
	/**
	 *  セッションの値を全て破棄する
	 *  @return	処理結果
	 */
	public function end() {
		$_SESSION = array();
		return session_destroy();
	}
	/**
	 *  1度しか使わないセッション値をセット/取得する
	 *  @param	string	$key
	 *  @return	mixed
	 */
	public function flash($value = null) {
		if (is_null($value)) {
			return $this->get('flash', true, self::TYPE_SYS);
		} else {
			$this->set('flash', $value, self::TYPE_SYS);
		}
	}
	/**
	 *  フォームのCSRF・二重送信防止用のワンタイムトークンを取得/チェックする
	 *  @param	boolean	$init	true:取得, false:チェック
	 *  @return	mixed	取得時はstring、チェック時はboolean
	 */
	public function token($init) {
		if ($init) {
			$token = session_id() . microtime();
			$this->set('token', $token, self::TYPE_SYS);
			return $token;
		} else {
			$token = $this->get('token', true, self::TYPE_SYS);
			return ($this->_arrayValue(self::FORM_TOKEN, $_POST) === $token);
		}
	}
	/**
	 *  ログイン
	 *  @param	string	$hash	saltとhash化したパスワード
	 *  @param	string	$password	パスワード
	 *  @param	mixed	$data	(Optional) ログイン情報としてセッションに保存する値
	 *  @return	boolean	パスワードが正しいかどうか
	 */
	public function login($hash, $password, $data = true) {
		list($salt) = explode(':', $hash);
		
		if (!$salt) {
			throw new UnexpectedValueException("saltがありません $hash");
		}
		
		if ($this->hash($password, $salt) !== $hash) {
			return false;
		}
		
		// ログイン成功の場合
		session_regenerate_id(true);
		$this->set('login', $data, self::TYPE_SYS);
		$redirectUrl = $this->get('check', true, self::TYPE_SYS);
		
		if ($redirectUrl) {
			$http = new P3_Http();
			$http->redirect($redirectUrl);
		} else {
			return true;
		}
	}
	/**
	 *  ログイン済みかチェックする
	 *  未ログインの場合、ログインページへリダイレクトしてexitする
	 *  @param	string	$loginUrl	ログインページのURL
	 */
	public function check($loginUrl) {
		if ($this->get('login', false, self::TYPE_SYS)) {	// ログイン済みか
			return;
		} else if (!$_POST) {	// GETの場合はログイン後にこのURLにリダイレクトさせる
			$this->set('check', $_SERVER['REQUEST_URI'], self::TYPE_SYS);
		}
		
		$http = new P3_Http();
		$http->redirect($loginUrl);
	}
	/**
	 *  ログアウトする
	 */
	public function logout() {
		$this->remove('login', self::TYPE_SYS);
	}
	/**
	 *  パスワードとsaltからhashを生成する
	 *  @param	string	$password	パスワード
	 *  @param	string	$salt	(Optional) salt
	 *  @return	string	saltとhash
	 */
	public function hash($password, $salt = '') {
		if (!$this->_seed) {
			throw new UnexpectedValueException('seedがありません');
		}
		
		if (!$salt) {
			$salt = microtime();
		}
		
		return "$salt:" . hash_hmac(self::HASH_ALGO, "$password*$salt", $this->_seed);
	}
}
