#!/bin/sh
# Builds dist/colorpack_smart_search.zip from src/
set -e
cd "$(dirname "$0")"
rm -rf dist/pkg && mkdir -p dist/pkg
( cd src/com_aisearch && zip -qr ../../dist/pkg/com_aisearch.zip . -x '*/._*' '._*' '.DS_Store' )
( cd src/plg_content_aisearch && zip -qr ../../dist/pkg/plg_content_aisearch.zip . -x '*/._*' '._*' '.DS_Store' )
cp src/pkg_aisearch.xml src/pkg_script.php dist/pkg/
( cd dist/pkg && zip -qr ../colorpack_smart_search.zip . -x '._*' )
rm -rf dist/pkg
ls -la dist/colorpack_smart_search.zip
