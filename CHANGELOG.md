# Changelog

## [1.0.0] - 2026-05-05

### Added

- `SiteInfo` — WordPress サイト情報（name / description / charset / language / url / themeUri）の取得
- `TitleResolver` — ページタイトルの解決（`wp_get_document_title` / シングル / アーカイブ / 検索 / 404 対応）
- `TemplateFormatter` — `{{ key }}` プレースホルダー置換と `apply_filters` 統合
- `Translator` — WordPress i18n 関数（`__` / `esc_html__` / `esc_attr__` / `_n`）のラッパー
- `StartHtmlRenderer` — `<!doctype html>` から `<head>` 内要素（`<title>` 自動生成・`wp_head()` 対応）
- `BodyRenderer` — `<body>` 開始タグ（`get_body_class()` マージ・`wp_body_open()` 対応）
- `EndHtmlRenderer` — `wp_footer()` / `</body>` / `</html>` 出力
- `DocumentRenderer` — 上記3つを統合した1回呼び出しの HTML ドキュメント生成
- `ImageRenderer` — WordPress 添付ファイルを `<img>` タグとしてレンダリング
- `PageNavigationRenderer` — `paginate_links()` ラッパー
- `ShortcodeRegistrar` （WordPress namespace） — `[document]` / `[title]` / `[site_name]` ショートコード登録
- Template Tags — `pwk()->title()` / `pwk()->site()` / `pwk()->document()`
- MetaBox `labels` 配列によるラベル一括指定

### Changed

- MetaBox のラベル管理を `labels` 配列に統合（`select` / `select_image` / `select_images` / `add` / `clear` / `remove`）

### Deprecated

- MetaBox フィールドの `button_label` キー → `labels['select']` / `labels['select_image']` / `labels['select_images']` / `labels['add']` を使用
- MetaBox フィールドの `clear_label` キー → `labels['clear']` を使用
- MetaBox フィールドの `remove_label` キー → `labels['remove']` を使用
