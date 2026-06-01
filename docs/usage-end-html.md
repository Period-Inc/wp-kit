# End HTML

`EndHtmlRenderer` は HTML ドキュメントの終了部を生成するレンダラーです。`</body>` と `</html>` を出力し、必要であれば footer 要素を追加します。

### 使用例

```php
use Period\WpKit\Infrastructure\WordPress\EndHtmlRenderer;

$renderer = new EndHtmlRenderer();
echo $renderer->render([
    'elements' => [
        '<script src="/app.js"></script>',
    ],
    'include_wp_footer' => false,
]);
```

### 引数

- `elements`: `string` / `RawHtml` / `Element` を head 終了後に追加する配列
- `newline`: 改行文字、デフォルトは `\n`
- `include_wp_footer`: bool、デフォルト `true`

### 仕様

- `elements` を先に出力
- `wp_footer()` が存在し、`include_wp_footer` が `true` の場合はその出力を取り込む
- `</body>` と `</html>` を出力
- `</head>` や `<body>` は出力しない
- WordPress 関数がなくても動作する
