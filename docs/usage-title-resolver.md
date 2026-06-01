# TitleResolver

WordPress の現在リクエスト文脈に応じたページタイトルを取得するラッパー。WordPress が存在しない環境でも例外を投げない。

## 基本的な使い方

```php
use Period\WpKit\Infrastructure\WordPress\TitleResolver;

$resolver = new TitleResolver();

// 現在ページのタイトルを取得
$resolver->title();

// "タイトル | サイト名" 形式で取得
$resolver->siteTitle();

// セパレーター変更
$resolver->siteTitle(' – ');

// fallback 指定
$resolver->title(['fallback' => 'My Page']);
```

## title() の解決順序

1. `wp_get_document_title()` が非空文字を返す → それを使用
2. `is_singular()` + `get_the_title()` → 投稿タイトル
3. `is_archive()` + `get_the_archive_title()` → アーカイブタイトル
4. `is_search()` + `get_search_query()` → `"Search: {query}"`
5. `is_404()` → `"Not Found"`
6. `args['fallback']` が指定されていればそれを使用
7. `SiteInfo::name()` → サイト名
8. 空文字

## siteTitle() の結合ルール

| title() | SiteInfo::name() | 結果 |
|---------|-----------------|------|
| `'About'` | `'My Site'` | `'About \| My Site'` |
| `'My Site'` | `'My Site'` | `'My Site'`（重複しない） |
| `''` | `'My Site'` | `'My Site'` |
| `'About'` | `''` | `'About'` |
| `''` | `''` | `''` |

## SiteInfo を外部から渡す

```php
use Period\WpKit\Infrastructure\WordPress\SiteInfo;
use Period\WpKit\Infrastructure\WordPress\TitleResolver;

$resolver = new TitleResolver(new SiteInfo());
```

省略した場合は内部で `new SiteInfo()` を生成する。
