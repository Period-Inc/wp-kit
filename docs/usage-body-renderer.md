# BodyRenderer

`BodyRenderer` は `<body>` 開始タグを生成するレンダラーです。WordPress の `get_body_class()` / `wp_body_open()` を安全に呼び出し、WordPress がない環境でもフォールバックして動作します。

### 使用例

```php
use Period\WpKit\Infrastructure\WordPress\BodyRenderer;

$renderer = new BodyRenderer();
echo $renderer->render([
    'class' => ['page-home', 'my-theme'],
]);
```

### 引数

- `class`: `string` | `string[]` — 追加するクラス。`get_body_class()` があればその結果とマージされる
- `include_wp_body_open`: `bool`（デフォルト `true`）— `wp_body_open()` を呼び出すかどうか
- `newline`: 改行文字。デフォルトは `\n`

### クラスのマージ

`get_body_class()` が存在する場合、WordPress が生成するクラス（`home`、`page-id-1` など）と `class` 引数の値をマージして出力します。WordPress がない場合は `class` 引数の値のみ使用されます。

```php
// WordPress あり: <body class="home blog my-class">
// WordPress なし: <body class="my-class">
$renderer->render(['class' => 'my-class']);
```

### wp_body_open

`include_wp_body_open: true`（デフォルト）かつ `wp_body_open()` が存在する場合、`<body>` タグの直後に呼び出されます。

```php
// wp_body_open を呼ばない場合
$renderer->render(['include_wp_body_open' => false]);
```

### 仕様

- `<body>` の開始タグのみを出力（`</body>` は出力しない）
- `class` が空の場合は `class` 属性を出力しない
- `get_body_class()` があればクラスをマージ、なければ `class` 引数をそのまま使用
- `wp_body_open()` があれば `<body>` 直後に呼び出す
- WordPress 関数がなくてもフォールバックして出力する
