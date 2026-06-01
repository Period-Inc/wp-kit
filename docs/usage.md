# 使用例リファレンス

period-wp-kit の主要機能をコピペ可能な形でまとめたリファレンスです。

---

## 1. 基本セットアップ

```php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/vendor/period/wp-kit/bootstrap.php';
$app = pwk(); // Application シングルトン
```

`pwk()` は `bootstrap.php` で定義されたグローバル関数で、`Application` インスタンスを返します。

---

## 2. Application API

→ [usage-template-tags.md](usage-template-tags.md)

```php
// HTML ドキュメント生成
echo pwk()->document('<h1>Hello</h1>', [
    'body_class'        => ['home'],
    'head_elements'     => ['<meta name="description" content="説明">'],
    'include_wp_head'   => true,
    'include_wp_footer' => true,
]);

// ページタイトル取得
echo pwk()->title(); // "About Us | My Site"

// サイト情報
$site = pwk()->site();
echo $site->name();

// アセット登録
pwk()->assets()
    ->script('app', get_stylesheet_directory_uri() . '/assets/js/app.js', ['enqueue' => true])
    ->style('main', get_stylesheet_directory_uri() . '/assets/css/main.css', ['enqueue' => true]);

// カスタム投稿タイプ登録
pwk()->posts()
    ->register('news', ['label' => 'ニュース'])
    ->metaBox(['id' => 'news_detail', 'title' => '詳細', 'fields' => [
        ['name' => 'lead', 'type' => 'textarea', 'label' => 'リード文'],
    ]])
    ->boot();

// フック登録（ShortcodeRegistrar, PostClassEnhancer など）
pwk()->boot();
```

---

## 3. WordPress 情報取得

### SiteInfo

→ [usage-site-info.md](usage-site-info.md)

```php
use Period\WpKit\Infrastructure\WordPress\SiteInfo;

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
use Period\WpKit\Infrastructure\WordPress\TitleResolver;
use Period\WpKit\Infrastructure\WordPress\SiteInfo;

$resolver = new TitleResolver(new SiteInfo());
$resolver->title();           // ページタイトルのみ
$resolver->siteTitle(' | '); // "タイトル | サイト名"
```

### TemplateFormatter

`{{ key }}` プレースホルダーを置換する WordPress 非依存クラスです。→ [usage-template-formatter.md](usage-template-formatter.md)

```php
use Period\WpKit\Support\TemplateFormatter;

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
use Period\WpKit\Infrastructure\WordPress\DocumentRenderer;

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
use Period\WpKit\Infrastructure\WordPress\StartHtmlRenderer;

echo (new StartHtmlRenderer())->render([
    'charset'         => 'UTF-8',
    'elements'        => ['<meta name="viewport" content="width=device-width">'],
    'include_wp_head' => true,
]);
```

### BodyRenderer

→ [usage-body-renderer.md](usage-body-renderer.md)

```php
use Period\WpKit\Infrastructure\WordPress\BodyRenderer;

echo (new BodyRenderer())->render([
    'class'                => ['home', 'dark'],
    'include_wp_body_open' => true,
]);
```

### EndHtmlRenderer

```php
use Period\WpKit\Infrastructure\WordPress\EndHtmlRenderer;

echo (new EndHtmlRenderer())->render(['include_wp_footer' => true]);
```

---

## 5. Assets

```php
pwk()->assets()
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
pwk()->posts()
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
use Period\WpKit\Infrastructure\WordPress\HookRegistrar;

(new HookRegistrar())
    ->action('init', function (): void { /* ... */ })
    ->filter('the_content', function (string $c): string { return $c; })
    ->shortcode('my_tag', function (): string { return '<p>Hello</p>'; });
```

### ShortcodeRegistrar

`HookRegistrar` を使って `[document]` / `[title]` / `[site_name]` を登録する便利クラスです。

```php
use Period\WpKit\Infrastructure\WordPress\ShortcodeRegistrar;

(new ShortcodeRegistrar())->register();
```

---

## 8. Renderer 系

### PageNavigationRenderer

```php
use Period\WpKit\Infrastructure\WordPress\PageNavigationRenderer;

echo (new PageNavigationRenderer())->render([
    'aria_label' => 'ページナビゲーション',
    'prev_text'  => '前へ',
    'next_text'  => '次へ',
]);
```

### ImageTagRenderer

WordPress 添付ファイルから `<img>` タグを生成します。`picture` / `figure` は対象外です。→ [usage-image-renderer.md](usage-image-renderer.md)

```php
use Period\WpKit\Infrastructure\WordPress\ImageTagRenderer;

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
use Period\WpKit\View\Element;

Element::el('a', ['href' => '/about', 'class' => ['btn', 'btn-lg']], 'About');
// → <a href="/about" class="btn btn-lg">About</a>

Element::void('img', ['src' => '/logo.png', 'alt' => 'Logo']);
(new Element('div', ['id' => 'wrap']))->open()->render(); // → <div id="wrap">
Element::class(['btn', null, 'btn-lg', 'btn']);           // → "btn btn-lg"

// コメント / CDATA（RawHtml を返す）
Element::comment('debug')->render();       // → <!-- debug -->
Element::cdata('var a = 1;')->render();    // → <![CDATA[var a = 1;]]>

// 空なら出力しない
Element::elIfNotEmpty('p', [], '');        // → ''
Element::elIfNotEmpty('p', [], 'Hello');   // → <p>Hello</p>
```

