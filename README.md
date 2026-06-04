# Whale MediaWiki Skin

한국어 설명은 바로 아래에 있습니다. English documentation starts in the second half of this file.

## 한국어

Whale은 MediaWiki용 반응형 스킨입니다. 리브레 위키에서 사용하던 스킨을 바탕으로 하며, 문서를 읽기 쉬운 상단 내비게이션, 오른쪽 사이드바, 최근 변경 위젯, 읽기 진행 표시줄, 공유 버튼, 다크 모드, 광고 슬롯 등을 제공합니다.

이 문서는 MediaWiki 스킨을 처음 설치하는 사람도 따라 할 수 있도록 천천히 설명합니다.

기존 Liberty 스킨과 무엇이 다른지 알고 싶다면 [Whale과 기존 Liberty 스킨의 차이](docs/liberty-differences.md)를 참고해 주세요. 현재 동작은 <https://beta.gaonwiki.com> 에서 테스트할 수 있습니다.

기능 추가 요청, 버그 리포트, 문서 개선, Pull Request를 환영합니다.

### 1. 필요한 것

Whale을 쓰려면 아래 조건이 필요합니다.

| 항목 | 필요한 값 |
| --- | --- |
| MediaWiki | 1.39 이상 |
| PHP | 8.2 이상 |
| Composer | PHP 의존성 설치에 필요 |
| 스킨 폴더 이름 | 반드시 `Whale` |
| 설치 위치 | MediaWiki 설치 폴더 안의 `skins/Whale` |

폴더 이름이 `whale`, `whale-skin`, `Whale-main`처럼 다르면 MediaWiki가 스킨을 찾지 못할 수 있습니다. 압축 파일을 풀었을 때 폴더 이름이 다르다면 `Whale`로 바꿔 주세요.

### 2. 설치하기

1. MediaWiki가 설치된 폴더를 엽니다.
2. 그 안에 있는 `skins` 폴더를 엽니다.
3. 이 저장소를 `skins/Whale` 위치에 넣습니다.

Git을 쓴다면 예시는 다음과 같습니다.

```bash
cd /path/to/mediawiki/skins
git clone https://github.com/gaon12/Whale.git Whale
cd Whale
composer install --no-dev --optimize-autoloader
```

압축 파일을 받아서 넣어도 됩니다. 중요한 것은 최종 경로가 이렇게 되는 것입니다.

```text
mediawiki/
  skins/
    Whale/
      skin.json
      SkinWhale.php
      WhaleRenderer.php
```

### 3. LocalSettings.php에 추가하기

MediaWiki의 `LocalSettings.php` 파일을 열고 아래 줄을 추가합니다.

```php
wfLoadSkin( 'Whale' );
```

이 줄은 “Whale 스킨을 MediaWiki에 등록한다”는 뜻입니다. 이 줄만 추가하면 사용자가 환경설정에서 Whale을 고를 수 있습니다.

사이트 전체 기본 스킨을 Whale로 바꾸고 싶다면 아래 줄도 추가합니다.

```php
$wgDefaultSkin = 'whale';
```

둘 다 넣는 예시는 다음과 같습니다.

```php
wfLoadSkin( 'Whale' );
$wgDefaultSkin = 'whale';
```

로그인 메뉴의 프로필 아이콘은 DiceBear PHP 라이브러리로 서버에서 SVG를 생성합니다. 브라우저 JavaScript나 DiceBear HTTP API를 호출하지 않습니다. 기본 스타일은 `identicon`이며, `LocalSettings.php`에서 스타일과 옵션을 바꿀 수 있습니다.

```php
$wgWhaleAvatarStyle = 'identicon';
$wgWhaleAvatarOptions = [
	'backgroundColor' => [ 'f8fafc' ],
	'borderRadius' => 12,
	'size' => 64,
];
```

### 4. 사용자 환경설정에서 바꿀 수 있는 것

Whale은 사용자마다 설정을 바꿀 수 있습니다. MediaWiki에 로그인한 뒤 환경설정으로 들어가면 Whale 관련 설정이 보입니다.

| 설정 영역 | 할 수 있는 일 |
| --- | --- |
| 테마 설정 | 테마 팔레트 선택, 다크 모드 방식 선택 |
| 레이아웃 설정 | 문서 폭 선택, 고정 내비게이션 바 해제, 오른쪽 사이드바 숨김, 하단 컨트롤 바 숨김 |
| 광고 설정 | 권한이 있는 사용자만 일부 광고 숨김 |

색상 설정은 이제 `primary`와 `secondary` 색상값을 직접 입력하는 방식이 아닙니다. 사용자는 `테마 팔레트` 드롭다운에서 원하는 팔레트를 고릅니다. 이렇게 하면 잘못된 색상값 때문에 화면이 깨질 가능성이 줄고, 라이트 모드와 다크 모드 색상이 함께 맞춰집니다.

### 5. 테마 팔레트

관리자는 사이트 기본 팔레트를 정할 수 있고, 사용자는 자기 환경설정에서 다른 팔레트를 고를 수 있습니다.

사이트 전체 기본 팔레트를 정하려면 `LocalSettings.php`에 아래처럼 적습니다.

```php
$wgWhaleTheme = 'han-river-blue';
```

사용 가능한 팔레트는 다음과 같습니다.

