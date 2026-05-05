# 使用例リファレンス

period-wp-framework の主要機能をコピペ可能な形でまとめたリファレンスです。

---

## 1. 基本セットアップ

```php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/vendor/period/wp-framework/bootstrap.php';
$app = pwf(); // Application シングルトン
```

`pwf()` は `bootstrap.php` で定義されたグローバル関数で、`Application` インスタンスを返します。

---

## 2. Application API

→ [usage-template-tags.md](usage-template-tags.md)

```php
// HTML ドキュメント生成
echo pwf()->document('<h1>Hello</h1>', [
    'body_class'        => ['home'],
    'head_elements'     => ['<meta name="description" content="説明">'],
    'include_wp_head'   => true,
    'include_wp_footer' => true,
]);

// ページタイトル取得
echo pwf()->title(); // "About Us | My Site"

// サイト情報
$site = pwf()->site();
echo $site->name();

// アセット登録
pwf()->assets()
    ->script('app', get_stylesheet_directory_uri() . '/assets/js/app.js', ['enqueue' => true])
    ->style('main', get_stylesheet_directory_uri() . '/assets/css/main.css', ['enqueue' => true]);

// カスタム投稿タイプ登録
pwf()->posts()
    ->register('news', ['label' => 'ニュース'])
    ->metaBox(['id' => 'news_detail', 'title' => '詳細', 'fields' => [
        ['name' => 'lead', 'type' => 'textarea', 'label' => 'リード文'],
    ]])
    ->boot();

// フック登録（ShortcodeRegistrar, PostClassEnhancer など）
pwf()->boot();
```

---

## 3. WordPress 情報取得

### SiteInfo

→ [usage-site-info.md](usage-site-info.md)

```php
use Period\WpFramework\Infrastructure\WordPress\SiteInfo;

$info = new SiteInfo();
$info->name();        // get_bloginfo('name')
$info->description(); // get_bloginfo('description')
$info->charset();     // get_bloginfo('charset')
$info->language();    // get_bloginfo('language')
$info->url();         // home_url()
$info->themeUri();    // get_stylesheet_directory_uri()
```

### TitleResolver

→ [usage-title-resolver.md](usage-title-resolver.md)

```php
use Period\WpFramework\Infrastructure\WordPress\TitleResolver;
use Period\WpFramework\Infrastructure\WordPress\SiteInfo;

$resolver = new TitleResolver(new SiteInfo());
$resolver->title();           // ページタイトルのみ
$resolver->siteTitle(' | '); // "タイトル | サイト名"
```

### TemplateFormatter

`{{ key }}` プレースホルダーを置換する WordPress 非依存クラスです。→ [usage-template-formatter.md](usage-template-formatter.md)

```php
use Period\WpFramework\Support\TemplateFormatter;

$formatter = new TemplateFormatter();
$result = $formatter->format(
    '{{ title }} | {{ site_name }}',
    ['title' => 'About', 'site_name' => 'My Site']
);
// → "About | My Site"
```

`apply_filters` を適用したい場合は呼び出し側で行います。

```php
if (function_exists('apply_filters')) {
    $result = (string) apply_filters('my_theme_title', $result);
}
```

---

## 4. HTML 文書レンダリング

### DocumentRenderer（統合）

→ [usage-document-renderer.md](usage-document-renderer.md)

```php
use Period\WpFramework\Infrastructure\WordPress\DocumentRenderer;

echo (new DocumentRenderer())->render('<main>...</main>', [
    'head_elements'     => ['<meta name="robots" content="noindex">'],
    'body_class'        => ['page-about'],
    'include_wp_head'   => true,
    'include_wp_footer' => true,
]);
```

### StartHtmlRenderer

→ [usage-start-html.md](usage-start-html.md)

```php
use Period\WpFramework\Infrastructure\WordPress\StartHtmlRenderer;

echo (new StartHtmlRenderer())->render([
    'charset'         => 'UTF-8',
    'elements'        => ['<meta name="viewport" content="width=device-width">'],
    'include_wp_head' => true,
]);
```

### BodyRenderer

