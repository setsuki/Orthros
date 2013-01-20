
◆◇◆◇◆◇◆◇◆◇◆◇◆◇◆◇◆◇◆
　　　　　Orthros
◆◇◆◇◆◇◆◇◆◇◆◇◆◇◆◇◆◇◆

■----------------------------■
　　　　　概要
■----------------------------■
簡単なクエリが流せるORマッパです。
機能的にはORマッパというよりもクエリビルダが近いのかもしれませんが、
クエリビルダよりは少し機能を持たせてあるつもりなのでORマッパと呼んでます。





■----------------------------■
　　　　　使い方
■----------------------------■
PDOを使っています。
必ず事前にインストールを済ませてください。

-- はじめに
Orthros.php をrequireなりincludeなりしてください。
  require('./orthros/Orthros.php');

-- DBに接続
コンストラクタでコネクションまで行われます
  $db = new Orthros('localhost', 3306, 'test', 'root', 'root');

-- SELECT
  $data = $db->table('test_tbl')
               ->where(array('id' => 10))
               ->select();

-- INSERT
  $db->table('test_tbl')
           ->insert(array('id' => 20, 'score' => 80));

-- DELETE
  $db->table('test_tbl')
           ->where(array('id' => 20))
           ->delete();

-- UPDATE
  $db->table('test_tbl')
           ->where(array('id' => 20))
           ->update(array('score' => 100));

基本的な流れは、
テーブル指定 → WHERE句等を指定 → クエリ実行
という感じです。

■----------------------------■
　　　　　作者
■----------------------------■
setsuki とか yukicon とか Yuki Susugi とか名乗ってますが同じ人です。
https://github.com/setsuki
https://twitter.com/yukiconEx



■----------------------------■
　　　　　ライセンス
■----------------------------■
修正BSDライセンスです。
著作権表示さえしてくれれば好きに扱ってくれて構いません。
ただし無保証です。


