# MetaBox

## 基本的な使い方

```php
use Period\WpKit\Infrastructure\WordPress\MetaBox;

$box = new MetaBox([
    'id'        => 'my_box',
    'title'     => 'My Box',
    'post_type' => 'post',       // string または配列
    'context'   => 'normal',     // 省略可。デフォルト: normal
    'priority'  => 'default',    // 省略可。デフォルト: default
    'fields'    => [...],
]);

$box->register();
```

`register()` は `add_action` が存在しない環境（WordPress 外）では何もしない。

## フィールド定義

### 共通キー

| キー | 説明 |
|------|------|
| `name` | 必須。投稿メタのキー名にもなる |
| `type` | フィールド種別（後述）。省略時は `text` |
| `label` | 管理画面表示ラベル。省略時は `name` を使用 |
| `description` | 補足テキスト |
| `default` | デフォルト値 |

### フィールド種別

| type | 保存形式 | 備考 |
|------|----------|------|
| `text` | string | |
| `textarea` | string | |
| `checkbox` | `"1"` または `""` | |
| `select` | string | `options` キーに `[value => label]` を指定 |
| `hidden` | string | |
| `image` | attachment ID（string） | メディアピッカー |
| `media` | attachment ID（string） | MIME 種別を `mime` で指定可 |
| `gallery` | JSON（attachment ID の配列） | `sortable: true` で並び替え可 |
| `repeater` | JSON（オブジェクト配列） | 子フィールドを `fields` で定義 |

### ボタン文言の設定（labels）

各フィールドに `labels` 配列を指定することで、ボタン類の表示文言を一括設定できる。

```php
[
    'name'   => 'gallery_ids',
    'type'   => 'gallery',
    'labels' => [
        'select_images' => '画像を選択',  // gallery の選択ボタン
        'clear'         => 'クリア',      // クリアボタン（image/media/gallery 共通）
    ],
]
```

`labels` のキーと対応するフィールド種別:

| キー | 用途 | デフォルト |
|------|------|-----------|
| `select_image` | image の選択ボタン | `"Select image"` |
| `select` | media の選択ボタン | `"Select"` |
| `select_images` | gallery の選択ボタン | `"Select images"` |
| `add` | repeater の追加ボタン | `"Add"` |
| `clear` | image / media / gallery のクリアボタン | `"Clear"` |
| `remove` | repeater アイテムの削除ボタン | `"Remove"` |

**後方互換キー** — `labels` の代わりに個別キーでも指定できる。両方ある場合は `labels` が優先される。

| 個別キー | 対応する labels キー |
|----------|---------------------|
| `button_label` | type に応じた選択/追加ボタン |
| `clear_label` | `clear` |
| `remove_label` | `remove` |

### gallery フィールド

```php
[
    'name'    => 'gallery_ids',
    'type'    => 'gallery',
    'label'   => 'ギャラリー',
    'mime'    => 'image',   // デフォルト: image
    'sortable' => true,     // デフォルト: true
    'preview' => true,      // デフォルト: true
    'labels'  => [
        'select_images' => '画像を選択',
        'clear'         => 'クリア',
    ],
]
```

保存値は attachment ID の整数配列を JSON エンコードした文字列。空のときは `[]`。

### repeater フィールド

```php
[
    'name'   => 'items',
    'type'   => 'repeater',
    'fields' => [
        ['name' => 'title',    'type' => 'text'],
        ['name' => 'image_id', 'type' => 'image'],
    ],
    'min'    => 0,     // 削除の下限。デフォルト: 0
    'max'    => null,  // 追加の上限。null = 無制限
    'sortable' => true,
    'labels' => [
        'add'    => '追加',
        'remove' => '削除',
    ],
]
```

`group` キーで各アイテムをまとめたUIにできる。

```php
'group' => [
    'label'        => '項目',
    'collapsible'  => true,   // ヘッダークリックで折りたたみ
    'default_open' => true,   // 初期状態を開いた状態にする
    'index_label'  => true,   // ヘッダーに "項目 1", "項目 2" と連番を表示
]
```

## save() の挙動

### シグネチャ

```php
public function save(int $postId, array $postData = []): void
```

### データソースの優先順位

`$postData` が非空の場合はそれを使い、`$_POST` は一切参照しない。  
`$postData` が空配列（デフォルト）の場合は `$_POST` にフォールバックする。

```php
// WordPress の save_post フックから呼ばれる通常ルート
$box->save($postId);           // → $_POST を使用

// テスト・API からデータを明示的に渡すルート
$box->save($postId, $data);    // → $data を使用（$_POST を参照しない）
```

nonce の読み取り・検証もこのデータソースで行う。`$postData` を渡した場合、nonce も `$postData` 内に含めなければ検証が失敗する。

### 処理フロー

1. `wp_verify_nonce` が存在しなければ即リターン
2. nonce の存在・検証チェック
3. 自動保存・リビジョンのスキップ
4. `current_user_can('edit_post')` チェック
5. フィールドごとに `sanitizeFieldValue()` → `update_post_meta()`

各 WordPress 関数は `function_exists()` でガードされており、WordPress なしでも PHP エラーにならない。

## なぜ `$_POST` を直接使わないか

`$_POST` を直接読むと、テストでグローバル状態を書き換えるしかなくなる。`$postData` 引数で入力を注入できるようにすることで、テストが `$_POST` への依存なしに `save()` の挙動を検証できる。実運用では `$postData` を省略すれば従来と同じ `$_POST` 参照にフォールバックするため、後方互換は維持される。
