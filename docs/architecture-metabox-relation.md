# MetaBox / Relation 設計仕様

## MetaBox 値の保存仕様

すべてのフィールドは配列として保存する。

```php
'value'   => ['value']
['a','b'] => ['a','b']
null      => [null]
''        => ['']
```

未定義と空文字は分ける。

```text
未定義 → default または null
空文字 → '' のまま保持
```

空文字を null に変換しない。

## normalizeFieldValue

```php
private function normalizeFieldValue(mixed $value): array
{
    if ($value === null) {
        return [null];
    }

    return (array) $value;
}
```

## Repeater 保存仕様

既存行の空値は保存し、新規末尾の空行だけ無視する。

```text
count(values) > existingCount
かつ
最後の行が空
```

行配列の場合は、行内のすべての値が `''` または `null` なら空行とみなす。

## Relation 設計

PostType 同士の親子関係を定義する。親子が同一 PostType でもよい。

```php
RelationDefinition::make(
    parentPostType: string,
    childPostType: string,
    parentMetaKey: string = 'relation_parent',
    childrenMetaKey: string = 'relation_children'
);
```

Meta キーは次の通り。

```text
relation_parent   int|null
relation_children int[]
```

子側は親を保持する。

```php
relation_parent => 12
```

親側は子を保持する。

```php
relation_children => [34, 56]
```

## 責務分離

```text
PostMetaManager → 値の保存・取得
MetaBox         → 入力値の正規化
Relation        → 親子構造の定義
```

## 今後の実装範囲

```text
RelationRegistry
子側 hidden meta 自動保存
親側 children 自動更新
編集画面リンク導線
```
