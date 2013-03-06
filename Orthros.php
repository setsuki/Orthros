<?php
/**
 * ORマッパOrthros本体
 *
 * @package		Orthros
 * @author		setsuki (yukicon)
 * @copyright	Susugi Ningyoukan
 * @license		BSD
 **/

require_once __DIR__ . '/orthros_define.php';

class Orthros
{
	static protected $cache_host_arr = array();
	static protected $cache_setting_arr = array();
	
	public $db_host;
	public $db_port;
	public $db_name;
	
	// ローカル変数
	protected $pdo = null;
	protected $transaction_flg = false;
	protected $lock_tables = array();							// ロックしたテーブル
	protected $default_fetch_mode = PDO::FETCH_ASSOC;
	
	// 現在処理中の情報
	protected $latest_column;
	protected $latest_table;
	protected $latest_join_table;
	protected $latest_where;
	protected $latest_order_by;
	protected $latest_group_by;
	protected $latest_limit;
	
	protected $latest_lock_mode;
	protected $latest_cache_expire;
	protected $latest_cache_category;
	protected $latest_update_param;
	protected $latest_insert_param;
	
	protected $latest_query_string;
	protected $latest_query_param;
	
	// キャッシュ関連の変数
	protected $default_cache_category = 'default';
	
	/**
	 * コンストラクタ
	 * 
	 * @param	string		$db_host		接続するデータベースのホスト
	 * @param	string		$db_port		接続するデータベースのポート
	 * @param	string		$db_name		接続するデータベースの名称
	 * @param	string		$db_user		接続するユーザ名
	 * @param	string		$db_pass		接続するユーザのパスワード
	 */
	public function __construct($db_host, $db_port, $db_name, $db_user, $db_pass)
	{
		$this->connect($db_host, $db_port, $db_name, $db_user, $db_pass);
	}
	
	
	