| 값 | 이름 | 밝은 모드 주 색상 | 밝은 모드 보조 색상 | 어두운 모드 주 색상 | 어두운 모드 보조 색상 |
| --- | --- | --- | --- | --- | --- |
| `han-river-blue` | 한강 블루 | `#336699` | `#003366` | `#99CCFF` | `#6699FF` |
| `hanbat-forest` | 한밭 포레스트 | `#006633` | `#336633` | `#99CC99` | `#66CC66` |
| `milk-vetch-purple` | 자운영 퍼플 | `#663399` | `#993366` | `#CCCCFF` | `#CC99FF` |
| `clay-roof` | 기와 브라운 | `#993300` | `#666633` | `#FFCC99` | `#CCCC99` |
| `jeju-teal` | 제주 틸 | `#006666` | `#336666` | `#99CCCC` | `#66CCCC` |
| `camellia-red` | 동백 레드 | `#993333` | `#663333` | `#FF9999` | `#CC9999` |
| `ginkgo-gold` | 은행 골드 | `#666600` | `#663300` | `#FFCC33` | `#CCCC66` |

사용자가 환경설정에서 `기본값`을 고르면 사이트 관리자가 정한 `$wgWhaleTheme`를 따릅니다. 사용자가 특정 팔레트를 직접 고르면 그 사용자에게는 그 팔레트가 우선 적용됩니다.

고급 호환 설정으로 `$wgWhalePrimaryColor`, `$wgWhaleSecondaryColor`, `$wgWhaleMainColor`, `$wgWhaleSecondColor`도 남아 있습니다. 다만 이 값들은 사이트 전체 설정용입니다. 사용자 환경설정 화면에서는 직접 색상 코드를 입력하지 않습니다.

### 6. 주요 기능

Whale이 제공하는 기능은 다음과 같습니다.

| 기능 | 설명 |
| --- | --- |
| 반응형 레이아웃 | 데스크톱과 모바일 화면에 맞춰 레이아웃이 바뀝니다. |
| 상단 내비게이션 | `MediaWiki:Whale-Navbar` 문서에서 메뉴를 설정합니다. |
| 검색 영역 | 상단 바에서 바로 문서를 검색할 수 있으며, 입력창과 버튼이 하나의 그룹으로 표시됩니다. |
| 로그인 모달 | 비로그인 사용자에게 로그인 창을 보여줍니다. |
| 오른쪽 사이드바 | 최근 변경과 최근 토론을 보여줍니다. |
| 읽기 진행 표시줄 | 문서를 얼마나 읽었는지 상단에 표시합니다. |
| 문단 번호 | 문서 목차 번호를 본문 문단명과 오른쪽 문단 목차에 함께 표시합니다. |
| 문단 접기 | 문단 제목 왼쪽의 `>` 토글로 하위 문단과 본문을 접거나 펼칩니다. 기본값은 모든 문단에 토글을 붙이며, 사용자 옵션으로 `#` 표식 문단만 접거나 끌 수 있습니다. |
| 접기 블록 | `{{{#!folding 제목 ... }}}` 형식의 접기/펼치기 블록을 제공합니다. |
| 스와이프 문단 목차 | 모바일/태블릿에서 화면 오른쪽 가장자리에서 왼쪽으로 밀어 PC형 문단 목차 패널을 열 수 있습니다. |
| 흐림 분류 | `[[분류:이름#blur]]`에서 표시 이름의 `#blur`를 제거하고 흐림 처리합니다. |
| 다크 모드 | 시스템 설정을 따르거나 사용자가 직접 밝은/어두운 모드를 고를 수 있습니다. |
| 하단 도구 메뉴 | 화면 하단의 도구 버튼에서 테마 전환, 단축 URL 같은 보조 기능을 엽니다. |
| 사용자 기여 그래프 | `사용자:예시`처럼 루트 사용자 문서에 GitHub 스타일 기여도 그래프를 표시합니다. |
| 단축 URL | 현재 문서 최신판의 revision id로 `/s/{base62}` 형식의 링크를 만들고 모달에서 복사할 수 있습니다. |
| 없는 문서 안내 | 존재하지 않는 일반 문서에는 새 문서 만들기, 검색, 대문 이동 버튼이 있는 Whale 404 안내 카드를 표시합니다. |
| 광고 슬롯 | 헤더, 오른쪽, 문서 아래, 하단 광고 위치를 설정할 수 있습니다. |
| 다국어 메시지 | 한국어, 영어, 일본어, 중국어 간체, 중국어 번체 메시지를 관리합니다. |

### 7. 접기 블록 꾸미기

접기 블록은 제목 헤더, 별도 토글 바, 본문 영역으로 나뉘어 출력됩니다. 기본 구조는 아래처럼 렌더링됩니다.

```html
<div class="whale-folding">
	<div class="whale-folding-header">
		<span class="whale-folding-title">제목</span>
	</div>
	<button class="whale-folding-toggle">펼치기 · 접기</button>
	<div class="whale-folding-body">본문</div>
</div>
```

사이트 CSS나 문서 템플릿에서 아래 CSS 변수를 바꾸면 색상과 테두리를 조정할 수 있습니다.

```css
.whale-folding {
	--whale-folding-accent: #00a3d9;
	--whale-folding-accent-contrast: #fff;
	--whale-folding-border: var(--whale-folding-accent);
	--whale-folding-body-bg: #fff;
	--whale-folding-row-bg: #f1f3f5;
}
```