#### HTML タグショートハンド

よく使うタグは `Element::タグ名($attrs, $content)` で直接生成できます。戻り値は `string` です。

第3引数 `$content` には `string` / `RawHtml` / `array` を渡せます。`array` の場合は各要素を順に連結します（要素は `string` または `RawHtml`）。

```php
// 通常要素 — (array $attrs = [], string|RawHtml|array $content = ''): string
Element::p(['class' => 'lead'], 'Hello');  // → <p class="lead">Hello</p>
Element::h1([], 'タイトル');                // → <h1>タイトル</h1>
Element::section(['id' => 'main']);        // → <section id="main"></section>

// 配列でネスト — array 内の string はそのまま連結（HTML として扱われる）
Element::ul([], [
    Element::li([], 'A'),
    Element::li([], 'B'),
    Element::li([], 'C'),
]);
// → <ul><li>A</li><li>B</li><li>C</li></ul>

// 深いネスト
Element::nav(['aria-label' => 'main'], [
    Element::ul([], [
        Element::li([], [Element::el('a', ['href' => '/'], 'Home')]),
        Element::li([], [Element::el('a', ['href' => '/about'], 'About')]),
    ]),
]);

// string + RawHtml 混在
Element::p([], [
    'Before ',
    Element::raw('<strong>bold</strong>'),
    ' after',
]);
// → <p>Before <strong>bold</strong> after</p>

// テーブル
Element::table([], [
    Element::tr([], [Element::td([], 'Cell')]),
]);

// PHP 予約語との衝突を避けるエイリアス
Element::objectTag(['data' => '/file.pdf']);   // → <object data="/file.pdf"></object>
Element::varTag([], 'x');                      // → <var>x</var>

// 空要素（void）— (array $attrs = []): string
Element::input(['type' => 'text', 'name' => 'q']); // → <input type="text" name="q">
Element::meta(['charset' => 'UTF-8']);              // → <meta charset="UTF-8">
Element::link(['rel' => 'stylesheet', 'href' => '/app.css']);
Element::hr();                                      // → <hr>
Element::source(['src' => '/v.mp4', 'type' => 'video/mp4']);
```

対応タグ一覧:
- **通常要素**: `abbr` `address` `article` `aside` `audio` `b` `bdi` `bdo` `blockquote` `body` `button` `canvas` `caption` `cite` `code` `colgroup` `data` `datalist` `dd` `del` `details` `dfn` `dialog` `dl` `dt` `em` `fieldset` `figcaption` `figure` `footer` `form` `h1`〜`h6` `head` `header` `hgroup` `html` `i` `iframe` `ins` `kbd` `label` `legend` `li` `main` `map` `mark` `menu` `meter` `nav` `noscript` `objectTag` `ol` `optgroup` `option` `output` `p` `picture` `pre` `progress` `q` `rp` `rt` `ruby` `s` `samp` `script` `search` `section` `select` `slot` `small` `strong` `style` `sub` `summary` `sup` `table` `tbody` `td` `template` `textarea` `tfoot` `th` `thead` `time` `title` `tr` `u` `ul` `varTag` `video`
- **空要素**: `area` `base` `col` `embed` `hr` `input` `link` `meta` `param` `source` `track` `wbr`

#### エスケープと RawHtml

`Element` は `string` の content をテキストとして扱い、HTML エスケープします。

```php
Element::div([], '<span>A</span>');
// → <div>&lt;span&gt;A&lt;/span&gt;</div>

Element::div([], Element::raw('<span>A</span>'));
// → <div><span>A</span></div>
```

**設計思想**

- `string` はデフォルトで安全。ユーザー入力や動的な値をそのまま渡せる。
- `RawHtml` は「このHTMLはそのまま出力してよい」と開発者が明示する手段。使用は開発者責任。

**セキュリティ注意**

ユーザー入力を `RawHtml` に渡してはいけません。XSS の原因になります。`RawHtml` は信頼できる内容（自前のテンプレート断片・外部HTML）のみに使用してください。

**配列 children との関係**

配列を content に渡した場合、各要素はエスケープされずそのまま連結されます。単一 `string` とは挙動が異なります。

| content の型 | 挙動 |
|---|---|
| `string` | HTML エスケープされる |
| `RawHtml` | エスケープされない |
| `array` の各要素（`string`） | エスケープされない（そのまま連結） |
| `array` の各要素（`RawHtml`） | エスケープされない |

```php
// string → エスケープされる
Element::div([], '<b>text</b>');
// → <div>&lt;b&gt;text&lt;/b&gt;</div>

// 配列の string → エスケープされない（ショートハンドの戻り値をそのまま渡せる）
Element::ul([], [
    Element::li([], 'A'),
    Element::li([], 'B'),
]);
// → <ul><li>A</li><li>B</li></ul>
```

