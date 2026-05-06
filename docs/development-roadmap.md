# Development Roadmap

## Phase 1 — Core Infrastructure

**Status:** Done

- Application singleton (`pwf()`)
- HookRegistrar, ShortcodeRegistrar
- ScriptStyleRegistrar (assets)
- PostTypeRegistrar + MetaBox
- StartHtmlRenderer, BodyRenderer, EndHtmlRenderer, DocumentRenderer
- SiteInfo, TitleResolver, TemplateFormatter
- ImageTagRenderer, PageNavigationRenderer
- Condition (isPostType / isUser)
- Translator

---

## Phase 2 — Support Layer

**Status:** Done

- Element (HTML builder, shorthands, array children)
- HtmlTemplate (mustache-style templating)
- ArrayUtil, Encoding, CssName, ImageUtil, LineEnding
- Calendar + CalendarDay
- Locale (WeekdayName, MonthName, BloodType, Zodiac)

---

## Phase 3 （Progress: 80%） — WordPress Application Features

**Status:** In progress

**Current focus:** MetaBox 拡張基盤 (PostMetaManager)

### Done

- [x] PostMetaManager — get / set / has、WordPress なしは noop

### TODO

- [ ] **Post Assets** — 投稿単位で CSS/JS を管理する
  - csscode / cssfile (インライン or ファイルパス)
  - jscode / jsfile (インライン or ファイルパス)
  - MetaBox フィールドとして保存、wp_head / wp_footer で出力

- [ ] **Relation** — post type 間の親子関係
  - 親/子 post_id を保持するメタフィールド
  - 管理画面の親/子リンク UI
  - 複数 post type 間の双方向参照

- [ ] **SiteData** — HTML コードスニペットの挿入
  - 別 Post やショートコードから HTML 断片を挿入
  - head / body open / footer へのインジェクションポイント

- [ ] **Calendar WP 展開** — Support\Calendar をスケジュール表として WordPress で使う
  - 投稿をカレンダー上にマッピング
  - WP クエリと Calendar::month() の統合ヘルパー

- [ ] **Featured Post** — チェックした投稿を一覧化
  - 管理画面でチェックボックスによる「おすすめ」フラグ
  - WP_Query 連携

- [ ] **TermSearch** — taxonomy term の AND/OR 検索・絞り込み
  - 複数 taxonomy をまたいだ絞り込み
  - AND / OR モード切り替え

- [ ] **ThemeImage** — テーマフォルダ内アセットアクセス
  - テーマ内画像の URL / パス解決ヘルパー
  - 存在チェック付き

- [ ] **Admin UI** — 本機能群の管理画面 view 整理
  - Phase 3 各機能の管理画面コンポーネントを統一

- [ ] **Breadcrumb** — パンくずリスト生成
  - 投稿タイプ・taxonomy・階層ページに対応

- [ ] **Posts shortcode** — `[posts]` ショートコード
  - post_type / taxonomy / 件数などをパラメータで指定
  - 出力テンプレートを差し替え可能

- [ ] **Include shortcode** — `[include]` ショートコード
  - テンプレートパーツをショートコードで埋め込む

- [ ] **旧 wpcf-shortcodes の再設計**
  - Legacy ショートコード群を現行アーキテクチャに移植
  - HookRegistrar ベースで再実装

---

## Notes

- Phase 3 の各機能は PostMetaManager を基盤として構築する
- WordPress なし環境での動作（noop / 空返却）を維持する
- HTML 生成は Element / View 層に委譲し、Renderer クラスはデータのみ扱う
- Legacy コードは編集しない（新規クラスとして並行実装）
