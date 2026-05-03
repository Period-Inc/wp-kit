# TaxQuery の使用方法

本ライブラリでは、ショートコードの `tax_query` を JSON で指定できます。

## 基本形式

```text
[posts tax_query='[
  {"taxonomy":"category","field":"slug","terms":["news"]}
]']
```

## 複数条件

```text
[posts tax_query='[
  {"taxonomy":"category","field":"slug","terms":["news"]},
  {"taxonomy":"post_tag","field":"slug","terms":["featured"]}
]']
```

## relation 指定

```text
[posts tax_query='{
  "relation":"OR",
  "queries":[
    {"taxonomy":"category","field":"slug","terms":["news"]},
    {"taxonomy":"post_tag","field":"slug","terms":["featured"]}
  ]
}']
```

## operator 指定

```text
[posts tax_query='[
  {
    "taxonomy":"category",
    "field":"slug",
    "terms":["news"],
    "operator":"NOT IN"
  }
]']
```

## 旧記法（非推奨）

従来の独自記法も引き続き利用可能ですが、将来的に廃止予定です。

```text
taxonomy=AND(category->slug(news)&post_tag->slug(featured))
```

## 仕様

- JSON が優先される
- JSON として解釈できない場合のみ旧記法を使用
- WordPress の `tax_query` 構造に準拠
- terms は配列で指定する（slug の文字列 / term_id の数値どちらも可）
