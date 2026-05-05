# TemplateFormatter

`{{ key }}` プレースホルダーを context の値で置換する整形レイヤー。WordPress の `apply_filters` を通じて最終出力をフック可能。

## 基本的な使い方

```php
use Period\WpFramework\Infrastructure\WordPress\TemplateFormatter;

$formatter = new TemplateFormatter();

$result = $formatter->format(
    '{{ title }} | {{ site_name }}',
    [
        'title'     => 'About Us',
        'site_name' => 'My Site',
    ]
);
// → "About Us | My Site"
```

## 変換ルール

| context 値 | 出力 |
|-----------|------|
| `'Hello'` | `'Hello'` |
| `42` | `'42'`（string にキャスト） |
| `null` | `''` |
| 配列 | `''` |
| オブジェクト | `''` |
| キーが存在しない | `''` |

最終結果は `trim()` される。

## filter フック

第 3 引数に filter 名を渡すと `apply_filters` を通す。

```php
$result = $formatter->format(
    '{{ title }}',
    ['title' => 'Hello'],
    'my_plugin_title'
);

// WordPress 側
add_filter('my_plugin_title', function (string $result, string $template, array $context): string {
    return strtoupper($result);
});
// → "HELLO"
```

`apply_filters` が存在しない場合はスキップされ、置換済みの値がそのまま返る。

## SiteInfo / TitleResolver との組み合わせ

```php
use Period\WpFramework\Infrastructure\WordPress\SiteInfo;
use Period\WpFramework\Infrastructure\WordPress\TitleResolver;
use Period\WpFramework\Infrastructure\WordPress\TemplateFormatter;

$info     = new SiteInfo();
$resolver = new TitleResolver($info);
$formatter = new TemplateFormatter();

echo $formatter->format(
    '{{ title }} | {{ site_name }}',
    [
        'title'     => $resolver->title(),
        'site_name' => $info->name(),
    ],
    'my_plugin_document_title'
);
```