配列の各要素はショートハンドが返す HTML 文字列を前提としています。ユーザー入力を配列に含める場合は `htmlspecialchars()` で事前にエスケープしてください。

`RawHtml` が必要になる主なケース:

```php
// script / style の中身
Element::script(['type' => 'text/javascript'], Element::raw('console.log("ok");'));

// HTML コメント
Element::div([], Element::comment('debug section'));

// 外部 HTML 断片（信頼できる出力のみ）
Element::div([], Element::raw($trustedFragment));
```

#### strip_tags 相当について

HTML 生成（`Element`）の責務はタグ構造の組み立てに限定します。テキストのタグ除去が必要な場合は、呼び出し側で `strip_tags()` を使ってください。将来的に `TextUtil` として分離する可能性があります。

```php
Element::el('p', [], strip_tags($userInput));
```

### ArrayUtil

配列のリスト判定と連想配列判定を提供します。

```php
use Period\WpKit\Support\ArrayUtil;

ArrayUtil::isList([1, 2, 3]);                    // → true
ArrayUtil::isList(['a' => 1, 'b' => 2]);         // → false
ArrayUtil::isList([0 => 'a', 2 => 'b']);         // → false（欠番あり）
ArrayUtil::isAssociative(['a' => 1, 'b' => 2]);  // → true
ArrayUtil::isAssociative([1, 2, 3]);             // → false
```

PHP 8.1 以上では `array_is_list()` を使用し、未満では同等の fallback を使用します。

### Calendar

月単位のカレンダーデータを生成します。HTML は生成しません。表示は View / Element 側で行います。

```php
use Period\WpKit\Support\Calendar;
use Period\WpKit\Support\Locale\WeekdayName;

$weeks = Calendar::month(2026, 5, [
    'start_of_week' => 0, // 0 = Sunday, 1 = Monday
]);

foreach ($weeks as $week) {
    foreach ($week as $day) {
        echo $day->date();             // "2026-05-01"
        echo $day->year;               // 2026
        echo $day->month;              // 5
        echo $day->day;                // 1
        echo $day->weekday;            // 0–6 (0 = Sunday)
        var_dump($day->isCurrentMonth); // true/false
        var_dump($day->isToday);        // true/false
    }
}

// 曜日ヘッダー（デフォルトは WeekdayName::EN_SHORT）
Calendar::weekdays(0); // ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
Calendar::weekdays(1); // ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']

// Locale の曜日名を差し替えられる
Calendar::weekdays(1, WeekdayName::JA_SHORT); // ['月', '火', '水', '木', '金', '土', '日']
Calendar::weekdays(0, WeekdayName::JA);       // ['日曜日', '月曜日', ...]
```

戻り値は `array<array<CalendarDay>>` の二次元配列です。各週は必ず7要素。前月・翌月の日付で埋められた日は `isCurrentMonth === false` になります。

### CssName / ImageUtil / LineEnding / Encoding

```php
use Period\WpKit\Support\CssName;
use Period\WpKit\Support\ImageUtil;
use Period\WpKit\Support\LineEnding;
use Period\WpKit\Support\Encoding;

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

## 10. Condition（WordPress 状態判定）

```php
use Period\WpKit\Infrastructure\WordPress\Condition;

$cond = new Condition();

// 投稿タイプの判定
$cond->isPostType('news');                 // 現在の投稿が news タイプか
$cond->isPostType(['news', 'event']);      // いずれかに一致するか
$cond->isPostType('news', $post);         // $post（ID または WP_Post）を明示指定

// ユーザーの判定（ID / ログイン名 / メールアドレス）
$cond->isUser(5);                         // 現在ログインユーザーの ID が 5 か
$cond->isUser('alice');                   // ログイン名 "alice" か
$cond->isUser('alice@example.com');       // メールアドレス一致か
$cond->isUser([5, 'alice']);              // いずれかに一致するか
$cond->isUser(5, $user);                 // $user（ID または WP_User）を明示指定
```

WordPress が存在しない環境（テスト時など）では常に `false` を返します。

---

## 11. i18n / Translator

`Translator` はテンプレート層・呼び出し側で使います。ライブラリ内部ロジックへの注入は行いません。

```php
use Period\WpKit\Infrastructure\WordPress\Translator;

$t = new Translator('my-text-domain');
$t->text('Save');                          // __('Save', 'my-text-domain')
$t->html('Save');                          // esc_html__(...)
$t->attr('Save');                          // esc_attr__(...)
$t->plural('%d item', '%d items', $count); // _n(...)
```

`pwk()->translator()` で共有インスタンスを取得できます。

MetaBox のラベルを翻訳する場合は `labels` に渡します。

```php
$t = pwk()->translator();
$field = [
    'name'   => 'thumb',
    'type'   => 'image',
    'labels' => [
        'select_image' => $t->text('Select image'),
        'clear'        => $t->text('Clear'),
    ],
];
```