본문 안에서 `whale-folding-header`, `whale-folding-band`, `whale-folding-row` 클래스를 쓰면 행정구역 표처럼 구역 제목과 행 배경을 맞춰 구성할 수 있습니다.

문단 접기는 일반 문단 제목에 자동으로 붙는 기능입니다. `$wgWhaleEnableSectionCollapse`가 `true`일 때 사용자 환경설정 `whale-content-section-collapse`로 동작 범위를 고를 수 있습니다.

| 값 | 동작 |
| --- | --- |
| `all` | 모든 문단 제목에 접기/펼치기 토글을 표시합니다. 기본값입니다. |
| `marked` | `=# 문단 #=`처럼 제목 양끝에 `#`를 붙인 문단만 처음부터 접습니다. |
| `off` | 문단 접기/펼치기 토글을 표시하지 않습니다. |

문단을 접으면 토글이 `>` 방향으로 바뀌고 문단명이 회색 톤으로 표시됩니다. 접힌 문단의 본문과 하위 문단은 `hidden` 처리되어 화면과 키보드 탐색에서 빠집니다.

모바일/태블릿 문단 목차는 `$wgWhaleEnableMobileFloatingToc`와 사용자 환경설정 `whale-layout-mobile-toc`로 켜고 끌 수 있습니다. 화면 오른쪽 가장자리에서 왼쪽으로 스와이프하거나 하단 목차 버튼을 누르면 패널이 열리고, 배경을 누르거나 목차 항목을 누르면 닫힙니다.

### 8. 단축 URL 서버 설정

단축 URL 기능은 문서 하단 도구 메뉴와 푸터 도구에서 현재 문서 최신판을 가리키는 짧은 링크를 보여줍니다. 복사 버튼이 있는 모달을 열 수 있으며, 실제 `/s/{code}` 요청은 `Special:WhaleShortUrl/{code}`로 넘기는 웹 서버 rewrite가 필요합니다. 스킨에는 바로 복사해서 쓸 수 있는 예시 파일이 들어 있습니다.

| 서버 | 예시 파일 |
| --- | --- |
| Apache | `docs/rewrite/apache-whale-short-url.htaccess` |
| Nginx | `docs/rewrite/nginx-whale-short-url.conf` |

Apache `.htaccess` 예시는 다음과 같습니다.

```apache
RewriteEngine On
RewriteRule ^s/([A-Za-z0-9]+)$ /index.php?title=Special:WhaleShortUrl/$1 [L,QSA]
```

Nginx 예시는 다음과 같습니다.

```nginx
location ~ ^/s/([A-Za-z0-9]+)$ {
	rewrite ^/s/([A-Za-z0-9]+)$ /index.php?title=Special:WhaleShortUrl/$1 last;
}
```

경로 접두어를 바꾸고 싶다면 `LocalSettings.php`에서 `$wgWhaleShortUrlPathPrefix`도 같은 값으로 맞춰 주세요.

### 9. 내비게이션 메뉴 만들기

Whale의 상단 메뉴는 `MediaWiki:Whale-Navbar` 문서에서 설정합니다. 이 문서는 위키 안에서 직접 만들거나 수정합니다.

가장 간단한 예시는 다음과 같습니다.

```text
- text: 최근 바뀜
  link: Special:RecentChanges
  icon: sync
  access: r

- text: 도움말
  link: Help:Contents
  icon: book

- text: 공식 사이트
  link: https://example.org
  icon: link
```

하위 메뉴는 `children:` 아래에 다시 `- text:` 항목을 넣습니다.

```text
- text: 도움말
  link: Help:Contents
  icon: book
  children:
    - text: 초보자 안내
      link: Help:Beginner
      children:
        - text: 문법 도움말
          link: Help:Syntax
```

각 항목에서 쓸 수 있는 값은 다음과 같습니다.

| 값 | 뜻 |
| --- | --- |
| `icon` | 메뉴 앞에 보여줄 아이콘 이름 |
| `text` | 화면에 보이는 메뉴 이름 |
| `title` | 마우스를 올렸을 때 보이는 설명 |
| `link` | 이동할 문서 이름 또는 URL |
| `access` | 단축키로 쓸 문자 |
| `class` | 추가 CSS 클래스 |
| `group` | 이 메뉴를 볼 수 있는 사용자 그룹 |
| `right` | 이 메뉴를 볼 수 있는 사용자 권한 |

`icon`과 `text` 중 하나는 반드시 있어야 합니다. 둘 다 없으면 사용자가 무엇을 눌러야 하는지 알 수 없습니다. 기존 별표 문법도 fallback으로 읽지만, 새 메뉴를 만들 때는 위 블록형 구조를 권장합니다.

### 10. 최근 변경 사이드바 설정

오른쪽 사이드바의 최근 변경 기능은 기본적으로 켜져 있습니다.

끄고 싶다면 `LocalSettings.php`에 아래처럼 적습니다.

```php
$wgWhaleEnableLiveRC = false;
```

표시할 개수를 바꾸고 싶다면 다음 값을 조정합니다.

```php
$wgWhaleMaxRecent = 10;
```

첫 번째 탭에 보여줄 이름공간은 `$wgWhaleLiveRCArticleNamespaces`로 정합니다.

```php
$wgWhaleLiveRCArticleNamespaces = [ NS_MAIN, NS_PROJECT, NS_TEMPLATE, NS_HELP, NS_CATEGORY ];
```

