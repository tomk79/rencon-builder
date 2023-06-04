# rencon-builder

A one filed PHP framework.

## 更新履歴 - Change Log

### tomk79/rencon-builder v0.1.0 (リリース日未定)

- PHP v8.2 に対応した。
- `middleware` がセットされていない場合にエラーが起きる不具合を修正。
- `$conf->realpath_private_data_dir` を追加。
- `rencon-builder.json` に `version` を追加。
- リソースの `Content-type` ヘッダーが出力されない不具合を修正。
- 初期セットアップ画面から最初の管理ユーザーを作成できるようになった。

### tomk79/rencon-builder v0.0.2 (2021年6月17日)

- 依存性走査で、 `php` と `ext-*` を解決しようとしてエラーが起きていた問題を修正。
- 依存性走査で、 `autoload->files` に対応。
- `rencon-builder.json` に `middleware` を追加。
- テーマとコンテンツから、 `$rencon` の名前でアクセスできるようになった。
- `$rencon->resources()` を追加した。
- `$rencon->user()` を追加した。
- `$rencon->app_id()` を追加した。
- `$rencon->app_name()` を追加した。
- Not Found ページ、 Forbidden ページを追加した。
- その他、いくつかの細かい修正。

### tomk79/rencon-builder v0.0.1 (2021年6月16日)

- Initial release.


## ライセンス - License

MIT License


## 作者 - Author

- Tomoya Koyanagi <tomk79@gmail.com>
- website: <https://www.pxt.jp/>
- Twitter: @tomk79 <https://twitter.com/tomk79/>
