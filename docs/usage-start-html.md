# Start HTML

`StartHtmlRenderer` は HTML ドキュメントの開始部分を生成するレンダラーです。`<html>` と `<head>` の開始タグ、および `meta charset` を出力します。

### 使用例

```php
use Period\WpKit\Infrastructure\WordPress\StartHtmlRenderer;

$renderer = new StartHtmlRenderer();
echo $renderer->render([
    'version' => 'html5',
    'charset' => 'UTF-8',
    'elements' => [
        '<title>サイトタイトル</title>',
    ],
]);
```

### 引数

- `version`: `html5` などのバージョン文字列。`xhtml` で始まる場合は XHTML 用の `language_attributes` を使います。
- `elements`: `string` / `RawHtml` / `Element` を head 内に追加する配列
- `charset`: string|null, `meta charset` に使用。指定がない場合は `get_bloginfo('charset')` または `UTF-8` を使います。
- `newline`: 改行文字。デフォルトは `\n`

### title の自動生成

`<title>` は `TitleResolver` / `SiteInfo` / `TemplateFormatter` を使って自動生成される。`<meta charset>` の直後に出力される。

デフォルトのテンプレート: `{{ title }}`（`TitleResolver::siteTitle()` の結果が入る）

`period_wp_document_title` フィルターで最終出力を変更できる。

```php
add_filter('period_wp_document_title', function (string $title): string {
    return $title . ' | カスタムサフィックス';
});
```

`elements` に任意の head 要素を追加することもできる。

```php
$renderer->render([
    'elements' => [
        '<meta name="description" content="説明文">',
        new RawHtml('<link rel="canonical" href="https://example.com/">'),
    ],
]);
```

### 仕様

- `<!doctype html>` を出力
- `<html ...>` と `<head>` の開始タグを出力
- `meta charset` を出力
- `<title>` を自動生成して出力（`period_wp_document_title` フィルター対応）
- `elements` の内容を出力
- `</head>` や `<body>` は出力しない
- WordPress 関数がなくてもフォールバックして出力する