	/**
	 * DBに接続する
	 * 
	 * @param	string		$db_host		接続するデータベースのホスト
	 * @param	string		$db_port		接続するデータベースのポート
	 * @param	string		$db_name		接続するデータベースの名称
	 * @param	string		$db_user		接続するユーザ名
	 * @param	string		$db_pass		接続するユーザのパスワード
	 */
	public function connect($db_host, $db_port, $db_name, $db_user, $db_pass)
	{
		$this->db_host = $db_host;
		$this->db_port = $db_port;
		$this->db_name = $db_name;
		// DBに接続
		$this->pdo = new PDO("mysql:host={$db_host};port={$db_port};dbname={$db_name}", $db_user, $db_pass);
		// PDOがPDOExceptionをスローするようにする
		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
	
	
	
	/**
	 * DB接続を切断する
	 */
	public function disconnect()
	{
		$this->pdo = null;
	}
	
	
	
	/**
	 * PDOの設定を行う
	 * 
	 * @param	array		$set_attribute_arr		設定配列
	 */
	public function setPDOAttribute($set_attribute_arr)
	{
		foreach ($set_attribute_arr as $set_key => $set_value) {
			$this->pdo->setAttribute($set_key, $set_value);
		}
	}
	
	
	
	/**
	 * デフォルトのキャッシュカテゴリを設定する
	 * 
	 * @param	string		$category				カテゴリ名
	 */
	public function setDefaultCacheCategory($category)
	{
		$this->default_cache_category = $category;
	}
	
	
	
	/**
	 * トランザクション中かどうかを返す
	 * 
	 * @param	bool								トランザクション中かどうか
	 */
	public function isTransaction()
	{
		return $this->transaction_flg;
	}
	
	
	
	/**
	 * 現在処理中のものを表す変数を初期化する
	 */
	public function resetLatestParam()
	{
		$this->latest_column = null;
		$this->latest_table = null;
		$this->latest_join_table = null;
		$this->latest_where = null;
		$this->latest_order_by = null;
		$this->latest_group_by = null;
		$this->latest_limit = null;
		
		$this->latest_lock_mode = null;
		$this->latest_cache_expire = null;
		$this->latest_cache_category = null;
		$this->latest_update_param = null;
		$this->latest_insert_param = null;
		$this->latest_query_string = null;
		$this->latest_query_param = null;
	}
	
	// ========================================================================
	// パラメータセット関連のメソッド
	// ========================================================================
	
	/**
	 * 処理するテーブルのセット
	 * 
	 * @param	string			$table_name				処理するテーブル名
	 * @return	Orthros									this
	 */
	public function table($table_name)
	{
		$this->resetLatestParam();			// パラメータを一度初期化
		$this->latest_table = $table_name;
		return $this;
	}
	
	
	
	/**
	 * 処理するカラムのセット
	 * 
	 * @param	array			$column_arr				カラム名の配列
	 * @return	Orthros									this
	 */
	public function column($column_arr)
	{
		$this->latest_column = $column_arr;
		return $this;
	}
	
	
	
	/**
	 * WHERE句のセット
	 * 
	 * @param	array			$condition_arr			条件用配列(array('column_name' => $condition))
	 * @param	string			$condition_type			条件指定文字列(ORTHROS_WHERE_XXXXX default:ORTHROS_WHERE_EQUAL)
	 * @return	Orthros									this
	 */
	public function where($condition_arr, $condition_type = ORTHROS_WHERE_EQUAL)
	{
		foreach ($condition_arr as $column_name => $condition) {
			// タイプと共に配列に格納
			$this->latest_where[$condition_type][$column_name] = $condition;
		}
		return $this;
	}
	
	
	
	/**
	 * JOINするテーブルのセット
	 * 
	 * @param	string			$join_table				JOINするテーブル名
	 * @param	array			$on_array				条件用配列(array('table1_column' => 'table2_column'))
	 * @param	string			$join_type				JOINのタイプ指定文字列(ORTHROS_JOIN_XXXXX default:ORTHROS_JOIN_INNER)
	 * @return	Orthros									this
	 */
	public function join($join_table, $on_array, $join_type = ORTHROS_JOIN_INNER)
	{
		$this->latest_join_table[$join_table] = array(
			'on_arr' => $on_array,
			'join_type' => $join_type
		);
		
		return $this;
	}
	
	
	
	/**
	 * ORDER BY句のセット
	 * 
	 * @param	array			$order_arr				OERDER配列(array('column_name' => ORTHROS_JOIN_XXXX))
	 * @return	Orthros									this
	 */
	public function order($order_arr)
	{
		$this->latest_order_by = $order_arr;
		return $this;
	}
	
	
	
	/**
	 * GROUP BY句のセット
	 * 
	 * @param	array			$group_arr				GROUP BY句に指定するカラム名の配列
	 * @return	Orthros									this
	 */
	public function group($group_arr)
	{
		$this->latest_group_by = $group_arr;
		return $this;
	}
	
	
	
	/**
	 * LIMIT句のセット
	 * 
	 * @param	int			$count						取得件数
	 * @param	int			$offset						取得位置(default:0)
	 * @return	Orthros									this
	 */
	public function limit($count, $offset = 0)
	{
		$this->latest_limit = array(
			'count' => $count,
			'offset' => $offset,
		);
		return $this;
	}
	
	
	
	/**
	 * ロックモードのセット
	 * 
	 * @param	int			$lock_mode					ロックモード(ORTHROS_LOCK_MODE_XXXX default:ORTHROS_LOCK_MODE_FOR_UPDATE)
	 * @return	Orthros									this
	 */
	public function lock($lock_mode = ORTHROS_LOCK_MODE_FOR_UPDATE)
	{
		$this->latest_lock_mode = $lock_mode;
		return $this;
	}
	
	
	
	/**
	 * キャッシュカテゴリのセット
	 * 
	 * @param	int				$expire					キャッシュ時間
	 * @param	string			$cache_category			キャッシュカテゴリ(default:default)
	 * @return	Orthros									this
	 */
	public function cache($expire = null, $cache_category = null)
	{
		$this->latest_cache_expire = $expire;
		$this->latest_cache_category = $this->default_cache_category;
		if (isset($cache_category)) {
			$this->latest_cache_category = $cache_category;
		}
		return $this;
	}
	
	// ========================================================================
	// 実行メソッド
	// ========================================================================
	
	/**
	 * トランザクション開始
	 */
	public function beginTransaction()
	{
		if ($this->transaction_flg) {
			throw new Exception('[Orthros] トランザクションのネストはできません');
		}
		$this->pdo->beginTransaction();
		$this->transaction_flg = true;
		$this->lock_tables = array();			// ロックしたテーブルの配列を初期化
	}
	
	
	
	/**
	 * コミット
	 */
	public function commit()
	{
		$this->pdo->commit();
		$this->transaction_flg = false;
	}
	
	
	
	/**
	 * ロールバック
	 */
	public function rollBack()
	{
		$this->pdo->rollBack();
		$this->transaction_flg = false;
	}
	
	
	
	/**
	 * 最後にINSERTしたIDを取得する
	 * 
	 * @reutrn	int									最後にINSERTしたID
	 */
	public function lastInsertId()
	{
		return $this->pdo->lastInsertId();
	}
	
	
	
	/**
	 * SELECT文を実行する
	 * 
	 * @param	int			$result_type			結果取得タイプ(ORTHROS_RESULT_TYPE_XXXX default:ORTHROS_RESULT_TYPE_FETCH_ALL)
	 * @param	mixed		$result_option			結果取得タイプ用のオプション(default:null)
	 * @reutrn	array								行データの入った配列の配列
	 */
	public function select($result_type = ORTHROS_RESULT_TYPE_FETCH_ALL, $result_option = null)
	{
		if ($this->transaction_flg and isset($this->latest_lock_mode)) {
			// トランザクション中でロックが指定されているならロック情報を記録
			$this->lock_tables[ORTHROS_QUERY_TYPE_SELECT][$this->latest_table][$this->latest_lock_mode] = true;
		}
		$this->makeQuery(ORTHROS_QUERY_TYPE_SELECT);
		if (isset($this->latest_cache_category) and !isset($this->latest_lock_mode) and ORTHROS_RESULT_TYPE_STATEMENT != $result_type) {
			// キャッシュが有効の場合(ロックするクエリでなく、result_typeがステートメントの場合以外)
			return $this->execCacheSelect($result_type, $result_option);
		} else {
			return $this->execQuery($this->latest_query_string, $this->latest_query_param, $result_type, $result_option);
		}
	}
	
	
	
	/**
	 * SELECT文を実行して、最初の要素だけを返す
	 * 
	 * @param	int			$result_type			結果取得タイプ(ORTHROS_RESULT_TYPE_XXXX default:ORTHROS_RESULT_TYPE_FETCH_ALL)
	 * @param	mixed		$result_option			結果取得タイプ用のオプション(default:null)
	 * @reutrn	array								行データの入った配列
	 */
	public function selectOne($result_type = ORTHROS_RESULT_TYPE_FETCH_ALL, $result_option = null)
	{
		$this->limit(1);
		$data = $this->select($result_type, $result_option);
		if (!empty($data)) {
			return $data[0];
		}
		return array();
	}
	
	
	
	/**
	 * 現在指定されている条件での件数を返す
	 * 
	 * @reutrn	int								対象の件数
	 */
	public function count()
	{
		$this->column(array('COUNT(*) AS cnt'));
		$cnt_info = $this->selectOne();
		return $cnt_info['cnt'];
	}
	
	
	
	/**
	 * INSERT文を実行する
	 * 
	 * @param	array		$insert_data_arr		INSERTするデータの配列
	 * @reutrn	int									影響のあった行数
	 */
	public function insert($insert_data_arr)
	{
		if ($this->transaction_flg) {
			// トランザクション中ならロック情報を記録
			$this->lock_tables[ORTHROS_QUERY_TYPE_INSERT][$this->latest_table] = true;
		}
		
		if (!isset($insert_data_arr[0])) {
			// 0番目の要素が無ければ1次元配列として配列の配列にする
			$this->latest_insert_param[0] = $insert_data_arr;
		} else {
			$this->latest_insert_param = $insert_data_arr;
		}
		$this->makeQuery(ORTHROS_QUERY_TYPE_INSERT);
		return $this->execQuery($this->latest_query_string, $this->latest_query_param, ORTHROS_RESULT_TYPE_ROW_COUNT);
	}
	
	
	
	/**
	 * DELETE文を実行する
	 * 
	 * @reutrn	int									影響のあった行数
	 */
	public function delete()
	{
		if ($this->transaction_flg) {
			// トランザクション中ならロック情報を記録
			$this->lock_tables[ORTHROS_QUERY_TYPE_DELETE][$this->latest_table] = true;
		}
		
		$this->makeQuery(ORTHROS_QUERY_TYPE_DELETE);
		return $this->execQuery($this->latest_query_string, $this->latest_query_param, ORTHROS_RESULT_TYPE_ROW_COUNT);
	}
	
	
	
	/**
	 * UPDATE文を実行する
	 * 
	 * @param	array		$update_data_arr		UPDATEするデータの配列(array('column_name' => value))
	 * @reutrn	int									影響のあった行数
	 */
	public function update($update_data_arr)
	{
		if ($this->transaction_flg) {
			// トランザクション中ならロック情報を記録
			$this->lock_tables[ORTHROS_QUERY_TYPE_UPDATE][$this->latest_table] = true;
		}
		
		$this->latest_update_param = $update_data_arr;
		$this->makeQuery(ORTHROS_QUERY_TYPE_UPDATE);
		return $this->execQuery($this->latest_query_string, $this->latest_query_param, ORTHROS_RESULT_TYPE_ROW_COUNT);
	}
	
	
	
	/**
	 * 指定されたクエリを実行する
	 * 
	 * @param	string		$query_string			クエリ文字列
	 * @param	array		$query_param			パラメータ配列(default:空配列)
	 * @param	int			$result_type			結果取得タイプ(ORTHROS_RESULT_TYPE_XXXX default:ORTHROS_RESULT_TYPE_FETCH_ALL)
	 * @param	mixed		$result_option			結果取得タイプ用のオプション(default:null)
	 * @param	int			$fetch_mode				PDO::FETCH_XXXX(default:null)
	 * @return	mixed								result_typeに対応した結果
	 */
	public function execQuery($query_string, $query_param = array(), $result_type = ORTHROS_RESULT_TYPE_FETCH_ALL, $result_option = null, $fetch_mode = null)
	{
		$exec_fetch_mode = $this->default_fetch_mode;
		if (isset($fetch_mode)) {
			$exec_fetch_mode = $fetch_mode;
		}
		
		// SQL実行
		$stmt = $this->pdo->prepare($query_string);
		$stmt->execute($query_param);
		
		// 結果取得タイプ別に結果を返却
		switch ($result_type) {
			// fetchAll
			case ORTHROS_RESULT_TYPE_FETCH_ALL:
				return $stmt->fetchAll($exec_fetch_mode);
				break;
			// 影響のあった行数
			case ORTHROS_RESULT_TYPE_ROW_COUNT:
				return $stmt->rowCount();
				break;
			// PDOStatement
			case ORTHROS_RESULT_TYPE_STATEMENT:
				return $stmt;
				break;
			// オブジェクト
			case ORTHROS_RESULT_TYPE_OBJECT:
				$return_arr = array();
				while ($row_data = $stmt->fetch($exec_fetch_mode)) {
					$class = new $result_option();			// オプションで指定されたクラスを生成
					foreach ($row_data as $key => $val) {
						// カラム名をそのままプロパティ名としてクラスにセット
						$class->$key = $val;
					}
					$return_arr[] = $class;
				}
				return $return_arr;
				break;
		}
	}
	
	
	/**
	 * キャッシュを有効としたSELECT実行
	 * キャッシュデータがある場合はキャッシュから取得して返す
	 * 
	 * @param	int			$result_type			結果取得タイプ(ORTHROS_RESULT_TYPE_XXXX default:ORTHROS_RESULT_TYPE_FETCH_ALL)
	 * @param	mixed		$result_option			結果取得タイプ用のオプション(default:null)
	 * @return	mixed								result_typeに対応した結果
	 */
	public function execCacheSelect($result_type = ORTHROS_RESULT_TYPE_FETCH_ALL, $result_option = null)
	{
		$cache_key = md5(sprintf('%s/%s/%s/%s/%s/%s/%s', $this->db_host, $this->db_port, $this->db_name, $this->latest_query_string, json_encode($this->latest_query_param), $result_type, json_encode($result_option)));
		$cache_data = self::$cache_host_arr[$this->latest_cache_category]->get($cache_key);
		if (false === $cache_data) {
			// キャッシュから取得出来なかった場合はクエリを実行して結果をキャッシュ
			$cache_data = $this->execQuery($this->latest_query_string, $this->latest_query_param, $result_type, $result_option);
			$expire = self::$cache_setting_arr[$this->latest_cache_category]['default_expire'];
			if ($this->latest_cache_expire) {
				// キャッシュ時間が指定されている場合
				$expire = $this->latest_cache_expire;
			}
			// キャッシュに格納
			self::$cache_host_arr[$this->latest_cache_category]->set($cache_key, $cache_data, 0, $expire);
		}
		return $cache_data;
	}
	
	// ========================================================================
	// クエリ生成メソッド
	// ========================================================================
	
	/**
	 * クエリを生成してクラス変数にセットする
	 * 
	 * @param	int			$query_type				クエリのタイプ指定(ORTHROS_QUERY_TYPE)
	 */
	protected function makeQuery($query_type)
	{
		$this->latest_query_string = '';
		$this->latest_query_param = array();
		switch($query_type)
		{
			// SELECT文
			case ORTHROS_QUERY_TYPE_SELECT:
				$this->latest_query_string = 'SELECT ';
				$this->makeQueryColmun();
				$this->latest_query_string .= sprintf('FROM %s ', $this->latest_table);
				$this->makeQueryJoin();
				$this->makeQueryWhere();
				$this->makeQueryGroupBy();
				$this->makeQueryOrderBy();
				$this->makeQueryLimit();
				$this->makeQueryLock();
				break;
			// INSERT文
			case ORTHROS_QUERY_TYPE_INSERT:
				$this->latest_query_string = sprintf('INSERT INTO %s ', $this->latest_table);
				$this->makeQueryInsertValue();
				break;
			// DELETE文
			case ORTHROS_QUERY_TYPE_DELETE:
				$this->latest_query_string = sprintf('DELETE %s ', $this->latest_table);
				$this->latest_query_string .= sprintf('FROM %s ', $this->latest_table);
				$this->makeQueryJoin();
				$this->makeQueryWhere();
				break;
			// UPDATE文
			case ORTHROS_QUERY_TYPE_UPDATE:
				$this->latest_query_string = sprintf('UPDATE %s ', $this->latest_table);
				$this->makeQueryJoin();
				$this->makeQueryUpdateSet();
				$this->makeQueryWhere();
				$this->makeQueryOrderBy();
				$this->makeQueryLimit();
				break;
		}
		
	}
	
	
	
	/**
	 * カラムの配列 makeQuery補助メソッド
	 */
	protected function makeQueryColmun()
	{
		if (isset($this->latest_column)) {
			$this->latest_query_string .= implode(',', $this->latest_column) . ' ';
		} else {
			$this->latest_query_string .= '* ';
		}
	}
	
	
	
	/**
	 * WHERE句を返す makeQuery補助メソッド
	 */
	protected function makeQueryWhere()
	{
		if (isset($this->latest_where)) {
			$this->latest_query_string .= 'WHERE ';
			$tmp_where_arr = array();
			foreach ($this->latest_where as $condition_type => $column_info_arr) {
				foreach ($column_info_arr as $column_name => $condition) {
					if (ORTHROS_WHERE_EQUAL == $condition_type and is_array($condition)) {
						// = 指定で配列が指定された場合はIN句を使う
						$tmp_where_arr[] = sprintf('%s IN(%s) ', $column_name, implode(',', array_fill(0, count($condition), '?')));
						foreach ($condition as $val) {
							$this->latest_query_param[] = $val;		// パラメータは別に持っておく
						}
					} else {
						$tmp_where_arr[] = sprintf('%s %s ? ', $column_name, $condition_type);
						$this->latest_query_param[] = $condition;		// パラメータは別に持っておく
					}
				}
			}
			
			// ANDで結合
			$this->latest_query_string .= implode('AND', $tmp_where_arr);
		}
	}
	
	
	
	/**
	 * JOIN makeQuery補助メソッド
	 */
	protected function makeQueryJoin()
	{
		if (isset($this->latest_join_table)) {
			foreach ($this->latest_join_table as $table_name => $join_info) {
				$this->latest_query_string .= sprintf('%s JOIN %s ON ', $join_info['join_type'], $table_name);
				$tmp_on_arr = array();
				foreach ($join_info['on_arr'] as $col1 => $col2) {
					$tmp_on_arr[] = sprintf('%s = %s ', $col1, $col2);
				}
				$this->latest_query_string .= implode(',', $tmp_on_arr);
			}
		}
	}
	
	
	
	/**
	 * ORDER BY句 makeQuery補助メソッド
	 */
	protected function makeQueryOrderBy()
	{
		if (isset($this->latest_order_by)) {
			$this->latest_query_string .= 'ORDER BY ';
			$tmp_order_arr = array();
			foreach ($this->latest_order_by as $column_name => $ordery_type) {
				$tmp_order_arr[] = sprintf('%s %s', $column_name, $ordery_type);
			}
			$this->latest_query_string .= implode(',', $tmp_order_arr) . ' ';
		}
	}
	
	
	
	/**
	 * GROUP BY句 makeQuery補助メソッド
	 */
	protected function makeQueryGroupBy()
	{
		if (isset($this->latest_group_by)) {
			$this->latest_query_string .= sprintf('GROUP BY %s ', implode(',', $this->latest_group_by));
		}
	}
	
	
	
	/**
	 * LIMIT句 makeQuery補助メソッド
	 */
	protected function makeQueryLimit()
	{
		if (isset($this->latest_limit)) {
			$this->latest_query_string .= sprintf('LIMIT %s,%s ', $this->latest_limit['offset'], $this->latest_limit['count']);
		}
	}
	
	
	
	/**
	 * ロック makeQuery補助メソッド
	 */
	protected function makeQueryLock()
	{
		if (isset($this->latest_lock_mode)) {
			switch($this->latest_lock_mode) {
				case ORTHROS_LOCK_MODE_FOR_UPDATE:
					$this->latest_query_string .= 'FOR UPDATE ';
					break;
				case ORTHROS_LOCK_MODE_SHARE_MODE:
					$this->latest_query_string .= 'LOCK IN SHARE MODE ';
					break;
			}
		}
	}
	
	
	
	/**
	 * INERTのVALUE makeQuery補助メソッド
	 */
	protected function makeQueryInsertValue()
	{
		$this->latest_query_string .= sprintf('(%s) VALUES ', implode(',', array_keys($this->latest_insert_param[0])));
		$tmp_insert_arr = array();
		$damy_colmun_string = implode(',', array_fill(0, count($this->latest_insert_param[0]), '?'));
		foreach ($this->latest_insert_param as $insert_arr) {
			$tmp_insert_arr[] = sprintf('(%s) ', $damy_colmun_string);
			foreach ($insert_arr as $val) {
				$this->latest_query_param[] = $val;		// パラメータは別に持っておく
			}
		}
		
		$this->latest_query_string .= implode(',', $tmp_insert_arr);
	}
	
	
	
	/**
	 * UPDATEのSET makeQuery補助メソッド
	 */
	protected function makeQueryUpdateSet()
	{
		$this->latest_query_string .= 'SET ';
		$tmp_update_arr = array();
		foreach ($this->latest_update_param as $column_name => $val) {
			$tmp_update_arr[] = sprintf('%s = ? ', $column_name);
			$this->latest_query_param[] = $val;		// パラメータは別に持っておく
		}
		
		$this->latest_query_string .= implode(',', $tmp_update_arr);
	}
	
	// ========================================================================
	// その他補助メソッド
	// ========================================================================
	
	/**
	 * ロックしたテーブルの情報を取得する
	 * @reutrn	string								ロックしたテーブルの情報をまとめた文字列
	 */
	public function getLockTableInfo()
	{
		$return_str = '';
		if (isset($this->latest_table)) {
			// 最後に操作したテーブル(これは必ずしもロックされていないが、lock wait timeout になった場合などに手がかりとなるため残す)
			$return_str .= sprintf('[latest_table] %s ', $this->latest_table);
		}
		
		if (!empty($this->lock_tables)) {
			// ロックしたテーブル情報があるなら順次追加
			foreach ($this->lock_tables as $query_type => $table_arr) {
				// 最初にクエリタイプ
				switch ($query_type) {
					case ORTHROS_QUERY_TYPE_SELECT:
						$return_str .= '[SELECT] ';
						break;
					case ORTHROS_QUERY_TYPE_INSERT:
						$return_str .= '[INSERT] ';
						break;
					case ORTHROS_QUERY_TYPE_DELETE:
						$return_str .= '[DELETE] ';
						break;
					case ORTHROS_QUERY_TYPE_UPDATE:
						$return_str .= '[UPDATE] ';
						break;
				}
				
				// 順次テーブルを追加
				foreach ($table_arr as $lock_table => $lock) {
					if (ORTHROS_QUERY_TYPE_SELECT == $query_type) {
						// SELECT の場合は排他ロックと共有ロックを出し分ける
						if (isset($lock[ORTHROS_LOCK_MODE_FOR_UPDATE])) {
							$return_str .= $lock_table . '(for update) ';
						}
						if (isset($lock[ORTHROS_LOCK_MODE_SHARE_MODE])) {
							$return_str .= $lock_table . '(share mode) ';
						}
					} else {
						$return_str .= $lock_table . ' ';
					}
				}
			}
		}
		
		return $return_str;
	}
	
	
	// ========================================================================
	// 静的メソッド
	// ========================================================================
	
	/**
	 * キャッシュ情報の設定を行う
	 * 
	 * @param	array		$set_attribute_arr		ホスト配列
	 * @param	int			$default_expire			デフォルトのキャッシュ時間(秒)
	 * @param	string		$category				カテゴリ名
	 */
	static public function setCacheHost($host_arr, $default_expire = ORTHROS_DEFAULT_CACHE_EXPIRE_SEC, $category = 'default')
	{
		$memcache = new Memcache();
		foreach ($host_arr as $host_info) {
			$persistent = true;		// 持続的接続はデフォルトtrue
			if (isset($host_info['PERSISTENT'])) {
				$persistent = $host_info['PERSISTENT'];
			}
			$memcache->addServer($host_info['HOST'], $host_info['PORT'], $persistent);
		}
		self::$cache_host_arr[$category] = $memcache;
		self::$cache_setting_arr[$category] = array(
			'default_expire' => $default_expire,
		);
	}
}
