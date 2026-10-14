#!/usr/bin/env bash
set -euo pipefail

version="${1:?Expected MediaWiki version 1.39 or 1.43}"
case "$version" in
  1.39|1.43) ;;
  *) echo "Unsupported fixture version: $version" >&2; exit 1 ;;
esac

skin_root="$(cd "$(dirname "$0")/.." && pwd)"
fixture_root="${WHALE_MEDIAWIKI_FIXTURE:?Set an empty fixture directory}"
mkdir "$fixture_root"
git clone --depth 1 --branch "REL${version/./_}" https://github.com/wikimedia/mediawiki.git "$fixture_root"

cd "$fixture_root"
# The EOL 1.39 dependency lock is used only in this disposable compatibility fixture.
if [[ "$version" == "1.39" ]]; then
  composer install --no-dev --prefer-dist --no-interaction --no-progress --no-security-blocking
else
  composer install --no-dev --prefer-dist --no-interaction --no-progress
fi
ln -s "$skin_root" skins/Whale
mkdir -p cache
php maintenance/install.php --dbtype sqlite --dbpath "$fixture_root/cache" \
  --server http://127.0.0.1:8080 --scriptpath '' --lang ko \
  --pass Whale-local-fixture-2026 'Whale Integration' FixtureAdmin
cat >> LocalSettings.php <<'PHP'

require_once __DIR__ . '/skins/Whale/vendor/autoload.php';
wfLoadSkin( 'Whale' );
$wgDefaultSkin = 'whale';
$wgSitename = '가온 위키';
$wgWhaleEnableLiveRC = true;
$wgWhaleEnableSectionCollapse = true;
$wgShowExceptionDetails = true;
$wgNamespacesWithSubpages[NS_MAIN] = true;
PHP
php maintenance/edit.php --user FixtureAdmin '대문' < "$skin_root/examples/main-page.wiki"
php maintenance/edit.php --user FixtureAdmin '상위 문서' <<'WIKITEXT'
상위 문서 예시입니다.
WIKITEXT
php maintenance/edit.php --user FixtureAdmin '상위 문서/넘겨주기 도착' <<'WIKITEXT'
== 개요 ==
넘겨주기 안내와 상위 문서 경로의 배치 예시입니다.
[[분류:예시]]
WIKITEXT
php maintenance/edit.php --user FixtureAdmin '넘겨주기 예시' <<'WIKITEXT'
#REDIRECT [[상위 문서/넘겨주기 도착]]
WIKITEXT
