<?php

$conf = new \stdClass();


/* --------------------------------------
 * ログインユーザーのIDとパスワードの対
 * 
 * rencon の初期画面は、ログイン画面から始まります。
 * `$conf->users` に 登録されたユーザーが、ログインを許可されます。
 * ユーザーIDを キー に、sha1ハッシュ化されたパスワード文字列を 値 に持つ連想配列で設定してください。
 * ユーザーは、複数登録できます。
 */
$conf->users = array(
	"admin" => sha1("admin"),
	"admin2" => array(
		"name" => "Admin 2",
		"id" => "admin2",
		"pw" => sha1("admin2"),
	),
);

/* --------------------------------------
 * 非公開データディレクトリのパス
 */
$conf->realpath_private_data_dir = __DIR__.'/'.basename(__FILE__, '.php').'__data/';

/* --------------------------------------
 * DB接続情報
 */
$conf->databases = null;

?>