# SiteInfo

WordPress のサイト情報（`bloginfo` 系）を安全に取得するラッパー。WordPress が存在しない環境でも例外を投げない。

## 基本的な使い方

```php
use Period\WpKit\Infrastructure\WordPress\SiteInfo;

$info = new SiteInfo();

$info->name();        // サイト名
$info->description(); // キャッチフレーズ
$info->charset();     // 文字コード（例: UTF-8）
$info->language();    // 言語コード（例: ja）
$info->url();         // ホーム URL
$info->themeUri();    // 有効テーマのディレクトリ URI
```

## fallback 値

WordPress 関数が存在しない場合の返却値:

| メソッド | fallback |
|---------|---------|
| `name()` | `''` |
| `description()` | `''` |
| `charset()` | `'UTF-8'` |
| `language()` | `'en'` |
| `url()` | `''` |
| `themeUri()` | `''` |