두 번째 탭, 즉 최근 토론에 보여줄 이름공간은 `$wgWhaleLiveRCTalkNamespaces`로 정합니다.

```php
$wgWhaleLiveRCTalkNamespaces = [
	NS_TALK,
	NS_USER_TALK,
	NS_PROJECT_TALK,
	NS_FILE_TALK,
	NS_MEDIAWIKI_TALK,
	NS_TEMPLATE_TALK,
	NS_HELP_TALK,
	NS_CATEGORY_TALK,
];
```

### 11. 광고 설정

Google AdSense 광고를 쓰려면 `$wgWhaleAdSetting`을 설정합니다.

```php
$wgWhaleAdSetting = [
	'client' => 'ca-pub-0000000000000000',
	'header' => '1234567890',
	'right' => '0987654321',
	'belowarticle' => '1313135452',
	'bottom' => '4242424242',
];
```

각 위치의 의미는 다음과 같습니다.

| 값 | 위치 |
| --- | --- |
| `client` | Google AdSense 클라이언트 ID |
| `header` | 문서 위쪽 광고 |
| `right` | 오른쪽 사이드바 광고 |
| `belowarticle` | 문서 본문 아래 광고 |
| `bottom` | 화면 하단 광고 |

모바일에서 오른쪽 사이드바 광고를 하단으로 옮기고 싶다면 다음 값을 켭니다.

```php
$wgWhaleMobileReplaceAd = true;
```

광고 숨김 권한을 사용자 권한별로 다르게 주고 싶다면 다음 값을 설정합니다.

```php
$wgWhaleAdGroup = 'differ';
```

이때 사용할 수 있는 권한은 다음 네 가지입니다.

| 권한 | 의미 |
| --- | --- |
| `blockads-header` | 헤더 광고를 숨길 수 있음 |
| `blockads-right` | 오른쪽 광고를 숨길 수 있음 |
| `blockads-belowarticle` | 문서 아래 광고를 숨길 수 있음 |
| `blockads-bottom` | 하단 광고를 숨길 수 있음 |

### 12. GitHub Actions와 배포

저장소에는 품질 게이트 workflow와 선택형 beta 배포 workflow가 들어 있습니다.

품질 게이트는 push와 pull request에서 아래 명령을 실행합니다.

```bash
npm run format:check
npm run lint
npm test
composer test
composer analyse
```

beta 배포 workflow는 저장소 변수 `WHALE_ENABLE_DEPLOY`가 `true`일 때만 동작합니다. 다음 secrets를 GitHub 저장소에 등록해야 합니다.

| Secret | 의미 |
| --- | --- |
| `WHALE_DEPLOY_KEY` | rsync/SSH에 사용할 private key |
| `WHALE_DEPLOY_HOST` | 배포 대상 host |
| `WHALE_DEPLOY_PORT` | SSH port. 없으면 `22` |
| `WHALE_DEPLOY_USER` | SSH 사용자 |
| `WHALE_DEPLOY_PATH` | 서버의 `skins/Whale` 경로 |

### 13. 설정 전체 표

