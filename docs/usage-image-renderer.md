# ImageTagRenderer

`ImageTagRenderer` は WordPress の attachment ID から `<img>` タグを生成するレンダラーです。`<picture>` / `<figure>` は対象外で、それらは将来の別クラスで扱います。

### 使用例

```php
use Period\WpKit\Infrastructure\WordPress\ImageTagRenderer;

$renderer = new ImageTagRenderer();
echo $renderer->render(123, [
    'size'          => 'full',
    'class'         => 'custom-image',
    'wrapper'       => true,
    'wrapper_class' => 'image',
    'lazy'          => true,
    'alt'           => '代替テキスト',
]);
```

### 引数

- `size`: string, デフォルト `full`
- `class`: string, wrapper に追加するクラス
- `wrapper`: bool, デフォルト `true`（`<div>` でラップする）
- `wrapper_class`: string, デフォルト `image`
- `lazy`: bool, デフォルト `true`（`loading="lazy"` を付与）
- `alt`: string|null, 明示的な alt テキスト（省略時は `_wp_attachment_image_alt` を使用）

### 仕様

- `wp_get_attachment_image_src()` が存在しない場合は空文字を返す
- attachment が取得できない場合は空文字を返す
- `ImageUtil::orientation()` による向きクラス（`image--landscape` 等）を wrapper に付与
- WordPress 関数がなくても読み込みエラーにならない

### deprecated

> `ImageRenderer` は `ImageTagRenderer` の deprecated エイリアスです。新規コードでは `ImageTagRenderer` を使ってください。

```php
// deprecated
use Period\WpKit\Infrastructure\WordPress\ImageRenderer;

// 推奨
use Period\WpKit\Infrastructure\WordPress\ImageTagRenderer;
```
