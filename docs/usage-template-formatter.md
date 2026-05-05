# TemplateFormatter

`{{ key }}` プレースホルダーを context の値で置換する整形クラスです。WordPress 非依存で `Period\WpFramework\Support` に属します。

## 基本的な使い方

```php
use Period\WpFramework\Support\TemplateFormatter;

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

## apply_filters を使いたい場合

`TemplateFormatter` 自体は WordPress に依存しません。フィルターを適用したい場合は呼び出し側で行います。

```php
use Period\WpFramework\Support\TemplateFormatter;

$formatter = new TemplateFormatter();
$result = $formatter->format('{{ title }}', ['title' => 'Hello']);

if (function_exists('apply_filters')) {
    $result = (string) apply_filters('my_plugin_title', $result);
}
```

## SiteInfo / TitleResolver との組み合わせ

```php
use Period\WpFramework\Support\TemplateFormatter;
use Period\WpFramework\Infrastructure\WordPress\SiteInfo;
use Period\WpFramework\Infrastructure\WordPress\TitleResolver;

$info      = new SiteInfo();
$resolver  = new TitleResolver($info);
$formatter = new TemplateFormatter();

echo $formatter->format(
    '{{ title }} | {{ site_name }}',
    [
        'title'     => $resolver->title(),
        'site_name' => $info->name(),
    ]
);
```
