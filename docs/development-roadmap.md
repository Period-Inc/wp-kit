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


### Pending: Infrastructure/Shortcode の取捨選択

`src/Infrastructure/Shortcode/*` は現時点では移動せず、後続で取捨選択する。

対象:

- `ButtonShortcode.php`
- `FetchTitleShortcode.php`
- `TemplateUrlShortcode.php`
- `ShortcodeInterface.php`

整理方針:

- `ShortcodeInterface` は WordPress FW基盤として残す可能性が高い
- `ButtonShortcode` / `FetchTitleShortcode` / `TemplateUrlShortcode` は実用ショートコード集またはサンプル扱いとして再分類する
- 候補は `src/WordPress/Shortcodes/` または `src/Examples/Shortcodes/`
- Relation 実装を優先し、この項目は後続タスクとする

### Pending: Editor UI strategy

PostAssets の `csscode` / `jscode` 編集UIは、通常の textarea を CodeMirror 化する方針とする。

対象:

- `csscode`
- `jscode`
- Sass / SCSS 編集
- JavaScript 編集

方針:

- 保存値は textarea / post meta と同期する
- CodeMirror は編集UIとして利用し、保存形式には依存させない
- CSS / SCSS / JavaScript の syntax highlight、indent、括弧対応、validation を検討する
- 管理画面での読み込み負荷を抑えるため、PostAssets の編集画面に限定して enqueue する
- リッチエディタではなくコードエディタとして扱う

WordPress 本体のメインエディターについては、ソース編集モードに Monaco Editor を導入する方向で検討する。

対象:

- 投稿本文の HTML / ブロックソース編集
- テーマ制作用途での高度なソース編集
- 将来的な validation / formatting / search / replace

方針:

- WordPress の通常編集体験は壊さない
- Monaco Editor はソース編集用UIとして限定的に導入する
- 保存データ構造には依存させない
- Gutenberg / Classic Editor との接続方法を別途検証する



### PostAssetsRenderer foundation

DONE:
- PostAssetsRenderer
- enqueue / inline responsibility split
- csscode_minified → csscode_compiled fallback
- ScriptStyleRegistrar integration


### DONE: MetaBox ↔ PostAssets compile integration

- MetaBox save 時に `csscode` 保存後、`PostAssetsCompileService::compileCss()` を呼ぶ
- compile source は DB 再読込ではなく、保存処理中の入力値を使う
- `post_assets_compile_service` option 経由で任意注入
- `csscode` 以外では compile しない
- compile failure 時も MetaBox save flow は継続
- PHPUnit integration tests 追加済み
