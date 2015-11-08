<?php
/**
 *  P3_Http
 *
 *  require
 *      * P3_Abstract
 *
 *  @version 3.1.0
 *  @see     https://github.com/orzy/p3
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
class P3_Http extends P3_Abstract {
	/**
	 *  リダイレクトする（絶対パスのURLでリダイレクトする）
	 *  @param	string	$url	絶対URL・相対URLのどちらでも可
	 *  @param	integer	$statusCode	(Optional) リダイレクト時のHTTPステータスコード
	 *  @param	string	$scheme	(Optional) "http", "https"など
	 */
	public function redirect($url, $statusCode = 302, $scheme = '') {
		if (!preg_match('@^https?://@i', $url)) {
			if (!$scheme) {
				$scheme = $this->scheme();
			}
			
			$host = $this->host();
			
			if (substr($url, 0, 1) !== '/') {
				//相対パスの場合
				$arr = explode('/', $_SERVER['REQUEST_URI']);
				array_pop($arr);
				
				foreach (explode('/', $url) as $dir) {
					if ($dir === '..') {
						array_pop($arr);
					} else if ($dir !== '.') {
						$arr[] = $dir;
					}
				}
				
				$url = implode('/', $arr);
			}
			
			$url = "$scheme://$host{$url}";
		}
		
		header("Location: $url", true, $statusCode);
		exit;
	}
	/**
	 *  HTTPSによるアクセスを強制する
	 */
	public function https() {
		if (!isset($_SERVER['HTTPS'])) {
			$this->redirect($_SERVER['REQUEST_URI'], 302, 'https');
		}
	}
	/**
	 *  レスポンスとしてCSVをダウンロードさせる
	 *  @param	string	$fileName	ファイル名
	 *  @param	array	$headers	ヘッダー
	 *  @param	mixed	$rows	データ（PDOStagement、arrayなど）
	 *  @param	string	$encoding	(Optional) CSVの文字コード
	 */
	public function csv($fileName, array $headers, $rows, $encoding = 'SJIS-WIN') {
		header('Content-Type: application/x-csv');
		$fileName = mb_convert_encoding($fileName, $encoding, self::ENCODING);
		header("Content-Disposition: attachment; filename=$fileName.csv");
		
		$fp = fopen('php://output', 'w');
		
		if ($headers) {
			mb_convert_variables($encoding, self::ENCODING, $headers);
			fputcsv($fp, $headers);
		}
		
		foreach ($rows as $row) {
			mb_convert_variables($encoding, self::ENCODING, $row);
			fputcsv($fp, $row);
		}
		
		fclose($fp);
		exit;
	}
	/**
	 *  レスポンスとしてJSONを返す
	 *  @param	array	$arr
	 *  @return	string
	 */
	public function json(array $arr) {
		header('Content-Type: application/json');
		echo json_encode($arr);
		exit;
	}
	/**
	 *  HTTP POSTを送信する
	 *  @param	string	$url
	 *  @param	array	$params
	 *  @param	mixed	$headers	(Optional) HTTPリクエストヘッダー
	 *  @param	string	レスポンスのbody
	 */
	public function post($url, array $params, $headers = array()) {
		$headers = (array)$headers;
		$headers[] = 'Content-type: application/x-www-form-urlencoded';
		
		$context = stream_context_create(array('http' => array(
			'method' => 'POST',
			'header' => $headers,
			'content' => http_build_query($params),
			'ignore_errors' => true,
		)));
		
		$response = file_get_contents($url, false, $context);
		
		// HTTPレスポンスヘッダーは定義済み変数$http_response_headerにセットされる
		if (is_array($http_response_header)) {
			// ステータスが200でなければ例外を投げる
			if (!preg_match('@^HTTP/1\\.. 200 @i', $http_response_header[0])) {
				$msg = "\n[URL]\n$url\n[PARAMS]\n" . var_export($params, true);
				throw new RuntimeException($http_response_header[0] . $msg);
			}
		}
		
		return $response;
	}
	/**
	 *  スキームを取得する
	 *  @return	string
	 */
	public function scheme() {
		return isset($_SERVER['HTTPS']) ? 'https' : 'http';
	}
	/**
	 *  ホスト名を取得する
	 *  @return	string
	 */
	public function host() {
		// リバースプロキシの場合は HTTP_X_FORWARDED_HOST に入っている
		return $this->_arrayValue('HTTP_X_FORWARDED_HOST', $_SERVER, $_SERVER['HTTP_HOST']);
	}
}
