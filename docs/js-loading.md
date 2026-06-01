# 管理画面 JS の読み込み

## JS ファイルの場所

```
assets/js/period-wp-metabox.js
```

メディアピッカー・ギャラリー・リピーターの追加/削除/並び替え・グループ折りたたみを担う IIFE。WordPress の `wp.media` API と SortableJS に依存するが、どちらも存在しない場合はその機能を無視して動作する。

## なぜ JS を PHP から切り離したか

以前は `MetaBox::printMediaScript()` が PHP ヒアドキュメントで JS を出力していた。これを外部ファイルに切り出した理由は次のとおり。

- PHP ファイル内の JS はエディタの補完・構文チェックが効かない
- JS の変更のたびに PHP のキャッシュが無効化される
- バージョン管理上 PHP と JS の差分が混在する

## 読み込み方法

`enqueueMedia()` は `period_wp_metabox_js_url` フィルターで注入された URL を使って `wp_enqueue_script()` を呼ぶ。**フィルターが URL を返さない場合は enqueue をスキップする**（エラーにはならない）。

```php
add_filter('period_wp_metabox_js_url', function (): string {
    return get_stylesheet_directory_uri() . '/vendor/period/wp-kit/assets/js/period-wp-metabox.js';
});
```

## なぜ `plugins_url()` を使わないか

このライブラリはプラグインではなく、テーマや任意ディレクトリから Composer で読み込まれる前提で設計されている。`plugins_url()` はファイルがプラグインディレクトリ配下にあることを前提とするため、テーマ経由で使われた場合に正しい URL を返せない。URL の解決は呼び出し側が担い、ライブラリ側は受け取るだけにする。

## バージョン（filemtime）

JS ファイルが実際に存在する場合、`filemtime()` でファイルの更新日時を取得してバージョンとして使用する。ファイルが存在しない場合は `null`（WordPress のデフォルト動作）になる。PHP エラーは発生しない。

```php
$jsPath = dirname(__DIR__, 3) . '/assets/js/period-wp-metabox.js';
$version = file_exists($jsPath) ? (filemtime($jsPath) ?: null) : null;

wp_enqueue_script('period-wp-metabox', $jsUrl, [], $version, true);
```

## SortableJS

`assets/vendor/sortable/Sortable.min.js` が存在し、`plugins_url()` が利用可能な場合のみ enqueue される。ギャラリー・リピーターの並び替えはこれがなくても機能する（D&D が無効になるだけ）。

## フィルターを設定しない場合の挙動

`period_wp_metabox_js_url` フィルターが null または空文字列を返す場合、`wp_enqueue_script()` は呼ばれない。管理画面の HTML は出力されるが、JS は動作しない。
