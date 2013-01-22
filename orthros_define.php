<?php
/**
 * ORマッパOrthros関係定義ファイル
 *
 * @package		Orthros
 * @author		setsuki (yukicon)
 * @copyright	Susugi Ningyoukan
 * @license		BSD
 **/

// WHERE句用の種別
define('ORTHROS_WHERE_EQUAL', '=');
define('ORTHROS_WHERE_NOT_EQUAL', '!=');
define('ORTHROS_WHERE_GREATER', '>');
define('ORTHROS_WHERE_GREATER_EQUAL', '>=');
define('ORTHROS_WHERE_LESS', '<');
define('ORTHROS_WHERE_LESS_EQUAL', '<=');
define('ORTHROS_WHERE_LIKE', 'LIKE');

// JOINの種別
define('ORTHROS_JOIN_INNER', 'INNER');
define('ORTHROS_JOIN_LEFT', 'LEFT');
define('ORTHROS_JOIN_RIGHT', 'LEFT');
define('ORTHROS_JOIN_FULL_OUTER', 'FULL OUTER');

// ORDER BYの種別
define('ORTHROS_ORDER_ASC', 'ASC');
define('ORTHROS_ORDER_DESC', 'DESC');

// クエリの種別
define('ORTHROS_QUERY_TYPE_SELECT', 1);
define('ORTHROS_QUERY_TYPE_INSERT', 2);
define('ORTHROS_QUERY_TYPE_DELETE', 3);
define('ORTHROS_QUERY_TYPE_UPDATE', 4);

// 結果取得の種別
define('ORTHROS_RESULT_TYPE_FETCH_ALL', 1);			// fetchAllした結果を返す
define('ORTHROS_RESULT_TYPE_ROW_COUNT', 2);			// 影響のあった行数を返す
define('ORTHROS_RESULT_TYPE_STATEMENT', 3);			// PDOStatementをそのまま返す
define('ORTHROS_RESULT_TYPE_OBJECT', 4);			// 指定されたクラスのオブジェクトを生成して値をセットして返す

// ロックの種別
define('ORTHROS_LOCK_MODE_FOR_UPDATE', 1);			// 排他ロック
define('ORTHROS_LOCK_MODE_SHARE_MODE', 2);			// 共有ロック

