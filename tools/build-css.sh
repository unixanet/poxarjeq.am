#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
tmp="$(mktemp)"
printf '@tailwind base;\n@tailwind components;\n@tailwind utilities;\n' > "$tmp"
npx --yes tailwindcss@3.4.17 -c tailwind.config.js -i "$tmp" -o assets/tailwind.css --minify
rm -f "$tmp"
ls -l assets/tailwind.css
