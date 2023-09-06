# 基本的なビルドの手順

rencon-builder を使用してPHPファイル群をビルドする手順について説明します。

## 定義ファイルを作成する

### ./composer.json

パッケージのルートディレクトリに `composer.json` を配置します。

rencon-builder は、`composer.json` の内容から、 `require`、 `autoload->psr-4`、`autoload->files` を読み取り、定義されたPHPスクリプトを収集し、結合します。

```json
{
    "require": {
        "tomk79/filesystem": "*"
    },
    "autoload": {
        "psr-4": {
            "app01\\": [
                "src"
            ]
        },
        "files": [
            "php/sample.php"
        ]
    }
}
```


### ./rencon-builder.json

`composer.json` と同じ階層に、 `rencon-builder.json` を配置します。

```json
{
    "name": "Application Sample",
    "app_id": "app01",
    "version": "1.0.0",
    "dist": "dist/app01.php",
    "resources": "resources/",
    "middleware": [
        "app01\\middleware\\sample::middleware"
    ],
    "route": {
        "": {
            "title": "Home",
            "page": "app/pages/index.php"
        },
        "dynamic.{routeParam1?}.route": {
            "title": "Dinamic route",
            "page": "app01\\dinamicRoute::start"
        },
        "test": {
            "title": "Test",
            "page": "app01\\test::start"
        },
        "test.post": {
            "title": "Post Test",
            "allow_methods": "post",
            "page": "app01\\test::post"
        }
    },
    "theme": "app/theme/theme.php",
    "config_template": "app/config.php"
}
```

#### name

作成するアプリケーションの名前を設定します。

#### app_id

作成するアプリケーションのIDを設定します。

#### version

作成するアプリケーションのバージョン番号を設定します。

#### dist

ビルドしたアプリケーションを出力する先のパスを指定します。

#### resources

リソースファイル(画像やCSS、JSなど)を格納したディレクトリのパスを指定します。

例えば、`resources` に `resources/` を指定した場合、 `resources/foo/bar.png` にアクセスするパスは `xxx.php?res=foo/bar.png` となります。

#### middleware

ミドルウェアとして処理するスクリプトを登録します。
配列で複数登録することができます。

登録したミドルウェアは、ルーティングの前に登録した順に実行されます。

#### route

ルーティングを設定します。

`a` パラメータの値を表す文字列をキーに設定します。
例えば、 `foo.bar` は、 `xxx.php?a=foo.bar` にマッチします。

動的なルーティングを設定するには、 `{hogefuga?}` の形式を利用できます。

例えば、 `foo.{hogefuga?}.bar` は、 `xxx.php?a=foo.aaa.bar` や `xxx.php?a=foo.bbb.bar` にマッチします。
マッチした文字列は、割り当てた関数内で `$rencon->req->get_route_param('hogefuga')` で取得することができます。

#### route.title

ページのタイトルを割り当てます。

#### route.allow_methods

許容するHTTPメソッドを定義します。
文字列で、または配列で複数指定することができます。

省略した場合、デフォルトは `get` です。

#### route.page

実行するスクリプトを割り当てます。

PHPファイルのパスを割り当てた場合は、そのスクリプトを先頭から実行します。
静的なメソッド名を割り当てた場合は、そのメソッドに、引数 `$rencon` を渡して実行します。

#### theme

テーマを処理するPHPファイルを指定します。

#### config_template

コンフィグのテンプレートとなるPHPファイルを指定します。

コンフィグの書き方や設定項目についての詳細は、[config](./config.md) のページを参照してください。


## ビルドコマンドを実行する

```bash
php ./vendor/tomk79/rencon-builder/rencon-builder.phar
```
