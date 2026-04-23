#!/bin/bash
set -euo pipefail

cd "$(dirname "$0")"

php build.php
echo "Build OK"

cd dist
git init
git checkout -b gh-pages
git add -A
git commit -m "deploy $(date -u +%Y-%m-%dT%H:%M:%SZ)"
git remote add origin https://github.com/holly382/denik.git
git push -f origin gh-pages
echo "Deployed to https://holly382.github.io/denik/"
