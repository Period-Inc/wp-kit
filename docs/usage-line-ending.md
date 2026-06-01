# LineEnding

`LineEnding` は改行コードを扱うためのユーティリティです。

---

## 基本

```php
use Period\WpKit\Support\LineEnding;
```

---

## 例

```php
LineEnding::LF     // "\n"
LineEnding::CR     // "\r"
LineEnding::CRLF   // "\r\n"
```

---

## 用途

- ファイル出力時の改行制御
- CSV / テキスト生成
- OS差異の吸収

---

## Encoding

```php
use Period\WpKit\Support\Encoding;
```

```php
Encoding::UTF8
```

---

## 用途

- `mb_*` 関数
- 文字コード指定
- HTML / JSON 出力