| 설정 | 설명 | 예시 | 기본값 |
| --- | --- | --- | --- |
| `$wgWhaleTheme` | 사이트 기본 테마 팔레트 | `'han-river-blue'` | `null` |
| `$wgWhalePrimaryColor` | 고급 사이트 전체 주 색상 override | `'#336699'` | `null` |
| `$wgWhaleSecondaryColor` | 고급 사이트 전체 보조 색상 override | `'#003366'` | `null` |
| `$wgWhaleMainColor` | 예전 버전 호환용 주 색상 | `'#4188F1'` | `'#4188F1'` |
| `$wgWhaleSecondColor` | 예전 버전 호환용 보조 색상 | `'#2774DC'` | `null` |
| `$wgWhaleOgLogo` | OpenGraph에 사용할 로고 이미지 | `'https://example.org/logo.png'` | `$wgLogo` |
| `$wgTwitterAccount` | Twitter/X 카드에 넣을 계정 | `'librewiki'` | 설정 안 함 |
| `$wgNaverVerification` | 네이버 사이트 인증 코드 | `'abcdef...'` | 설정 안 함 |
| `$wgWhaleAvatarStyle` | 로그인 메뉴 DiceBear 프로필 아이콘 스타일 | `'bottts'` | `'identicon'` |
| `$wgWhaleAvatarOptions` | DiceBear PHP 라이브러리에 전달할 프로필 아이콘 옵션 | `[ 'borderRadius' => 12 ]` | `[]` |
| `$wgWhaleEnableLiveRC` | 오른쪽 최근 변경 위젯 사용 | `true` | `true` |
| `$wgWhaleEnableSectionCollapse` | 문단 접기/펼치기 사용 | `true` | `true` |
| `$wgWhaleEnableFoldingBlocks` | `{{{#!folding ... }}}` 접기 블록 사용 | `true` | `true` |
| `$wgWhaleEnableBlurredCategories` | `#blur` 분류 흐림 표시 사용 | `true` | `true` |
| `$wgWhaleEnableFloatingToc` | PC 오른쪽 문단 목차 사용 | `true` | `true` |
| `$wgWhaleEnableMobileFloatingToc` | 모바일/태블릿 스와이프 문단 목차 사용 | `true` | `true` |
| `$wgWhaleMaxRecent` | 최근 변경 표시 개수 | `10` | `10` |
| `$wgWhaleNavBarLogoImage` | 상단 내비게이션 로고 이미지 | `'/images/logo.svg'` | `null` |
| `$wgWhaleLiveRCArticleNamespaces` | 최근 변경 탭의 이름공간 | `[ NS_MAIN, NS_PROJECT ]` | 문서, 프로젝트, 틀, 도움말, 분류 |
| `$wgWhaleLiveRCTalkNamespaces` | 최근 토론 탭의 이름공간 | `[ NS_TALK, NS_PROJECT_TALK ]` | 토론 이름공간들 |
| `$wgWhaleAdSetting` | 광고 클라이언트와 슬롯 설정 | 위 예시 참고 | `null` |
| `$wgWhaleAdGroup` | 권한별 광고 숨김 설정 사용 | `'differ'` | `null` |
| `$wgWhaleMobileReplaceAd` | 모바일에서 오른쪽 광고를 하단으로 이동 | `true` | `false` |
| `$wgWhaleEnableUserContributionGraph` | 사용자 문서 기여도 그래프 사용 | `true` | `true` |
| `$wgWhaleContributionGraphDays` | 기여도 그래프 표시 기간 | `365` | `365` |
| `$wgWhaleContributionGraphNamespaces` | 기여도 그래프 이름공간 제한 | `[ NS_MAIN ]` | `null` |
| `$wgWhaleContributionGraphCacheTTL` | 기여도 그래프 캐시 시간(초) | `3600` | `3600` |
| `$wgWhaleContributionGraphLevels` | 기여도 그래프 색상 단계 기준 | `[1, 3, 6, 10]` | `[1, 3, 6, 10]` |
| `$wgWhaleEnableShortUrls` | 푸터/도구 단축 URL과 특수문서 redirect 사용 | `true` | `true` |
| `$wgWhaleShortUrlPathPrefix` | 단축 URL 경로 접두어 | `'/s'` | `'/s'` |
| `$wgWhaleShortUrlRedirectStatus` | 단축 URL redirect 상태 코드 | `302` | `302` |
| `$wgWhaleEnableHeadingAnchors` | 문단 링크 복사 버튼 사용 | `true` | `true` |
| `$wgWhaleEnableReadingProgress` | 읽기 진행 표시줄 사용 | `true` | `true` |
| `$wgWhaleEnableResponsiveTables` | 넓은 표 가로 스크롤 처리 | `true` | `true` |
| `$wgWhaleEnableSortableTables` | 클라이언트 표 정렬 사용 | `true` | `true` |
| `$wgWhaleEnableContentFontScale` | 사용자별 본문 글자 크기 설정 사용 | `true` | `true` |
| `$wgWhaleEnableAnonThemeToggle` | 비로그인 사용자 테마 전환 버튼 사용 | `true` | `true` |
| `$wgWhaleEnableImageLazyLoad` | 문서 이미지 lazy load와 스켈레톤 표시 사용 | `true` | `true` |
| `$wgWhaleMobileUserToolsPosition` | 모바일 로그인/프로필 버튼 위치 | `'right'` | `'right'` |

### 14. 관리하는 언어

현재 이 저장소에서 직접 관리하는 다국어 파일은 아래 다섯 개입니다.

| 파일 | 언어 |
| --- | --- |
| `i18n/ko.json` | 한국어 |
| `i18n/en.json` | 영어 |
| `i18n/ja.json` | 일본어 |
| `i18n/zh-hans.json` | 중국어 간체 |
| `i18n/zh-hant.json` | 중국어 번체 |

다른 언어는 MediaWiki의 일반 언어 fallback 규칙을 따릅니다.

## English

Whale is a responsive MediaWiki skin. It is based on the skin used by LibreWiki and provides a compact wiki reading layout with a top navigation bar, right sidebar, live recent changes, reading progress bar, share controls, dark mode, and optional ad slots.

This guide explains the setup slowly so a first-time MediaWiki skin installer can follow it.

If you are comparing Whale with the older Liberty-style skin, see [Differences between Whale and Liberty](docs/liberty-differences.md). You can test the current behavior at <https://beta.gaonwiki.com>.

Feature requests, bug reports, documentation fixes, and pull requests are welcome.

### 1. Requirements

| Item | Required value |
| --- | --- |
| MediaWiki | 1.39 or later |
| PHP | 8.2 or later |
| Composer | Required for PHP dependencies |
| Skin directory name | Exactly `Whale` |
| Install location | `skins/Whale` inside your MediaWiki directory |

The directory name matters. If the extracted folder is named `whale`, `whale-skin`, or `Whale-main`, rename it to `Whale`.

### 2. Install

Put this repository inside MediaWiki's `skins` directory.

```bash
cd /path/to/mediawiki/skins
git clone https://github.com/gaon12/Whale.git Whale
cd Whale
composer install --no-dev --optimize-autoloader
```

The final layout should look like this.

```text
mediawiki/
  skins/
    Whale/
      skin.json
      SkinWhale.php
      WhaleRenderer.php
```

### 3. Enable the Skin

Open `LocalSettings.php` and add:

```php
wfLoadSkin( 'Whale' );
```

Users can now choose Whale in their preferences. To make Whale the default skin for the whole site, also add:

