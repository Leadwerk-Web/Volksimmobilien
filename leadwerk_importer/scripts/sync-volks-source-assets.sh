#!/usr/bin/env bash
# Sync volksimmobilien static files from repo root into leadwerk_importer/source_assets.
# Removes leftover ACM files so the importer only scans Volks content.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
DEST="$(cd "$(dirname "$0")/.." && pwd)/source_assets"

mkdir -p "$DEST"

HTML_FILES=(
  index.html
  bewerten.html
  kaufen.html
  verkaufen.html
  ausland.html
  mallorca.html
  impressum.html
  datenschutz.html
  danke.html
  404.html
)

# Remove ACM / legacy HTML at destination root (not in Volks manifest).
if [[ -d "$DEST" ]]; then
  for existing in "$DEST"/*.html; do
    [[ -f "$existing" ]] || continue
    base="$(basename "$existing")"
    keep=0
    for allowed in "${HTML_FILES[@]}"; do
      if [[ "$base" == "$allowed" ]]; then
        keep=1
        break
      fi
    done
    if [[ "$keep" -eq 0 ]]; then
      rm -f "$existing"
    fi
  done
fi

# Remove ACM-only directories and root clutter.
rm -rf "$DEST/news"
rm -rf "$DEST/assets"
rm -f "$DEST/logo.png" \
  "$DEST/mobile-qa.css" \
  "$DEST/nav-active.js" \
  "$DEST/ajax1.html" \
  "$DEST/apple-touch-icon.png" \
  "$DEST/favicon-192x192.png" \
  "$DEST/favicon-32x32.png" \
  "$DEST/is-bao-registered-company-stages3-01.webp" \
  "$DEST/package.json" \
  "$DEST/robots.txt" \
  "$DEST/page-sitemap.xml" \
  "$DEST/webp-conversion-manifest.json" \
  "$DEST/webp-conversion-report.json"

for file in "${HTML_FILES[@]}"; do
  if [[ -f "$ROOT/$file" ]]; then
    cp "$ROOT/$file" "$DEST/$file"
  else
    echo "WARN: missing $ROOT/$file" >&2
  fi
done

if [[ -d "$ROOT/Fotos" ]]; then
  rsync -a --delete "$ROOT/Fotos/" "$DEST/Fotos/"
else
  echo "WARN: missing $ROOT/Fotos" >&2
fi

if [[ -f "$ROOT/css/style.css" ]]; then
  mkdir -p "$DEST/css"
  cp "$ROOT/css/style.css" "$DEST/css/style.css"
fi

if [[ -f "$ROOT/js/main.js" ]]; then
  mkdir -p "$DEST/js"
  cp "$ROOT/js/main.js" "$DEST/js/main.js"
fi

echo "Synced volksimmobilien source into: $DEST"
echo "HTML: ${#HTML_FILES[@]} files | Fotos: $(find "$DEST/Fotos" -type f 2>/dev/null | wc -l | tr -d ' ') files"
