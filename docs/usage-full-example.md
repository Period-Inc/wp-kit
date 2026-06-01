# フル使用例

## 1. 最小テーマ

```php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/vendor/period/wp-kit/bootstrap.php';
$app = pwk();

echo $app->document('<h1>Hello</h1>');
```

出力:

```html
<!doctype html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>My Site</title>
</head>
<body>
<h1>Hello</h1>
</body>
</html>
```

---

## 2. title カスタマイズ

### TemplateFormatter で構造を変える

`TemplateFormatter` は WordPress 非依存で `Support` に属します。`apply_filters` は呼び出し側で行います。

```php
use Period\WpKit\Support\TemplateFormatter;
use Period\WpKit\Infrastructure\WordPress\SiteInfo;
use Period\WpKit\Infrastructure\WordPress\TitleResolver;

$info      = new SiteInfo();
$resolver  = new TitleResolver($info);
$formatter = new TemplateFormatter();

$title = $formatter->format(
    '{{ title }} | {{ site_name }}',
    [
        'title'     => $resolver->title(),
        'site_name' => $info->name(),
    ]
);

if (function_exists('apply_filters')) {
    $title = (string) apply_filters('period_wp_document_title', $title);
}
```

### フィルターで変更する

```php
add_filter('period_wp_document_title', function (string $title): string {
    return $title . ' | カスタムサフィックス';
});
```

---

## 3. body_class の指定

```php
echo pwk()->document(get_template_part_content(), [
    'body_class' => ['home', 'dark-mode'],
]);
```

WordPress がある場合は `get_body_class()` の結果とマージされます。

---

## 4. head_elements の追加

```php
use Period\WpKit\View\RawHtml;

echo pwk()->document($content, [
    'head_elements' => [
        '<meta name="description" content="サイトの説明文">',
        new RawHtml('<link rel="canonical" href="https://example.com/">'),
    ],
]);
```

---

## 5. Hook / Shortcode 登録

`HookRegistrar` を使って action / filter / shortcode を登録します。WordPress がない環境では noop です。

```php
use Period\WpKit\Infrastructure\WordPress\HookRegistrar;

$hooks = new HookRegistrar();
$hooks
    ->action('init', function (): void { /* 初期化処理 */ })
    ->filter('period_wp_document_title', function (string $title): string {
        return $title . ' | My Site';
    })
    ->shortcode('my_tag', function (): string { return '<p>Hello</p>'; });
```

`[document]` / `[title]` / `[site_name]` の登録には `ShortcodeRegistrar` を使えます。

```php
use Period\WpKit\Infrastructure\WordPress\ShortcodeRegistrar;

(new ShortcodeRegistrar())->register();
```

テンプレート内での使用例:

```
<p>サイト名: [site_name]</p>
<p>タイトル: [title]</p>
```

---

## 6. Template Tags

```php
// タイトル取得
$title = pwk()->title();

// サイト情報
$site = pwk()->site();
echo $site->name();        // サイト名
echo $site->url();         // URL
echo $site->description(); // キャッチフレーズ

// ドキュメント生成
echo pwk()->document($content, [
    'body_class'        => ['page-about'],
    'include_wp_head'   => true,
    'include_wp_footer' => true,
    'head_elements'     => [
        '<meta name="robots" content="noindex">',
    ],
]);
```
