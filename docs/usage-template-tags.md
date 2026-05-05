# Template Tags

`pwf()` から `DocumentRenderer` / `TitleResolver` / `SiteInfo` を簡潔に呼び出せるテンプレートタグです。

### pwf()->title()

現在のページタイトルを返します（`TitleResolver::siteTitle()` の結果）。

```php
echo pwf()->title();
// → "About Us | My Site"
```

### pwf()->site()

`SiteInfo` インスタンスを返します。繰り返し呼んでも同一インスタンスを返します。

```php
$site = pwf()->site();
echo $site->name();        // サイト名
echo $site->description(); // キャッチフレーズ
echo $site->url();         // サイトURL
```

詳細は [docs/usage-site-info.md](docs/usage-site-info.md) を参照。

### pwf()->document()

`DocumentRenderer` を使って完全な HTML ドキュメントを生成します。

```php
echo pwf()->document('<h1>Hello</h1>', [
    'body_class' => ['home'],
]);
```

第2引数の `$args` は `DocumentRenderer::render()` にそのまま渡ります。詳細は [docs/usage-document-renderer.md](docs/usage-document-renderer.md) を参照。

### pwf()->translator()

`Translator` インスタンスを返します。テンプレート層・呼び出し側での翻訳に使います。MetaBox などのライブラリ内部では使いません。

```php
$t = pwf()->translator();
echo $t->html('Save');
```
