# period-wp-framework

`period-wp-framework` は、WordPress のテーマ制作やプラグイン開発を支援する軽量ライブラリです。

- entry function: `pwf()`
- namespace: `Period\WpFramework`
- WordPress 依存は `Infrastructure` に閉じています

## 概要

このライブラリは、WordPress 固有の機能と汎用的なユーティリティを分離して提供します。

- `Support`: WordPress に依存しないヘルパーとユーティリティ
- `Infrastructure`: WordPress 固有のラッパーと拡張機能
- `Application`: ライブラリの中心となるエントリポイント

## セットアップ

```bash
composer install
```

PHP ファイルに `bootstrap.php` を読み込んでください。

```php
require_once __DIR__ . '/bootstrap.php';
$app = pwf();
```

## 基本構成

### Support
`Support` には、テンプレート描画、URL 操作、HTTP クライアントなどの汎用機能があります。

### Infrastructure
`Infrastructure` は WordPress 固有の機能を包含します。`MetaBox`、`PostTypeRegistrar`、`ScriptStyleRegistrar` などがここに含まれます。

### Application
`Application` は、`Support` と `Infrastructure` を結びつけるエントリポイントです。

## Assets (ScriptStyleRegistrar)

スクリプトとスタイルの登録をラップし、`enqueue` もサポートします。

```php
$app->assets()
    ->script(
        'app',
        get_stylesheet_directory_uri() . '/assets/js/app.js',
        [
            'path' => get_stylesheet_directory() . '/assets/js/app.js',
            'enqueue' => true,
        ]
    )
    ->style(
        'main',
        get_stylesheet_directory_uri() . '/assets/css/main.css',
        [
            'path' => get_stylesheet_directory() . '/assets/css/main.css',
            'enqueue' => true,
        ]
    );
```

## PostTypeRegistrar

カスタム投稿タイプとタクソノミーをラップして登録できます。

```php
$app->posts()
    ->register('news', [
        'label' => 'ニュース',
        'menu_icon' => 'dashicons-media-text',
    ])
    ->registerTaxonomy('news_category', 'news', [
        'label' => 'ニュースカテゴリー',
    ])
    ->boot();
```

## MetaBox

WordPress のメタボックスを簡単に定義できます。

```php
use Period\WpFramework\Infrastructure\WordPress\MetaBox;

new MetaBox([
    'id' => 'test',
    'post_type' => 'post',
    'fields' => [
        [
            'name' => 'title',
            'type' => 'text',
        ],
    ],
]);
```

### repeater / gallery

`repeater` フィールドは JSON 形式で保存され、並び替えに対応しています。

```php
[
    'type' => 'repeater',
    'name' => 'items',
    'fields' => [
        ['name' => 'title', 'type' => 'text'],
        ['name' => 'image', 'type' => 'image'],
    ],
]
```

- 保存形式: JSON
- 並び替え可能

## Shortcode

`Infrastructure` には URL 取得や投稿取得を補助するショートコードがあります。

- `fetch_title`
- `posts`
- `template_url`

例:

```text
[fetch_title url="https://example.com"]
```

```text
[posts tax_query='[{"taxonomy":"category","field":"slug","terms":["news"]}]']
```

## docs へのリンク

- `docs/usage-metabox.md`
- `docs/usage-tax-query.md`

## 主な Support 機能

- `HtmlTemplate`: プレーンなテンプレート描画
- `Url`: URL 操作
- `HttpClient`: HTTP リクエスト
- `CssName`: CSS class / id 変換

---

`Legacy` フォルダは旧資産の保管用です。新規コードでは直接参照しないでください。