```php
$wgDefaultSkin = 'whale';
```

The login menu profile icon is generated on the server with the DiceBear PHP library. It does not use browser-side JavaScript and does not call the DiceBear HTTP API. The default style is `identicon`, and administrators can customize the style and options from `LocalSettings.php`.

```php
$wgWhaleAvatarStyle = 'identicon';
$wgWhaleAvatarOptions = [
	'backgroundColor' => [ 'f8fafc' ],
	'borderRadius' => 12,
	'size' => 64,
];
```

### 4. User Preferences

Logged-in users can customize Whale from MediaWiki preferences.

| Area | What users can change |
| --- | --- |
| Theme settings | Theme palette and dark-mode behavior |
| Layout settings | Content width, fixed navigation bar, right sidebar, bottom control bar |
| Ads settings | Optional ad hiding when the user has the required rights |

Color customization no longer asks users to type `primary` and `secondary` color values. Users choose a `Theme palette` from a dropdown instead. This keeps light-mode and dark-mode colors paired correctly and avoids broken color input.

### 5. Theme Palettes

Site administrators can set the default palette:

```php
$wgWhaleTheme = 'han-river-blue';
```

Available palettes:

| Value | Name | Light primary | Light secondary | Dark primary | Dark secondary |
| --- | --- | --- | --- | --- | --- |
| `han-river-blue` | Han River Blue | `#336699` | `#003366` | `#99CCFF` | `#6699FF` |
| `hanbat-forest` | Hanbat Forest | `#006633` | `#336633` | `#99CC99` | `#66CC66` |
| `milk-vetch-purple` | Milk Vetch Purple | `#663399` | `#993366` | `#CCCCFF` | `#CC99FF` |
| `clay-roof` | Clay Roof | `#993300` | `#666633` | `#FFCC99` | `#CCCC99` |
| `jeju-teal` | Jeju Teal | `#006666` | `#336666` | `#99CCCC` | `#66CCCC` |
| `camellia-red` | Camellia Red | `#993333` | `#663333` | `#FF9999` | `#CC9999` |
| `ginkgo-gold` | Ginkgo Gold | `#666600` | `#663300` | `#FFCC33` | `#CCCC66` |

If a user chooses `Default`, Whale uses the site administrator's `$wgWhaleTheme`. If a user chooses a specific palette, that user's choice takes priority.

Advanced site-wide color override variables still exist for compatibility: `$wgWhalePrimaryColor`, `$wgWhaleSecondaryColor`, `$wgWhaleMainColor`, and `$wgWhaleSecondColor`. They are administrator settings, not user preference fields.

### 6. Main Features

| Feature | Description |
| --- | --- |
| Responsive layout | Adjusts for desktop and mobile screens. |
| Top navigation | Configured from `MediaWiki:Whale-Navbar`. |
| Search area | Search directly from the top bar with the input and action buttons rendered as one connected control. |
| Login modal | Shows a login dialog for anonymous users. |
| Right sidebar | Shows live recent changes and recent discussions. |
| Reading progress | Displays article reading progress at the top. |
| Section numbering | Shows table-of-contents numbers in both content headings and the right-side section index. |
| Section folding | Adds a `>` toggle to section headings so readers can collapse or expand the section body and nested subsections. By default all sections receive a toggle; users can limit folding to `#`-marked headings or disable it. |
| Folding blocks | Supports `{{{#!folding title ... }}}` folding blocks. |
| Swipe section index | Opens a desktop-like section index panel on mobile/tablet by swiping left from the right screen edge. |
| Blurred categories | Removes `#blur` from `[[Category:Name#blur]]` labels and blurs the category. |
| Dark mode | Follows the system setting or a user-selected mode. |
| Bottom tools menu | Opens helper actions such as theme toggle and short URL from the floating tools button. |
| User contribution graph | Shows a GitHub-style contribution grid on root user pages such as `User:Example`. |
| Short URLs | Adds a footer/tools modal that creates and copies `/s/{base62}` URLs from the page's latest revision id. |
| Missing-page help | Shows a Whale 404 card with create, search, and main-page actions for nonexistent normal pages. |
| Ad slots | Supports header, right, below-article, and bottom slots. |
| Localization | Maintains Korean, English, Japanese, Simplified Chinese, and Traditional Chinese messages. |

Short URLs appear in the footer/tools UI as a copyable modal for the latest revision of the current page. The public `/s/{code}` URL requires a web-server rewrite from `/s/{code}` to `Special:WhaleShortUrl/{code}`. See `docs/rewrite/apache-whale-short-url.htaccess` and `docs/rewrite/nginx-whale-short-url.conf`.

### 7. Styling Folding Blocks

Folding blocks render as a title header, a separate toggle bar, and a body area.

```html
<div class="whale-folding">
	<div class="whale-folding-header">
		<span class="whale-folding-title">Title</span>
	</div>
	<button class="whale-folding-toggle">Expand · collapse</button>
	<div class="whale-folding-body">Body</div>
</div>
```

Site CSS or wiki templates can customize the frame with CSS variables:

```css
.whale-folding {
	--whale-folding-accent: #00a3d9;
	--whale-folding-accent-contrast: #fff;
	--whale-folding-border: var(--whale-folding-accent);
	--whale-folding-body-bg: #fff;
	--whale-folding-row-bg: #f1f3f5;
}
```

