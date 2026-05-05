# period-wp-framework

WordPress のテーマ・プラグイン開発向け軽量ライブラリ。MetaBox、カスタム投稿タイプ、スクリプト/スタイル管理、HTML生成などの定型処理をまとめる。

- namespace: `Period\WpFramework`
- エントリポイント: `pwf()`
- WordPress 依存は `src/Infrastructure/WordPress/` に閉じている

## インストール

```bash
composer install
```

```php
require_once __DIR__ . '/bootstrap.php';
$app = pwf();
```

## MetaBox の最小使用例

```php
use Period\WpFramework\Infrastructure\WordPress\MetaBox;

$box = new MetaBox([
    'id'        => 'news_detail',
    'title'     => 'ニュース詳細',
    'post_type' => 'news',
    'fields'    => [
        ['name' => 'lead',       'type' => 'textarea', 'label' => 'リード文'],
        ['name' => 'main_image', 'type' => 'image',    'label' => 'メイン画像'],
        ['name' => 'gallery',    'type' => 'gallery',  'label' => 'ギャラリー'],
    ],
]);

$box->register();
```

管理画面の JS（メディアピッカー・ギャラリー・リピーター）を有効にするには、JS の URL をフィルターで注入する。

```php
add_filter('period_wp_metabox_js_url', function () {
    return get_stylesheet_directory_uri() . '/vendor/period/wp-framework/assets/js/period-wp-metabox.js';
});
```

詳細は [docs/js-loading.md](docs/js-loading.md) を参照。

## PostTypeRegistrar

カスタム投稿タイプ・タクソノミー・MetaBox をまとめて登録する。

```php
$app->posts()
    ->register('news', ['label' => 'ニュース', 'menu_icon' => 'dashicons-media-text'])
    ->metaBox([
        'id'     => 'news_detail',
        'title'  => 'ニュース詳細',
        'fields' => [
            ['name' => 'lead',       'type' => 'textarea'],
            ['name' => 'main_image', 'type' => 'image'],
        ],
    ])
    ->registerTaxonomy('news_category', 'news', ['label' => 'カテゴリー'])
    ->boot();
```

`metaBox()` は `post_type` を省略すると直前の `register()` で指定した投稿タイプを引き継ぐ。

## ScriptStyleRegistrar

```php
$app->assets()
    ->script('app', get_stylesheet_directory_uri() . '/assets/js/app.js', [
        'path'    => get_stylesheet_directory() . '/assets/js/app.js',
        'enqueue' => true,
    ])
    ->style('main', get_stylesheet_directory_uri() . '/assets/css/main.css', [
        'path'    => get_stylesheet_directory() . '/assets/css/main.css',
        'enqueue' => true,
    ]);
```

`path` を渡すと `filemtime` でバージョンハッシュが自動付与される。

## ドキュメント

- [docs/metabox.md](docs/metabox.md) — MetaBox フィールド定義・save() の挙動
- [docs/js-loading.md](docs/js-loading.md) — 管理画面 JS の読み込み方法
- [docs/design-decisions.md](docs/design-decisions.md) — 設計判断の記録
- [docs/testing.md](docs/testing.md) — テスト方針・モック構成
