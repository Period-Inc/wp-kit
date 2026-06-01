# Page Navigation

`PageNavigationRenderer` は WordPress のページナビゲーションを出力するレンダラーです。

### 使用例

```php
use Period\WpKit\Infrastructure\WordPress\PageNavigationRenderer;

$renderer = new PageNavigationRenderer();
echo $renderer->render([
    'class' => 'my-pagination',
    'prev_label' => '前へ',
    'next_label' => '次へ',
]);
```

### 引数

- `type`: string, デフォルト `archive`
- `prev_label`: string, デフォルト `前へ`
- `next_label`: string, デフォルト `次へ`
- `class`: string, デフォルト `period-wp-page-navigation`
- `show_numbers`: bool, デフォルト `true`
- `before`: string, HTML の前に挿入
- `after`: string, HTML の後に挿入

### 仕様

- WordPress の `paginate_links` と `get_pagenum_link` を利用
- `global $wp_query->max_num_pages` を参照
- `get_query_var('paged')` で現在ページを決定
- WordPress 関数が利用できない場合は空文字を返す
- ページ数が 1 以下の場合は空文字を返す
