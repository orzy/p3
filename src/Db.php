<?php
/**
 *  P3_Db
 *
 *  require
 *      * PDO
 *      * MySQL
 *      * P3_Abstract
 *
 *  @version 3.3.3
 *  @see     https://github.com/orzy/p3
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
class P3_Db extends P3_Abstract {
	/** INSERTで一意キー重複データが既にあれば登録しない */
	const ON_DUPLICATE_IGNORE = 'IGNORE';
	/** INSERTで一意キー重複データが既にあれば削除してから登録 */
	const ON_DUPLICATE_REPLACE = 'REPLACE';
	
	private $_pdo = null;
	private $_columnCreatedAt = '';
	private $_columnUpdatedAt = '';
	private $_sql = '';
	private $_params = null;
	
	/**
	 *  DB接続する
	 *  @param	string	$db	DB名
	 *  @param	string	$user	DB接続のユーザー名
	 *  @param	string	$password	DB接続のパスワード
	 *  @param	string	$others	(Optional) DB接続のその他のパラメータ
	 *  @see http://php.net/manual/ja/ref.pdo-mysql.connection.php
	 */
	public function __construct($db, $user, $password, $others = '') {
		$this->_pdo = new PDO(
			"mysql:dbname=$db;charset=utf8;$others",
			$user,
			$password,
			array(
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,	//例外を投げる
				PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,	//MySQLのみ
				PDO::ATTR_EMULATE_PREPARES => false,	//サーバサイドのを使う
			)
		);
	}
	/**
	 *  レコード作成時に自動で作成日時を登録する列名を指定する
	 *  @param	string	$column	(Optional) レコード作成日時列の列名
	 */
	public function columnCreatedAt($column = 'created_at') {
		$this->_columnCreatedAt = $column;
	}
	/**
	 *  レコード更新時に自動で更新日時を更新する列名を指定する
	 *  @param	string	$column	(Optional) レコード更新日時列の列名
	 */
	public function columnUpdatedAt($column = 'updated_at') {
		$this->_columnUpdatedAt = $column;
	}
	/**
	 *  SQLを実行する
	 *  @param	string	$sql
	 *  @param	mixed	$params	(Optional) Prepared Statementのパラメータ
	 *  @return	PDOStatement
	 */
	public function query($sql, $params = array()) {
		$this->_sql = $sql;
		$this->_params = $params;
		
		try {
			$stmt = $this->_pdo->prepare($sql);
			$stmt->execute((array)$params);
		} catch (PDOException $e) {
			//エラーログにSQLとパラメータを出力
			$msg = "\n[SQL]\n$sql\n[PARAMS]\n" . var_export($params, true);
			throw new RuntimeException($e->getMessage() . $msg);
		}
		
		$stmt->setFetchMode(PDO::FETCH_ASSOC);	//添字を列名のみにする
		return $stmt;
	}
	/**
	 *  最後に実行したSQLを取得する
	 *  @return	string
	 */
	public function sql() {
		return $this->_sql;
	}
	/**
	 *  最後に実行したSQLのパラメータを取得する
	 *  @return	array
	 */
	public function params() {
		return $this->_params;
	}
	/**
	 *  SQLを実行し、先頭の1行を取得する
	 *  @param	string	$sql
	 *  @param	mixed	$params	(Optional) Prepared Statementのパラメータ
	 *  @return	array
	 */
	public function queryRow($sql, $params = array()) {
		$stmt = $this->query($sql, $params);
		return $stmt->fetch();
	}
	/**
	 *  SQLを実行し、先頭の1行の先頭の列の値を取得する
	 *  @param	string	$sql
	 *  @param	mixed	$params	(Optional) Prepared Statementのパラメータ
	 *  @return	mixed
	 */
	public function queryColumn($sql, $params = array()) {
		$stmt = $this->query($sql, $params);
		return $stmt->fetchColumn();
	}
	/**
	 *  文字コードを設定する
	 *  @param	string	$charset	(Optional) 文字コード
	 */
	public function charset($charset = 'utf8') {
		$this->query("SET NAMES $charset");
	}
	/**
	 *  SELECTする
	 *  @param	string	$select	取得する列名（カンマ区切り）
	 *  @param	string	$from	取得元のテーブル名
	 *  @param	array	$where	(Optional) 取得条件の列名（と演算子）と値の配列
	 *  @param	string	$others	(Optional) ORDER BY等
	 *  @return	mixed	ORDER BYあり:PDOStatement、複数列:array、それ以外:列の値
	 */
	public function select($select, $from, array $where = array(), $others = '') {
		$sql = "SELECT $select \n";
		$sql .= " FROM $from \n";
		
		if ($where) {
			list($sqlWhere, $params) = $this->_where($where);
			$sql .= $sqlWhere;
		} else {
			$params = array();
		}
		
		$sql .= $others;
		
		if (stripos($others, 'ORDER BY') !== false) {	//複数行の場合
			return $this->query($sql, $params);
		} else if (preg_match('/[,*]/', $select)) {	//複数列の場合
			return $this->queryRow($sql, $params);
		} else {
			return $this->queryColumn($sql, $params);
		}
	}
	/**
	 *  件数を取得する
	 *  @param	string	$table	テーブル名
	 *  @param	array	$where	(Optional) 取得条件の列名（と演算子）と値の配列
	 *  @return	integer
	 */
	public function count($table, array $where = array()) {
		$row = $this->select('COUNT(*) AS cnt', $table, $where);
		return $row['cnt'];
	}
	/**
	 *  INSERTする
	 *  @param	string	$table	テーブル名
	 *  @param	array	$values	登録する列名と値の配列
	 *  @param	mixed	$onDuplicate	(Optional) 一意制約違反時の動作
	 *  @param	boolean	$seqNoFlg	(Optional) SEQ Noを返すかどうか
	 *  @return	integer	(Optional) $seqNoFlgがtrueの場合、SEQ Noを返す
	 */
	public function insert($table, array $values, $onDuplicate = null, $seqNoFlg = false) {
		if ($this->_columnCreatedAt && !isset($values[$this->_columnCreatedAt])) {
			$values[$this->_columnCreatedAt] = date('Y-m-d H:i:s');
		}
		
		$sql = 'INSERT';
		
		if ($onDuplicate && !is_array($onDuplicate)) {
			switch (strToUpper($onDuplicate)) {
				case self::ON_DUPLICATE_IGNORE:
					$sql .= ' IGNORE';
					break;
				case self::ON_DUPLICATE_REPLACE:
					$sql = 'REPLACE';
					break;
				default:
					throw new InvalidArgumentException("未定義のパラメータ '$onDuplicate'");
			}
		}
		
		$params = array_values($values);
		
		$sql .= " INTO $table (" . implode(', ', array_keys($values)) . ")\n";
		$sql .= 'VALUES(' . str_repeat('?, ', count($values) - 1) . '?)';
		
		if (is_array($onDuplicate)) {	//一意キー重複データが既にあれば更新
			list($sqlSet, $updateParams) = $this->_set($onDuplicate);
			$sql .= "\n ON DUPLICATE KEY UPDATE $sqlSet";
			$params = array_merge($params, $updateParams);
		}
		
		$this->query($sql, $params);
		
		if ($seqNoFlg) {
			return $this->_pdo->lastInsertId();
		}
	}
	/**
	 *  UPDATEする
	 *  @param	string	$table	テーブル名
	 *  @param	array	$set	更新する列名と値の配列
	 *  @param	array	$where	更新条件の列名（と演算子）と値の配列
	 */
	public function update($table, array $set, array $where) {
		list($sqlSet, $params) = $this->_set($set);
		list($sqlWhere, $params) = $this->_where($where, $params);
		
		$this->query("UPDATE $table \n SET $sqlSet \n $sqlWhere", $params);
	}
	/**
	 *  DELETEする
	 *  @param	string	$table	テーブル名
	 *  @param	array	$where	更新条件の列名（と演算子）と値の配列
	 */
	public function delete($table, array $where) {
		list($sqlWhere, $params) = $this->_where($where);
		
		$this->query("DELETE FROM $table \n" . $sqlWhere, $params);
	}
	
	private function _where(array $where, array $params = array()) {
		foreach ($where as $key => $value) {
			if (is_numeric($key)) {
				$arr[] = $value;
			} else if (is_array($value)) {
				$arr[] = $key;
				$params = array_merge($params, $value);
			} else if (preg_match('/ ((<|>|!)=?|(NOT )?LIKE)\z/i', $key)) {
				$arr[] = "$key ?";
				$params[] = $value;
			} else {
				$arr[] = "$key = ?";
				$params[] = $value;
			}
		}
		
		return array('WHERE ' . implode("\n AND ", $arr) . "\n", $params);
	}
	
	private function _set(array $set) {
		if ($this->_columnUpdatedAt && !isset($set[$this->_columnUpdatedAt])) {
			$set[$this->_columnUpdatedAt] = date('Y-m-d H:i:s');
		}
		
		$arr = array();
		$params = array();
		
		foreach ($set as $key => $value) {
			if (is_numeric($key)) {
				$arr[] = $value;
			} else {
				$arr[] = "$key = ?";
				$params[] = $value;
			}
		}
		
		return array(implode("\n ,", $arr), $params);
	}
	/**
	 *  トランザクションを開始する
	 */
	public function begin() {
		$this->_pdo->beginTransaction();
	}
	/**
	 *  トランザクションをCOMMITする
	 */
	public function commit() {
		$this->_pdo->commit();
	}
	/**
	 *  トランザクションをROLLBACKする
	 */
	public function rollback() {
		$this->_pdo->rollBack();
	}
}