→ [usage-body-renderer.md](usage-body-renderer.md)

```php
use Period\WpFramework\Infrastructure\WordPress\BodyRenderer;

echo (new BodyRenderer())->render([
    'class'                => ['home', 'dark'],
    'include_wp_body_open' => true,
]);
```

### EndHtmlRenderer

```php
use Period\WpFramework\Infrastructure\WordPress\EndHtmlRenderer;

echo (new EndHtmlRenderer())->render(['include_wp_footer' => true]);
```

---

## 5. Assets

```php
pwf()->assets()
    ->script('app', get_stylesheet_directory_uri() . '/js/app.js', [
        'path'    => get_stylesheet_directory() . '/js/app.js',
        'deps'    => ['jquery'],
        'enqueue' => true,
    ])
    ->style('main', get_stylesheet_directory_uri() . '/css/main.css', [
        'path'    => get_stylesheet_directory() . '/css/main.css',
        'enqueue' => true,
    ])
    ->inlineScript('app', 'console.log("ready");')
    ->inlineStyle('main', 'body { margin: 0; }');
```

---

## 6. PostType + MetaBox

### PostTypeRegistrar

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

### MetaBox フィールド型

→ [metabox.md](metabox.md)

```php
['name' => 'text_field',   'type' => 'text']
['name' => 'body',         'type' => 'textarea']
['name' => 'flag',         'type' => 'checkbox']
['name' => 'status',       'type' => 'select',   'options' => ['draft' => '下書き', 'pub' => '公開']]
['name' => 'thumb',        'type' => 'image']
['name' => 'file',         'type' => 'media']
['name' => 'gallery',      'type' => 'gallery']
['name' => 'items',        'type' => 'repeater', 'fields' => [
    ['name' => 'title', 'type' => 'text', 'label' => 'タイトル'],
]]
```

ラベルのカスタマイズは `labels` 配列を使います（`button_label` 等は deprecated）。

```php
[
    'name'   => 'thumb',
    'type'   => 'image',
    'labels' => ['select_image' => '画像を選択', 'clear' => 'クリア'],
]
```

---

## 7. WordPress フック・ショートコード

### HookRegistrar

`add_action` / `add_filter` / `add_shortcode` を統一的に登録します。WordPress がない環境では noop です。→ [usage-hooks.md](usage-hooks.md)

```php
use Period\WpFramework\Infrastructure\WordPress\HookRegistrar;

(new HookRegistrar())
    ->action('init', function (): void { /* ... */ })
    ->filter('the_content', function (string $c): string { return $c; })
    ->shortcode('my_tag', function (): string { return '<p>Hello</p>'; });
```

### ShortcodeRegistrar

`HookRegistrar` を使って `[document]` / `[title]` / `[site_name]` を登録する便利クラスです。

```php
use Period\WpFramework\Infrastructure\WordPress\ShortcodeRegistrar;

(new ShortcodeRegistrar())->register();
```

---

## 8. Renderer 系

### PageNavigationRenderer

```php
use Period\WpFramework\Infrastructure\WordPress\PageNavigationRenderer;

echo (new PageNavigationRenderer())->render([
    'aria_label' => 'ページナビゲーション',
    'prev_text'  => '前へ',
    'next_text'  => '次へ',
]);
```

### ImageTagRenderer

WordPress 添付ファイルから `<img>` タグを生成します。`picture` / `figure` は対象外です。→ [usage-image-renderer.md](usage-image-renderer.md)

```php
use Period\WpFramework\Infrastructure\WordPress\ImageTagRenderer;

echo (new ImageTagRenderer())->render(123, [
    'size'    => 'large',
    'lazy'    => true,
    'wrapper' => true,
]);
```

> **Deprecated**: `ImageRenderer` は `ImageTagRenderer` の deprecated エイリアスです。

---

## 9. Support / View ユーティリティ

### Element（HTML ビルダー）

