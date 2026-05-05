# Migration Guide

## v1 → v2

### 1. MetaBox: labels への移行

個別の `button_label` / `clear_label` / `remove_label` は非推奨になりました。`labels` 配列に統合してください。

**Before（非推奨）**

```php
[
    'name'         => 'main_image',
    'type'         => 'image',
    'label'        => 'メイン画像',
    'button_label' => '画像を選択',
    'clear_label'  => 'クリア',
    'remove_label' => '削除',
]
```

**After（推奨）**

```php
[
    'name'  => 'main_image',
    'type'  => 'image',
    'label' => 'メイン画像',
    'labels' => [
        'select_image' => '画像を選択',
        'clear'        => 'クリア',
        'remove'       => '削除',
    ],
]
```

`labels` キーの対応表:

| type      | キー           | 説明 |
|-----------|---------------|------|
| `image`   | `select_image` | ボタンラベル |
| `gallery` | `select_images`| ボタンラベル |
| `media`   | `select`       | ボタンラベル |
| `repeater`| `add`          | 追加ボタンラベル |
| すべて     | `clear`        | クリアボタンラベル |
| すべて     | `remove`       | 削除ボタンラベル |

---

### 2. title / site / document の新API

v2 では `pwf()` に Template Tags が追加されました。

```php
// タイトル取得
echo pwf()->title();

// サイト情報
$site = pwf()->site();
echo $site->name();
echo $site->url();

// HTML ドキュメント生成
echo pwf()->document($content, [
    'body_class'        => ['home'],
    'head_elements'     => ['<meta name="robots" content="noindex">'],
    'include_wp_head'   => true,
    'include_wp_footer' => true,
]);
```

詳細は [docs/usage-template-tags.md](usage-template-tags.md) を参照。

---

## 非推奨項目一覧

| 項目 | 代替 | 対象クラス |
|------|------|-----------|
| `button_label` フィールドキー | `labels['select']` / `labels['select_image']` / `labels['select_images']` / `labels['add']` | `MetaBox` |
| `clear_label` フィールドキー | `labels['clear']` | `MetaBox` |
| `remove_label` フィールドキー | `labels['remove']` | `MetaBox` |
