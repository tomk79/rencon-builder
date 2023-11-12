# rencon-builder

A single file PHP framework.

## Install

```bash
composer require tomk79/rencon-builder
```


## Document

- [rencon-builder document](https://tomk79.github.io/rencon-builder/)


## 更新履歴 - Change Log

### tomk79/rencon-builder v0.3.0 (2023年11月13日)

- アカウントロック機能を追加した。
- APIのルーティング機能と、APIキーの管理機能を追加した。
- エラーログファイルに `.php` を与えて隠蔽するようになった。
- `$rencon->logger()` を追加。
- `$rencon->lock()`、`$rencon->is_locked()`、`$rencon->unlock()`、`$rencon->touch_lockfile()` を追加。
- bin を公開した。
- ユーザーディレクトリ名を `admin_users` から `users` に改名した。
- ログと内部管理される時刻情報を ISO 8601 形式 に変更した。
- その他、いくつかの細かい修正。

### tomk79/rencon-builder v0.2.0 (2023年8月29日)

- ルーティング設定に `allow_methods` を追加した。
- パラメータ名 `ADMIN_USER_CSRF_TOKEN` を `CSRF_TOKEN` に変更した。
- `$rencon` に動的な値を追加できるようになった。
- エラーログを保存するようになった。

### tomk79/rencon-builder v0.1.0 (2023年8月26日)

- PHP v8.2 に対応した。
- `middleware` がセットされていない場合にエラーが起きる不具合を修正。
- `$conf->realpath_private_data_dir` を追加。
- `rencon-builder.json` に `version` を追加。
- 動的ルーティング機能を追加。
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