Inside the body, `whale-folding-header`, `whale-folding-band`, and `whale-folding-row` can be used to build table-like grouped content.

Section folding is separate from folding blocks. When `$wgWhaleEnableSectionCollapse` is `true`, users can choose the `whale-content-section-collapse` scope:

| Value | Behavior |
| --- | --- |
| `all` | Show a collapse/expand toggle on every section heading. This is the default. |
| `marked` | Only headings written like `=# Section #=` start collapsed and receive folding treatment. |
| `off` | Do not decorate section headings with folding controls. |

Collapsed sections show a `>` indicator and muted heading text. The section body and nested lower-level sections are hidden with `hidden`, so they are removed from visual and keyboard navigation until expanded.

Mobile/tablet section index is controlled by `$wgWhaleEnableMobileFloatingToc` and the `whale-layout-mobile-toc` preference. Swipe left from the right screen edge or press the bottom table-of-contents button to open the panel; selecting a section or pressing the backdrop closes it.

### 8. Short URL Server Rewrite

Short URLs require a web-server rewrite from `/s/{code}` to `Special:WhaleShortUrl/{code}`. The skin renders a modal with the full short URL and copy button when the current page has a latest revision id; the internal code is used only to build the URL and is not shown as a separate label. Ready-to-copy rewrite examples are included in the repository.

| Server | Example file |
| --- | --- |
| Apache | `docs/rewrite/apache-whale-short-url.htaccess` |
| Nginx | `docs/rewrite/nginx-whale-short-url.conf` |

Apache `.htaccess` example:

```apache
RewriteEngine On
RewriteRule ^s/([A-Za-z0-9]+)$ /index.php?title=Special:WhaleShortUrl/$1 [L,QSA]
```

Nginx example:

```nginx
location ~ ^/s/([A-Za-z0-9]+)$ {
	rewrite ^/s/([A-Za-z0-9]+)$ /index.php?title=Special:WhaleShortUrl/$1 last;
}
```

If you change the path prefix, set `$wgWhaleShortUrlPathPrefix` in `LocalSettings.php` to the same value.

### 9. Navigation Menu

Create or edit `MediaWiki:Whale-Navbar` on your wiki.

```text
- text: Recent changes
  link: Special:RecentChanges
  icon: sync
  access: r

- text: Help
  link: Help:Contents
  icon: book

- text: Official site
  link: https://example.org
  icon: link
```

Put child items under `children:`.

```text
- text: Help
  link: Help:Contents
  icon: book
  children:
    - text: Beginner guide
      link: Help:Beginner
      children:
        - text: Syntax help
          link: Help:Syntax
```

Supported fields:

| Field | Meaning |
| --- | --- |
| `icon` | Icon name shown before the item |
| `text` | Visible menu label |
| `title` | Tooltip text |
| `link` | Page name or URL |
| `access` | Access-key suffix |
| `class` | Additional CSS classes |
| `group` | Required user group |
| `right` | Required user right |

At least one of `icon` or `text` must be present. The older asterisk syntax is still accepted as a fallback, but the block structure above is the recommended format for new menus.

### 10. Live Recent Sidebar

Live recent changes are enabled by default. To disable them:

```php
$wgWhaleEnableLiveRC = false;
```

To change the number of rows:

```php
$wgWhaleMaxRecent = 10;
```

To choose namespaces for the article tab:

```php
$wgWhaleLiveRCArticleNamespaces = [ NS_MAIN, NS_PROJECT, NS_TEMPLATE, NS_HELP, NS_CATEGORY ];
```

To choose namespaces for the discussion tab:

```php
$wgWhaleLiveRCTalkNamespaces = [
	NS_TALK,
	NS_USER_TALK,
	NS_PROJECT_TALK,
	NS_FILE_TALK,
	NS_MEDIAWIKI_TALK,
	NS_TEMPLATE_TALK,
	NS_HELP_TALK,
	NS_CATEGORY_TALK,
];
```

### 11. Ads

Example Google AdSense configuration:

```php
$wgWhaleAdSetting = [
	'client' => 'ca-pub-0000000000000000',
	'header' => '1234567890',
	'right' => '0987654321',
	'belowarticle' => '1313135452',
	'bottom' => '4242424242',
];
```

| Value | Position |
| --- | --- |
| `client` | Google AdSense client ID |
| `header` | Ad above the article |
| `right` | Right sidebar ad |
| `belowarticle` | Ad below the article body |
| `bottom` | Bottom ad |

Move the right sidebar ad to the bottom on mobile:

```php
$wgWhaleMobileReplaceAd = true;
```

Enable per-right ad hiding preferences:

```php
$wgWhaleAdGroup = 'differ';
```

Available rights:

| Right | Meaning |
| --- | --- |
| `blockads-header` | Can hide header ads |
| `blockads-right` | Can hide right ads |
| `blockads-belowarticle` | Can hide below-article ads |
| `blockads-bottom` | Can hide bottom ads |

### 12. GitHub Actions and Deployment

The repository includes a quality-gate workflow and an optional beta deploy workflow.

The quality gate runs these commands on push and pull request:

```bash
npm run format:check
npm run lint
npm test
composer test
composer analyse
```

The beta deploy workflow only runs when the repository variable `WHALE_ENABLE_DEPLOY` is set to `true`. Configure these GitHub secrets before enabling it:

