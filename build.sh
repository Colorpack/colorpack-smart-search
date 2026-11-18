#!/bin/sh
# Builds dist/colorpack_smart_search-<version>.zip from src/
set -e
cd "$(dirname "$0")"
# the version in the file name comes from the manifest, so the zip always says which release it is
VERSION=$(sed -n 's|.*<version>\([^<]*\)</version>.*|\1|p' src/pkg_aisearch.xml | head -1)
[ -n "$VERSION" ] || { echo "no <version> in src/pkg_aisearch.xml" >&2; exit 1; }
rm -rf dist/pkg "dist/colorpack_smart_search-$VERSION.zip" && mkdir -p dist/pkg
( cd src/com_aisearch && zip -qr ../../dist/pkg/com_aisearch.zip . -x '*/._*' '._*' '.DS_Store' )
( cd src/plg_content_aisearch && zip -qr ../../dist/pkg/plg_content_aisearch.zip . -x '*/._*' '._*' '.DS_Store' )
( cd src/mod_smartsearch && zip -qr ../../dist/pkg/mod_smartsearch.zip . -x '*/._*' '._*' '.DS_Store' )
( cd src/plg_system_smartsearchbridge && zip -qr ../../dist/pkg/plg_system_smartsearchbridge.zip . -x '*/._*' '._*' '.DS_Store' )
cp src/pkg_aisearch.xml src/pkg_script.php dist/pkg/
( cd dist/pkg && zip -qr "../colorpack_smart_search-$VERSION.zip" . -x '._*' )
rm -rf dist/pkg
ls -la "dist/colorpack_smart_search-$VERSION.zip"
