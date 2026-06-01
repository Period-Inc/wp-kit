# HookRegistrar / Shortcode / Filter / Action

`HookRegistrar` は `add_action` / `add_filter` / `add_shortcode` を統一的に登録する基盤クラスです。WordPress 関数が存在しない場合でもエラーにはならず、安全に無視されます。

---

## HookRegistrar

```php
use Period\WpKit\Infrastructure\WordPress\HookRegistrar;

$hooks = new HookRegistrar();

$hooks
    ->action('init', function (): void { /* 初期化処理 */ })
    ->filter('the_content', function (string $content): string { return $content; })
    ->shortcode('my_tag', function (): string { return '<p>Hello</p>'; });
```

メソッドはすべてチェーン可能です。

### メソッド一覧

| メソッド | 対応 WordPress 関数 |
|--------|-------------------|
| `action(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1)` | `add_action` |
| `filter(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1)` | `add_filter` |
| `shortcode(string $tag, callable $callback)` | `add_shortcode` |

---

## filter の活用例

`period_wp_document_title` フィルターでタイトルを変更する例:

```php
use Period\WpKit\Infrastructure\WordPress\HookRegistrar;

(new HookRegistrar())->filter('period_wp_document_title', function (string $title): string {
    return $title . ' | My Site';
});
```

---

## ShortcodeRegistrar（補助クラス）

`ShortcodeRegistrar` は `HookRegistrar` を使って `[document]` / `[title]` / `[site_name]` を登録する便利クラスです。

```php
use Period\WpKit\Infrastructure\WordPress\ShortcodeRegistrar;

(new ShortcodeRegistrar())->register();
```

登録されるショートコードの例:

```
[title]                          → TitleResolver::siteTitle() の結果
[site_name]                      → SiteInfo::name() の結果
[document]<h1>Hello</h1>[/document] → DocumentRenderer による完全な HTML ドキュメント
```

独自ショートコードを追加したい場合は `HookRegistrar::shortcode()` を直接使います。

```php
// シンプルな例
(new HookRegistrar())->shortcode('hello', function (): string {
    return '<p>Hello, World!</p>';
});

// attributes / content を受け取る例
(new HookRegistrar())->shortcode('my_tag', function ($atts = [], $content = null): string {
    return '<div>' . ($content ?? '') . '</div>';
});
```
