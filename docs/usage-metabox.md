# MetaBox Usage

## PostMetaManager

MetaBox が保存したデータを統一的に読み書きするユーティリティです。WordPress がない環境では noop になります。

```php
use Period\WpFramework\Infrastructure\WordPress\PostMetaManager;

$meta = new PostMetaManager();

// 取得（値がない場合は null）
$value = $meta->get($postId, 'lead');

// 保存
$meta->set($postId, 'lead', 'リード文のテキスト');

// キーが存在するか確認
if ($meta->has($postId, 'lead')) {
    // ...
}
```

| メソッド | 戻り値 | WordPress なし |
|---|---|---|
| `get(int $postId, string $key)` | `mixed` | `null` |
| `set(int $postId, string $key, mixed $value)` | `void` | noop |
| `has(int $postId, string $key)` | `bool` | `false` |

`get()` は空文字列 (`""`) を `null` に正規化します。

---

## labels（ラベル指定）

ボタン・操作ラベルは `labels` 配列で指定します。内部の fallback は英語固定で、翻訳は呼び出し側で行います。

```php
use Period\WpFramework\Infrastructure\WordPress\MetaBox;

new MetaBox([
    'id'       => 'sample',
    'title'    => 'Sample',
    'post_type'=> 'post',
    'fields'   => [
        [
            'name'   => 'thumb',
            'type'   => 'image',
            'label'  => 'サムネイル',
            'labels' => [
                'select_image' => '画像を選択',
                'clear'        => 'クリア',
            ],
        ],
    ],
]);
```

`labels` キーの一覧:

| キー | 対象 type | fallback |
|-----|-----------|---------|
| `select_image` | `image` | `Select image` |
| `select_images`| `gallery` | `Select images` |
| `select` | `media` | `Select` |
| `add` | `repeater` | `Add` |
| `clear` | すべて | `Clear` |
| `remove` | すべて | `Remove` |

### Translator との組み合わせ

翻訳したい場合は `pwf()->translator()` を呼び出し側で使います。

```php
$t = pwf()->translator();

new MetaBox([
    'id'       => 'sample',
    'post_type'=> 'post',
    'fields'   => [[
        'name'   => 'thumb',
        'type'   => 'image',
        'labels' => [
            'select_image' => $t->text('Select image'),
            'clear'        => $t->text('Clear'),
        ],
    ]],
]);
```

### deprecated

以下のキーは deprecated です。`labels` 配列に移行してください。

| deprecated キー | 代替 |
|----------------|------|
| `button_label` | `labels['select']` / `labels['select_image']` / `labels['select_images']` / `labels['add']` |
| `clear_label` | `labels['clear']` |
| `remove_label` | `labels['remove']` |

---

## gallery field

複数画像を選択・管理するフィールドです。

```php
[
    'name'     => 'gallery_ids',
    'type'     => 'gallery',
    'label'    => 'Gallery',
    'labels'   => ['select_images' => '画像を選択', 'clear' => 'クリア'],
    'mime'     => 'image',
    'sortable' => true,
    'preview'  => true,
]
```

- 保存値: attachment ID の JSON 配列
- `wp_enqueue_media()` が呼ばれる
- SortableJS（`assets/vendor/sortable/Sortable.min.js`）がある場合は並び替え有効

---

## repeater field

複数のフィールドグループを動的に追加・削除できます。

```php
[
    'name'   => 'items',
    'type'   => 'repeater',
    'label'  => 'Items',
    'labels' => ['add' => '追加', 'remove' => '削除'],
    'min'    => 0,
    'max'    => null,
    'fields' => [
        ['name' => 'title',    'type' => 'text',  'label' => 'タイトル'],
        ['name' => 'image_id', 'type' => 'image', 'label' => '画像'],
    ],
]
```

- 保存値: JSON 配列

### group 設定（UI まとめ）

```php
[
    'name'   => 'items',
    'type'   => 'repeater',
    'label'  => 'Items',
    'group'  => [
        'label'        => '項目',
        'collapsible'  => true,
        'default_open' => true,
        'index_label'  => true,
    ],
    'fields' => [
        ['name' => 'title', 'type' => 'text', 'label' => 'タイトル'],
    ],
]
```

