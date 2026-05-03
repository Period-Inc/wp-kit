CssName

UTF-8文字列やURLをCSSのclass/idとして安全に使える形式へ変換するユーティリティ。

---

基本

```php
CssName::fromString(string $value, string $prefix = '')
CssName::fromUrl(string $url, string $prefix = '')
```

---

例

```php
CssName::fromString('Hello World')
// hello--world

CssName::fromString('123abc')
// cssname-123abc

CssName::fromString('123abc', 'post')
// post-123abc
```

---

URL

```php
CssName::fromUrl('https://example.com/a/b')
// example-com--a--b

CssName::fromUrl('https://example.com/a?x=1')
// example-com--a___x-1

CssName::fromUrl('https://example.com/a?x=1', 'post')
// post-example-com--a___x-1
```

---

日本語

```php
CssName::fromUrl('https://example.com/未分類')
// example-com--_xE6_x9C_xAA_xE5_x88_x86_xE9_xA1_x9E
```

---

仕様

```text
- UTF-8文字は rawurlencode でエンコードされる
- 英数字はそのまま
- "_" と "-" は保持
- スペース / "/" / "\\" / "&" は "--"
- "." は "_"
- "%" は "_x"
- "?" は "___"
- "#" は "____"
- "=" は "-"
- その他の記号は "-" に変換
- 先頭・末尾の "-" "_" は削除
```

---

prefix

- prefix指定時は prefix-xxx の形式になる
- prefix未指定時は通常そのまま返す
- 数字始まりの場合のみ "cssname-" が付与される
- 空文字の場合は "cssname" を返す

---

用途

- URLをそのままCSS selectorに使う
- 動的に一意なclass/idを生成する
- スクレイピングやDOM操作での識別子生成
