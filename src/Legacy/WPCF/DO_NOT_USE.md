このディレクトリは旧資産の保管用です。

- 新規コードから参照しない
- require/include しない
- 将来の再設計時にのみ参照する

代替:

- HTML解析 → symfony/dom-crawler
- テンプレート → HtmlTemplate
