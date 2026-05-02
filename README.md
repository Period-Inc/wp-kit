# period-wp-framework

`period-wp-framework` は、WordPress テーマ制作やカスタマイズを補助する軽量ライブラリです。

- prefix: `period_wp_`
- entry function: `pwf()`
- namespace: `Period\WpFramework`

## 特徴

- テンプレート描画を支援する `HtmlTemplate`
- HTML5 解析を行う `HtmlDocument`
- `Legacy` 配下は旧資産の保管用であり、新規コードから参照しない

## 使い方

### `HtmlTemplate` の例

```php
use Period\WpFramework\Support\HtmlTemplate;

$template = new HtmlTemplate('<a href="{{ url }}">{{ label }}</a>');
echo $template->render([
    'url' => 'https://example.com',
    'label' => 'Example',
]);
```

### `HtmlDocument` の例

```php
use Period\WpFramework\Support\HtmlDocument;

$html = '<html><head><title>Example</title></head><body><p>本文</p></body></html>';
$document = HtmlDocument::fromString($html);

$title = $document->firstText('title');
$paragraphs = $document->filter('p');
```

### `fetch_title` ショートコードの例

WordPress 環境で次のように利用します。

```php
echo do_shortcode('[fetch_title url="https://example.com"]');
```

このショートコードは指定した URL から HTML を取得し、`<title>` を抽出して表示します。

## インストール

```bash
composer install
composer dump-autoload
```

## 注意

- `Legacy` フォルダ以下は旧資産の保管用です。
- 新規コードでは `Legacy` 配下を直接参照しないようにしてください。