| Secret | Meaning |
| --- | --- |
| `WHALE_DEPLOY_KEY` | Private key used by rsync/SSH |
| `WHALE_DEPLOY_HOST` | Deployment host |
| `WHALE_DEPLOY_PORT` | SSH port. Defaults to `22` when unset |
| `WHALE_DEPLOY_USER` | SSH user |
| `WHALE_DEPLOY_PATH` | Server path to `skins/Whale` |

### 13. Configuration Reference

| Setting | Description | Example | Default |
| --- | --- | --- | --- |
| `$wgWhaleTheme` | Default site theme palette | `'han-river-blue'` | `null` |
| `$wgWhalePrimaryColor` | Advanced site-wide primary override | `'#336699'` | `null` |
| `$wgWhaleSecondaryColor` | Advanced site-wide secondary override | `'#003366'` | `null` |
| `$wgWhaleMainColor` | Legacy primary color setting | `'#4188F1'` | `'#4188F1'` |
| `$wgWhaleSecondColor` | Legacy secondary color setting | `'#2774DC'` | `null` |
| `$wgWhaleOgLogo` | OpenGraph image logo | `'https://example.org/logo.png'` | `$wgLogo` |
| `$wgTwitterAccount` | Twitter/X card account | `'librewiki'` | unset |
| `$wgNaverVerification` | Naver site verification token | `'abcdef...'` | unset |
| `$wgWhaleAvatarStyle` | DiceBear profile icon style for the login menu | `'bottts'` | `'identicon'` |
| `$wgWhaleAvatarOptions` | Profile icon options passed to the DiceBear PHP library | `[ 'borderRadius' => 12 ]` | `[]` |
| `$wgWhaleEnableLiveRC` | Enable right-sidebar recent changes | `true` | `true` |
| `$wgWhaleEnableSectionCollapse` | Enable section folding | `true` | `true` |
| `$wgWhaleEnableFoldingBlocks` | Enable `{{{#!folding ... }}}` blocks | `true` | `true` |
| `$wgWhaleEnableBlurredCategories` | Enable blurred `#blur` categories | `true` | `true` |
| `$wgWhaleEnableFloatingToc` | Enable desktop right-side section index | `true` | `true` |
| `$wgWhaleEnableMobileFloatingToc` | Enable mobile/tablet swipe section index | `true` | `true` |
| `$wgWhaleMaxRecent` | Number of recent rows | `10` | `10` |
| `$wgWhaleNavBarLogoImage` | Top navigation logo image | `'/images/logo.svg'` | `null` |
| `$wgWhaleLiveRCArticleNamespaces` | Namespaces for recent changes | `[ NS_MAIN, NS_PROJECT ]` | Main, project, template, help, category |
| `$wgWhaleLiveRCTalkNamespaces` | Namespaces for recent discussions | `[ NS_TALK, NS_PROJECT_TALK ]` | Talk namespaces |
| `$wgWhaleAdSetting` | Ad client and slot settings | See above | `null` |
| `$wgWhaleAdGroup` | Enable per-right ad hiding | `'differ'` | `null` |
| `$wgWhaleMobileReplaceAd` | Move right ad to bottom on mobile | `true` | `false` |
| `$wgWhaleEnableUserContributionGraph` | Enable user-page contribution graphs | `true` | `true` |
| `$wgWhaleContributionGraphDays` | Contribution graph range | `365` | `365` |
| `$wgWhaleContributionGraphNamespaces` | Restrict contribution graph namespaces | `[ NS_MAIN ]` | `null` |
| `$wgWhaleContributionGraphCacheTTL` | Contribution graph cache TTL | `3600` | `3600` |
| `$wgWhaleContributionGraphLevels` | Contribution graph color thresholds | `[1, 3, 6, 10]` | `[1, 3, 6, 10]` |
| `$wgWhaleEnableShortUrls` | Enable footer short URLs and SpecialPage redirect | `true` | `true` |
| `$wgWhaleShortUrlPathPrefix` | Short URL path prefix | `'/s'` | `'/s'` |
| `$wgWhaleShortUrlRedirectStatus` | Short URL redirect status | `302` | `302` |
| `$wgWhaleEnableHeadingAnchors` | Enable heading copy-link buttons | `true` | `true` |
| `$wgWhaleEnableReadingProgress` | Enable reading progress bar | `true` | `true` |
| `$wgWhaleEnableResponsiveTables` | Enable responsive wide tables | `true` | `true` |
| `$wgWhaleEnableSortableTables` | Enable client-side table sorting | `true` | `true` |
| `$wgWhaleEnableContentFontScale` | Enable per-user article font size | `true` | `true` |
| `$wgWhaleEnableAnonThemeToggle` | Enable the light/dark toggle for anonymous users | `true` | `true` |
| `$wgWhaleEnableImageLazyLoad` | Enable lazy loading and skeleton placeholders for article images | `true` | `true` |
| `$wgWhaleMobileUserToolsPosition` | Mobile login/profile position | `'right'` | `'right'` |

### 14. Maintained Languages

This repository intentionally maintains these message files:

| File | Language |
| --- | --- |
| `i18n/ko.json` | Korean |
| `i18n/en.json` | English |
| `i18n/ja.json` | Japanese |
| `i18n/zh-hans.json` | Simplified Chinese |
| `i18n/zh-hant.json` | Traditional Chinese |

Other languages can fall back through MediaWiki's normal language fallback chain.
