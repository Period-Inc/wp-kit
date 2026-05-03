# period-wp-framework

`period-wp-framework` は、WordPress テーマ制作やカスタマイズを補助する軽量ライブラリです。

- prefix: `period_wp_`
- entry function: `pwf()`
- namespace: `Period\WpFramework`

## 特徴

- テンプレート描画を支援する `HtmlTemplate`
- HTML5 解析を行う `HtmlDocument`
- CSSセレクタ用ID生成 `CssName`
- `Legacy` 配下は旧資産の保管用であり、新規コードから参照しない

## 使用例

### HtmlTemplate

```php
use Period\WpFramework\Support\HtmlTemplate;

$template = new HtmlTemplate('<a href="{{ url }}">{{ label }}</a>');
echo $template->render([
    'url' => '/contact',
    'label' => 'お問い合わせ',
]);
```

---

### HtmlDocument

```php
use Period\WpFramework\Support\HtmlDocument;

$doc = HtmlDocument::fromUrl('https://example.com');
$title = $doc->firstText('title');
```

---

### Element（仮想HTML）

```php
use Period\WpFramework\View\Element;

echo Element::div(['class' => 'card'], [
    Element::h3([], 'タイトル'),
    Element::p([], '説明文'),
    Element::a(['href' => '#'], 'リンク'),
])->render();
```

---

### raw HTML

```php
use Period\WpFramework\View\Element;

echo Element::div([], [
    Element::raw('<strong>強調</strong>')
])->render();
```

※ raw HTML は信頼済みデータのみ使用してください

---

### data-\* JSON

```php
use Period\WpFramework\View\Element;

echo Element::div([
    'data-user' => ['id' => 1, 'name' => 'omi']
])->render();
```

配列やオブジェクトは data-\* 属性の場合、自動的に JSON に変換されます。

---

### ショートコード

```text
[fetch_title url="https://example.com"]
```

`fetch_title` ショートコードは指定した URL から HTML を取得し、`<title>` を抽出して表示します。

`tax_query` は JSON 形式で指定できます。

```text
[posts tax_query='[{"taxonomy":"category","field":"slug","terms":["news"]}]']
```

詳細な使用方法は `docs/usage-tax-query.md` を参照してください。

---

### HttpClient

```php
use Period\WpFramework\Support\HttpClient;

$client = HttpClient::create();
$response = $client->get('https://example.com');

if ($response->isOk()) {
    echo $response->body();
}

$client->cookies()->set('preview', '1');
$response = $client->get('https://example.com/private');
```

---

### Url

```php
use Period\WpFramework\Support\Url;

echo Url::current();
echo Url::root();
echo Url::join('https://example.com/blog/post', '../about');
```

## インストール

```bash
composer install
composer dump-autoload
```

## 注意

- `Legacy` フォルダ以下は旧資産の保管用です。
- 新規コードでは `Legacy` 配下を直接参照しないようにしてください。

## Utilities

補助ユーティリティは以下を参照してください。

- CssName  
  URL や文字列を CSS class / id として安全な形式に変換  
  [docs/usage-cssname.md](docs/usage-cssname.md)

- LineEnding  
  改行コード定数  
  [docs/usage-line-ending.md](docs/usage-line-ending.md)
