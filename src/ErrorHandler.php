<?php
/**
 *  P3_ErrorHandler
 *
 *  require
 *      * P3_Abstract
 *      * P3_Controller
 *
 *  @version 3.0.3
 *  @see     https://github.com/orzy/p3
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
class P3_ErrorHandler extends P3_Abstract {
	private $_controller = null;
	private $_debugFlg = false;
	private $_errorPages = array();
	
	/**
	 *  コンストラクタ
	 *  @param	P3_Controller	$controller	(Optional) エラーページ内で使用できる
	 */
	public function __construct(P3_Controller $controller = null) {
		$this->_controller = $controller;
		
		//想定外のエラーのハンドリング
		set_exception_handler(array($this, 'uncatchedException'));
		set_error_handler(array($this, 'beforeWarning'), E_WARNING);
		register_shutdown_function(array($this, 'beforeShutdown'));
	}
	/**
	 *  デバグモードにする
	 *  @param	boolean	$debug	(Optional)
	 */
	public function debug($debug = true) {
		$this->_debugFlg = $debug;
		
		if ($debug) {
			ini_set('display_errors', true);
		}
	}
	/**
	 *  エラーページを指定する
	 *  @param	integer	$statusCode	HTTPステータスコード
	 *  @param	string	$path	(Optional) エラーページのファイルパス
	 */
	public function errorPage($statusCode, $path = '') {
		$path = $path ? $path : $statusCode;
		$this->_errorPages[$statusCode] = getcwd() . "/$path.php";
	}
	/**
	 *  P3_Controllerのparam()を実行する
	 *  @param	string	$key
	 *  @param	mixed	$value	(Optional)
	 *  @return	mixed
	 */
	public function param($key, $value = null) {
		return $this->_controller->param($key, $value);
	}
	/**
	 *  HTTPエラーを処理する
	 *  @param	integer	$statusCode	HTTPステータスコード
	 *  @param	string	$path	(Optional) エラーページのファイルパス
	 */
	public function httpError($statusCode, $content = '') {
		$statuses = array(
			400 => 'Bad Request',
			403 => 'Forbidden',
			404 => 'Not Found',
			500 => 'Internal Server Error',
			503 => 'Service Unavailable',
		);
		$msg = $this->_arrayValue($statusCode, $statuses, 'Unknown status');
		header("HTTP/1.1 $statusCode $msg");
		
		if (isset($this->_errorPages[$statusCode])) {
			ob_start();
			require($this->_errorPages[$statusCode]);
			return ob_get_clean();
		} else if ($content) {
			return $content;
		} else {
			return "$statusCode $msg";
		}
	}
	/**
	 *  キャッチされなかったExceptionの処理
	 *  @param	Exception	$e
	 */
	public function uncatchedException(Exception $e){
		echo $this->httpError(500) . "\n";
		
		if ($this->_debugFlg) {
			echo "<pre>$e</pre>\n";
		}
		
		$this->_errorLog('Exception', (string)$e);
	}
	/**
	 *  Warningをログに残す
	 *  @param	integer	$errNo
	 *  @param	string	$msg
	 *  @param	string	$file
	 *  @param	integer	$line
	 *  @param	array	$context
	 *  @return	boolean
	 */
	public function beforeWarning($errNo, $msg, $file, $line, $context) {
		$e = new Exception();	//スタックトレース取得用
		$this->_errorLog('Warning', $e->getTraceAsString());
		return false;	//通常のエラーハンドラーに処理を継続させる
	}
	/**
	 *  Fatal Errorをログに残す
	 */
	public function beforeShutdown(){
		$error = error_get_last();
		
		if ($error['type'] !== E_ERROR) {
			return;
		}
		
		if (substr($error['message'], 0, 22) === 'Maximum execution time') {
			echo $this->httpError(503, '<strong>Time out</strong>');
		} else {
			echo $this->httpError(500);
		}
		
		$this->_errorLog('Error', $error['message']);
	}
	
	private function _errorLog($level, $msg) {
		error_log("$level [URI] " . $_SERVER['REQUEST_URI'] . "\n" . $msg);
	}
}
