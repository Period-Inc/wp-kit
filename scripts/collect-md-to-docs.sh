#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
DOCS_DIR="$ROOT_DIR/docs"
INBOX_DIR="$DOCS_DIR/inbox"

mkdir -p "$INBOX_DIR"

find "$ROOT_DIR" \
  -type f \
  -name "*.md" \
  ! -path "$DOCS_DIR/*" \
  ! -path "$ROOT_DIR/.git/*" \
  ! -path "$ROOT_DIR/node_modules/*" \
  ! -path "$ROOT_DIR/vendor/*" \
  ! -path "$ROOT_DIR/wp-content/uploads/*" \
  ! -path "$ROOT_DIR/wp-content/cache/*" \
  ! -path "$ROOT_DIR/wp-content/ai1wm-backups/*" \
  -print0 |
while IFS= read -r -d '' file; do
  rel="${file#$ROOT_DIR/}"
  safe_name="${rel//\//__}"
  dest="$INBOX_DIR/$safe_name"

  if [ -e "$dest" ]; then
    base="${safe_name%.md}"
    dest="$INBOX_DIR/${base}__$(date +%Y%m%d_%H%M%S).md"
  fi

  mv "$file" "$dest"
  echo "moved: $rel -> ${dest#$ROOT_DIR/}"
done
