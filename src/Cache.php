<?php
/**
 *  P3_Cache
 *
 *  require
 *      * P3_Abstract
 *
 *  @version 3.1.2
 *  @see     https://github.com/orzy/p3
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
class P3_Cache extends P3_Abstract {
	private $_path;
	private $_workDir;
	
	/**
	 *  ページ全体をキャッシュする
	 *  @param	string	$dir	(Optional) キャッシュファイルを置くディレクトリのパス
	 *  @param	string	$key	(Optional) キャッシュファイル名
	 *  @param	integer	$duration	(Optional) キャッシュ期間の秒数
	 */
	public function cache($dir = '.', $key = '', $duration = 86400) {
		$path = $this->_cachePath($dir, $key);
		$now = time();
		clearStatCache();
		
		// サーバ側のキャッシュの期限切れチェック
		if (is_file($path) && (filemtime($path) + $duration) > $now) {
			// 有効なキャッシュあり
			$createdAt = filemtime($path);
			$since = $this->_arrayValue('HTTP_IF_MODIFIED_SINCE', $_SERVER);
			
			if ($since) {	// ブラウザキャッシュあり
				if (!preg_match('/GMT/', $since)) {
					$since .= ' GMT';
				}
				
				if ($createdAt === strToTime($since)) {	// ブラウザキャッシュがまだ有効か
					$this->_notModified();
					exit;	// 終了
				}
			}
		} else {	// 有効なキャッシュ無し
			$createdAt = $now;
		}
		
		// HTTPヘッダーでクライアントキャッシュを制御
		$this->httpHeaders($duration, $createdAt);
		
		// sessionを使うと出力されるHTTPヘッダーの設定・上書き
		if (session_id()) {
			header('Pragma:');
		} else {
			session_cache_limiter('public');
			// session.cache_expireの単位は分
			ini_set('session.cache_expire', ceil(($createdAt + $duration - $now) / 60));
		}
		
		if ($createdAt < $now) {	// サーバ側に有効なキャッシュあり
			if ($this->_etag(md5_file($path))) {
				readfile($path);	// 変更ありの場合
			}
			
			exit;	// 終了
		}
		
		$this->_path = $path;
		$this->_workDir = getcwd();
		ob_start(array($this, 'callback'));	// 以後の出力をバッファリングする
	}
	/**
	 *  ページをバッファへ出力した後に呼ばれるCallback
	 *  @param	string	$buf	buffer
	 */
	public function callback($buf) {
		chdir($this->_workDir);	// セットしないとルート(/)になる
		file_put_contents($this->_path, $buf, LOCK_EX);
		
		if ($this->_etag(md5($buf))) {
			return $buf;
		} else {
			return '';	// 変更なし
		}
	}
	/**
	 *  コンテンツのMD5ハッシュをEtagとして送信する
	 *  @param	string	$etag
	 *  @return	boolean	Etagを送信したかどうか
	 */
	private function _etag($etag) {
		if ($etag === $this->_arrayValue('HTTP_IF_NONE_MATCH', $_SERVER)) {
			$this->_notModified();
			return false;
		}
		
		header("Etag: $etag");
		return true;
	}
	/**
	 *  変更なしのHTTP Response Headerを返す
	 */
	private function _notModified() {
		header('HTTP/1.1 304 Not Modified');
		
		if (session_id()) {	//session使用時の余分なHTTPヘッダーを消す
			header('Expires:');
			header('Cache-Control:');
		}
	}
	/**
	 *  部分キャッシュ開始
	 *  @param	string	$dir	(Optional) キャッシュファイルを置くディレクトリのパス
	 *  @param	string	$key	(Optional) キャッシュファイル名
	 *  @param	integer	$duration	(Optional) キャッシュ期間の秒数
	 *  @return	bookean	新たな出力が必要かどうか
	 */
	public function start($dir = '.', $key = '', $duration = 86400) {
		$path = $this->_cachePath($dir, $key);
		clearStatCache();
		
		if (is_file($path) && (filemtime($path) + $duration) > time()) {
			//有効なキャッシュあり
			readfile($path);
			return false;
		} else {
			//有効なキャッシュ無し
			$this->_path = $path;
			ob_start();
			return true;
		}
	}
	/**
	 *  部分キャッシュ終了
	 */
	public function end() {
		if (!$this->_path) {
			throw new LogicException('pathがセットされていません');
		}
		
		file_put_contents($this->_path, ob_get_flush(), LOCK_EX);
	}
	/**
	 *  HTTPヘッダーでクライアントキャッシュを制御する
	 *  @param	integer	$duration	(Optional) キャッシュ期間の秒数
	 *  @param	integer	$createdAt	(Optional) コンテンツ生成時刻のUnixタイムスタンプ
	 */
	public function httpHeaders($duration = 86400, $createdAt = 0) {
		$createdAt = $createdAt ? $createdAt : time();
		
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s T', $createdAt));
		header('Expires: ' . gmdate('D, d M Y H:i:s T', $createdAt + $duration));
		header("Cache-Control: max-age=$duration");
	}
	/**
	 *  古いキャッシュを削除する
	 *  @param	string	$pattern	glob()のルールに従った削除対象のパスのパターン
	 *  @param	integer	$duration	(Optional) キャッシュ期間の秒数
	 *  @param	integer	$probability	(Optional) 削除が実行される確率（1～100）
	 */
	public function cleanUp($pattern, $duration = 259200, $probability = 1) {
		if (rand(1, 100) > $probability) {
			return;
		}
		
		$time = time() - $duration;
		clearStatCache();
		
		foreach (glob($pattern) as $path) {
			is_file($path) && filemtime($path) < $time && unlink($path);
		}
	}
	
	private function _cachePath($dir, $key) {
		$key = $key ? $key : md5($_SERVER['REQUEST_URI']) . '.html';
		return "$dir/$key";
	}
}
