# DocumentRenderer

`DocumentRenderer` は `StartHtmlRenderer` / `BodyRenderer` / `EndHtmlRenderer` を統合し、1回の呼び出しで完全な HTML ドキュメントを生成するレンダラーです。

### 使用例

```php
use Period\WpFramework\Infrastructure\WordPress\DocumentRenderer;

$renderer = new DocumentRenderer();
echo $renderer->render('<h1>Hello</h1>', [
    'body_class' => ['home'],
]);
```

### 引数

| キー | 型 | 説明 |
|------|----|------|
| `head_elements` | `array` | `<head>` 内に追加する要素（`StartHtmlRenderer` の `elements` に渡る） |
| `body_class` | `array\|string` | `<body>` に付与するクラス（`BodyRenderer` の `class` に渡る） |
| `include_wp_head` | `bool` | `wp_head()` を呼ぶかどうか（デフォルト `true`） |
| `include_wp_footer` | `bool` | `wp_footer()` を呼ぶかどうか（デフォルト `true`） |

### 出力構造

```html
<!doctype html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>...</title>
  <!-- head_elements -->
  <!-- wp_head() -->
</head>
<body class="...">
<!-- wp_body_open() -->
(content)
<!-- wp_footer() -->
</body>
</html>
```

### constructor injection

各レンダラーをコンストラクタから差し替えられます。`null` を渡すと内部で `new` されます。

```php
use Period\WpFramework\Infrastructure\WordPress\DocumentRenderer;
use Period\WpFramework\Infrastructure\WordPress\StartHtmlRenderer;

$renderer = new DocumentRenderer(
    start: new StartHtmlRenderer(),
);
```

### 仕様

- `content` はエスケープせずそのまま出力する
- WordPress 関数がなくてもフォールバックして動作する
- 各 Renderer の責務は変更しない