```php
use Period\WpFramework\View\Element;
use Period\WpFramework\View\RawHtml;

Element::el('a', ['href' => '/about', 'class' => ['btn', 'btn-lg']], 'About');
// → <a href="/about" class="btn btn-lg">About</a>

Element::void('img', ['src' => '/logo.png', 'alt' => 'Logo']);
(new Element('div', ['id' => 'wrap']))->open()->render(); // → <div id="wrap">
Element::class(['btn', null, 'btn-lg', 'btn']);           // → "btn btn-lg"
Element::el('div', [], new RawHtml('<span>raw</span>'));

// コメント / CDATA（RawHtml を返す）
Element::comment('debug')->render();       // → <!-- debug -->
Element::cdata('var a = 1;')->render();    // → <![CDATA[var a = 1;]]>

// 空なら出力しない
Element::elIfNotEmpty('p', [], '');        // → ''
Element::elIfNotEmpty('p', [], 'Hello');   // → <p>Hello</p>

// 複数要素の生成は array_map で行う
echo implode('', array_map(
    fn(string $item) => Element::el('li', [], $item),
    ['A', 'B', 'C']
));
// → <li>A</li><li>B</li><li>C</li>
```

#### strip_tags 相当について

HTML 生成（`Element`）の責務はタグ構造の組み立てに限定します。テキストのタグ除去が必要な場合は、呼び出し側で `strip_tags()` を使ってください。将来的に `TextUtil` として分離する可能性があります。

```php
Element::el('p', [], strip_tags($userInput));
```

### ArrayUtil

配列のリスト判定と連想配列判定を提供します。

```php
use Period\WpFramework\Support\ArrayUtil;

ArrayUtil::isList([1, 2, 3]);                    // → true
ArrayUtil::isList(['a' => 1, 'b' => 2]);         // → false
ArrayUtil::isList([0 => 'a', 2 => 'b']);         // → false（欠番あり）
ArrayUtil::isAssociative(['a' => 1, 'b' => 2]);  // → true
ArrayUtil::isAssociative([1, 2, 3]);             // → false
```

PHP 8.1 以上では `array_is_list()` を使用し、未満では同等の fallback を使用します。

### CssName / ImageUtil / LineEnding / Encoding

```php
use Period\WpFramework\Support\CssName;
use Period\WpFramework\Support\ImageUtil;
use Period\WpFramework\Support\LineEnding;
use Period\WpFramework\Support\Encoding;

CssName::fromString('Hello World');        // → "hello-world"
CssName::fromUrl('https://example.com/about/'); // → "about"

ImageUtil::orientation(1920, 1080);        // → "landscape"
ImageUtil::aspectRatio(1920, 1080);        // → "16/9"

$newline = LineEnding::LF;                 // "\n"
Encoding::decodeHtmlEntities('&lt;p&gt;'); // → "<p>"

// 文字を hex 表現に変換
Encoding::charToHex('A');            // → '\x41'
Encoding::charToHex('A', '%');       // → '%41'
Encoding::charToHex('\x41');         // → '\x41'（既に hex 形式ならそのまま）

// Unicode コードポイントを UTF-8 文字に変換（mb_chr なしでも動作）
Encoding::codepointToUtf8(65);       // → 'A'
Encoding::codepointToUtf8(0xE9);     // → 'é'
Encoding::codepointToUtf8(0x3042);   // → 'あ'
Encoding::codepointToUtf8(0x1F600);  // → '😀'
```

---

## 10. i18n / Translator

`Translator` はテンプレート層・呼び出し側で使います。ライブラリ内部ロジックへの注入は行いません。

```php
use Period\WpFramework\Infrastructure\WordPress\Translator;

$t = new Translator('my-text-domain');
$t->text('Save');                          // __('Save', 'my-text-domain')
$t->html('Save');                          // esc_html__(...)
$t->attr('Save');                          // esc_attr__(...)
$t->plural('%d item', '%d items', $count); // _n(...)
```

`pwf()->translator()` で共有インスタンスを取得できます。

MetaBox のラベルを翻訳する場合は `labels` に渡します。

```php
$t = pwf()->translator();
$field = [
    'name'   => 'thumb',
    'type'   => 'image',
    'labels' => [
        'select_image' => $t->text('Select image'),
        'clear'        => $t->text('Clear'),
    ],
];
```
