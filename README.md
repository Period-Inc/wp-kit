# period-wp-framework

WordPress のテーマ・プラグイン開発向け軽量ライブラリ。MetaBox、カスタム投稿タイプ、スクリプト/スタイル管理、HTML生成などの定型処理をまとめる。

- namespace: `Period\WpFramework`
- エントリポイント: `pwf()`
- WordPress 依存は `src/Infrastructure/WordPress/` に閉じている
- WordPress 非依存ユーティリティは `src/Support/` に属する

## セットアップ

```bash
composer require period/wp-framework
```

```php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/vendor/period/wp-framework/bootstrap.php';
$app = pwf();
```

## 基本使用（pwf）

```php
// HTML ドキュメント生成
echo pwf()->document('<h1>Hello</h1>');

// ページタイトル
echo pwf()->title();

// サイト情報
echo pwf()->site()->name();
```

## HTML レンダリング

```php
echo pwf()->document('<main>...</main>', [
    'body_class'        => ['home'],
    'head_elements'     => ['<meta name="description" content="説明">'],
    'include_wp_head'   => true,
    'include_wp_footer' => true,
]);
```

## WordPress 連携（HookRegistrar）

`HookRegistrar` は `add_action` / `add_filter` / `add_shortcode` を統一的に登録する基盤です。WordPress がない環境では noop になります。

```php
use Period\WpFramework\Infrastructure\WordPress\HookRegistrar;

$hooks = new HookRegistrar();
$hooks
    ->action('init', function (): void { /* ... */ })
    ->filter('the_content', function (string $c): string { return $c; })
    ->shortcode('my_tag', function (): string { return '<p>Hello</p>'; });
```

ショートコード登録には `ShortcodeRegistrar` も使えます。

```php
use Period\WpFramework\Infrastructure\WordPress\ShortcodeRegistrar;

(new ShortcodeRegistrar())->register(); // [document] [title] [site_name]
```

## データ取得（SiteInfo / TitleResolver）

```php
use Period\WpFramework\Infrastructure\WordPress\SiteInfo;
use Period\WpFramework\Infrastructure\WordPress\TitleResolver;

$info     = new SiteInfo();
$resolver = new TitleResolver($info);

$info->name();            // サイト名
$resolver->siteTitle();  // "タイトル | サイト名"
```

## MetaBox

```php
pwf()->posts()
    ->register('news', ['label' => 'ニュース', 'menu_icon' => 'dashicons-media-text'])
    ->metaBox([
        'id'     => 'news_detail',
        'title'  => 'ニュース詳細',
        'fields' => [
            ['name' => 'lead',       'type' => 'textarea', 'label' => 'リード文'],
            ['name' => 'main_image', 'type' => 'image',    'label' => 'メイン画像'],
        ],
    ])
    ->registerTaxonomy('news_category', 'news', ['label' => 'カテゴリー'])
    ->boot();
```

ボタンラベルの指定は `labels` 配列を使います（`button_label` 等は deprecated）。

```php
['name' => 'thumb', 'type' => 'image', 'labels' => ['select_image' => '画像を選択', 'clear' => 'クリア']]
```

管理画面 JS の読み込みは [docs/js-loading.md](docs/js-loading.md) を参照。

## ユーティリティ（Support）

```php
use Period\WpFramework\Support\TemplateFormatter;
use Period\WpFramework\Support\CssName;
use Period\WpFramework\Support\ImageUtil;

// {{ key }} 置換（WordPress 非依存）
(new TemplateFormatter())->format('{{ title }} | {{ site }}', ['title' => 'About', 'site' => 'My Site']);

// CSS クラス名生成
CssName::fromString('Hello World'); // → "hello-world"

// 画像向き判定
ImageUtil::orientation(1920, 1080); // → "landscape"
```

## i18n（Translator）

`Translator` はテンプレート層・呼び出し側で使います。内部ロジックへの注入は行いません。

```php
$t = pwf()->translator();
echo $t->html('Save'); // esc_html__('Save', 'period-wp-framework')
```

MetaBox のラベルを翻訳したい場合は呼び出し側で `labels` に渡します。

```php
$t = pwf()->translator();
new MetaBox([
    'id'     => 'sample',
    'post_type' => 'post',
    'fields' => [[
        'name'   => 'thumb',
        'type'   => 'image',
        'labels' => ['select_image' => $t->text('Select image'), 'clear' => $t->text('Clear')],
    ]],
]);
```

## ドキュメント

- [docs/usage.md](docs/usage.md) — 使用例リファレンス（全機能）
- [docs/metabox.md](docs/metabox.md) — MetaBox フィールド定義・save() の挙動
- [docs/usage-metabox.md](docs/usage-metabox.md) — MetaBox 使用例（gallery / repeater）
- [docs/usage-image-renderer.md](docs/usage-image-renderer.md) — ImageTagRenderer（img タグ生成）
- [docs/js-loading.md](docs/js-loading.md) — 管理画面 JS の読み込み方法
- [docs/usage-site-info.md](docs/usage-site-info.md) — SiteInfo（サイト情報取得）
- [docs/usage-title-resolver.md](docs/usage-title-resolver.md) — TitleResolver（ページタイトル取得）
- [docs/usage-template-formatter.md](docs/usage-template-formatter.md) — TemplateFormatter（WordPress 非依存テンプレート整形）
- [docs/usage-body-renderer.md](docs/usage-body-renderer.md) — BodyRenderer（body タグ生成）
- [docs/usage-document-renderer.md](docs/usage-document-renderer.md) — DocumentRenderer（完全な HTML ドキュメント生成）
- [docs/usage-hooks.md](docs/usage-hooks.md) — HookRegistrar / ShortcodeRegistrar（action / filter / shortcode 登録）
- [docs/usage-template-tags.md](docs/usage-template-tags.md) — Template Tags（pwf()->title() / site() / document()）
- [docs/migration.md](docs/migration.md) — v1 → v2 移行ガイド・非推奨項目一覧
- [docs/design-decisions.md](docs/design-decisions.md) — 設計判断の記録
- [docs/testing.md](docs/testing.md) — テスト方針・モック構成
