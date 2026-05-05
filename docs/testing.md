# テスト方針

## 実行

```bash
composer test                                                          # 全テスト
php vendor/bin/phpunit tests/Infrastructure/WordPress/MetaBoxTest.php # 単一ファイル
```

## 方針

- WordPress なしで `php vendor/bin/phpunit` が通ること
- 戻り値と生成 HTML を検証する。副作用（フック登録など）は検証しない
- HTML の検証には Symfony DomCrawler を使う（プロジェクトに依存関係として含まれている）

## WordPress 関数のモック方法

`tests/bootstrap.php` にスタブ関数を定義している。PHP の `function_exists()` ガードにより、WordPress が存在する場合はスタブが無視される。

```
tests/bootstrap.php  ← require_once vendor/autoload.php + スタブ定義
phpunit.xml          ← bootstrap="tests/bootstrap.php"
```

### 定義済みスタブ

| 関数 | 挙動 |
|------|------|
| `wp_verify_nonce` | 常に `true` を返す |
| `update_post_meta` | `$METABOX_TEST_META_UPDATES` グローバルに呼び出しを記録する |
| `wp_enqueue_media` | 何もしない |
| `apply_filters` | `$PERIOD_WP_FILTER_VALUES[$hook]` が設定されていればその値、なければデフォルト値を返す |
| `wp_enqueue_script` | `$PERIOD_WP_ENQUEUED_SCRIPTS` グローバルに呼び出しを記録する |

`add_action`、`add_meta_box`、`get_post_meta` などはスタブがないため、これらを呼ぶ処理は `function_exists()` ガードで素通りする。

## グローバル変数の使い方

テスト間の独立性を保つため、`tearDown()` でリセットする。

```php
protected function tearDown(): void
{
    parent::tearDown();
    $_POST = [];

    global $METABOX_TEST_META_UPDATES, $PERIOD_WP_FILTER_VALUES, $PERIOD_WP_ENQUEUED_SCRIPTS;
    $METABOX_TEST_META_UPDATES = [];
    $PERIOD_WP_FILTER_VALUES   = [];
    $PERIOD_WP_ENQUEUED_SCRIPTS = [];
}
```

### フィルター値の設定

```php
global $PERIOD_WP_FILTER_VALUES;
$PERIOD_WP_FILTER_VALUES['period_wp_metabox_js_url'] = 'https://example.com/metabox.js';
```

### enqueue されたスクリプトの検索

```php
private function findEnqueuedScript(string $handle): ?array
{
    global $PERIOD_WP_ENQUEUED_SCRIPTS;
    foreach ((array) $PERIOD_WP_ENQUEUED_SCRIPTS as $script) {
        if ($script['handle'] === $handle) {
            return $script;
        }
    }
    return null;
}
```

## プライベートメソッドのテスト

`sanitizeFieldValue()` など private メソッドは Reflection API で呼び出す。

```php
$reflection = new \ReflectionClass($metaBox);
$method = $reflection->getMethod('sanitizeFieldValue');
$result = $method->invoke($metaBox, $field, $postData);
```

## どこまでテストしているか

**カバーしている:**
- WordPress なし環境での `register()` / `save()` の安全な素通り
- `render()` が正しい HTML 属性を出力すること
- `sanitizeFieldValue()` の各フィールド型（text / checkbox / select / image / media / gallery / repeater）
- `save()` の `$postData` vs `$_POST` 優先順位
- `enqueueMedia()` がフィルター値に応じてスクリプトを登録/スキップすること
- `enqueueMedia()` のバージョンが `filemtime` と一致すること
- `printMediaScript()` が何も出力しないこと

**カバーしていない:**
- WordPress が実際に存在する環境での統合動作
- `add_meta_box` / `save_post` フックの実際の発火
- SortableJS の動作（JS レイヤー、PHPUnit 対象外）
