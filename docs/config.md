# $conf

## $conf->realpath_private_data_dir

データディレクトリのパス設定します。

```php
/* --------------------------------------
 * 非公開データディレクトリのパス
 */
$conf->realpath_private_data_dir = __DIR__.'/'.basename(__FILE__, '.php').'__data/';
```

## $conf->users

ユーザー情報を定義します。

```php
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
```

## $conf->api_keys

APIキーを定義します。

```php
/* --------------------------------------
 * APIキー
 */
$conf->api_keys = array(
	"xxxxx-xxxxx-xxxxxxxxxxx-xxxxxxx" => array(
		"created_by" => "admin", // 作成したユーザーのID
		"permissions" => array( // このAPIキーで許可された項目
			"foo1",
			"foo2",
			"bar1",
		),
	),
);
```

## $conf->databases

データベースへの接続情報を定義します。
