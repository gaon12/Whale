# Whale과 기존 Liberty 스킨의 차이

이 문서는 Liberty 계열 스킨을 이미 알고 있는 운영자와 기여자가 Whale을 빠르게 비교할 수 있도록 정리한 참고 문서입니다.

Whale은 Liberty의 wiki-reading UX를 바탕으로 하지만, 단순한 색상 변경판이 아니라 MediaWiki 1.39 이상 환경에 맞춰 기능과 설정 구조를 다시 정리한 스킨입니다.

실제 동작은 <https://beta.gaonwiki.com> 에서 테스트할 수 있습니다.

## 주요 차이점

| 영역 | Liberty 계열에서 익숙한 방식 | Whale의 방식 |
| --- | --- | --- |
| 지원 대상 | 과거 Liberty 배포판의 설치 환경에 맞춰 사용 | MediaWiki 1.39 이상을 기준으로 정리 |
| 상단 내비게이션 | 사이트별 커스텀 메뉴와 스킨 로직이 섞이기 쉬움 | `MediaWiki:Whale-Navbar` 문서에서 메뉴를 선언하고, 아이콘/권한/그룹 조건을 함께 관리 |
| 검색 영역 | 입력창과 버튼이 분리되어 보일 수 있음 | 입력창과 이동/검색 버튼을 하나의 연결된 컨트롤처럼 표시 |
| 하단 도구 | 스크롤 버튼 중심 | 스크롤 버튼과 별도 도구 메뉴를 제공하고, 테마 전환과 단축 URL 같은 보조 기능을 묶음 |
| 단축 URL | 별도 확장/사이트 설정에 의존하기 쉬움 | 스킨에 `Special:WhaleShortUrl`과 `/s/{code}` rewrite 예시를 포함하고, 모달에서 복사 가능 |
| 없는 문서 화면 | MediaWiki 기본 없는 문서 안내에 가까움 | Whale 404 안내 카드로 새 문서 만들기, 검색, 대문 이동 CTA 제공 |
| 사용자 설정 | 레이아웃/색상 설정이 사이트별로 흩어질 수 있음 | Whale 환경설정 영역에서 테마, 레이아웃, 문서 표시, 광고 관련 설정을 정리 |
| 테마 색상 | 직접 색상값을 넣는 방식이 섞일 수 있음 | 사이트 기본 팔레트와 사용자별 팔레트 선택을 우선 사용 |
| 다크 모드 | 사이트 커스텀 CSS에 의존하는 경우가 많음 | 시스템 설정 따르기, 항상 어두운 모드, 항상 밝은 모드를 지원 |
| 문서 표시 | 기본 MediaWiki 출력 위주 | 문단 접기, 접기 블록, 흐림 분류, 넓은 표 대응, 문단 링크 복사, 이미지 lazy load 등을 스킨 기능으로 제공 |
| 사용자 문서 | 별도 도구가 필요할 수 있음 | 루트 사용자 문서에 기여도 그래프 표시 가능 |
| 광고 영역 | 사이트별 템플릿/스킨 수정에 의존 | 헤더, 오른쪽, 문서 아래, 하단 광고 슬롯과 권한별 숨김 설정 제공 |
| 모바일 레이아웃 | 사이트별 보정이 필요할 수 있음 | 모바일 내비게이션, 검색 그룹, 로그인/프로필 위치, 광고 이동 옵션을 스킨 설정으로 제공 |
| 다국어 메시지 | 사이트 커스텀 메시지에 의존 | 한국어, 영어, 일본어, 중국어 간체, 중국어 번체 메시지를 저장소에서 관리 |

## 운영자가 확인할 점

Whale은 Liberty와 비슷한 사용감을 목표로 하지만, 설정 이름과 제공 기능이 다릅니다. 기존 Liberty 설정을 그대로 복사하기보다는 아래 순서로 확인하는 것을 권장합니다.

1. `skins/Whale` 경로에 설치되어 있는지 확인합니다.
2. `LocalSettings.php`에 `wfLoadSkin( 'Whale' );`를 추가합니다.
3. 사이트 기본 스킨으로 사용할 경우 `$wgDefaultSkin = 'whale';`을 설정합니다.
4. 상단 메뉴는 `MediaWiki:Whale-Navbar` 문서로 옮깁니다.
5. 단축 URL을 사용할 경우 웹 서버 rewrite와 `$wgWhaleShortUrlPathPrefix` 값을 함께 확인합니다.
6. 기존 사이트 CSS/JS에서 Liberty 전용 선택자에 의존하는 부분이 있다면 Whale의 DOM 구조와 충돌하지 않는지 점검합니다.

## 기여와 피드백

기능 추가 요청, 버그 리포트, 문서 개선, Pull Request를 모두 환영합니다.

특히 다음과 같은 피드백이 도움이 됩니다.

- Liberty에서 쓰던 기능 중 Whale에서 빠진 기능
- 모바일 또는 다크 모드에서 깨지는 화면
- 특정 MediaWiki 확장과의 충돌
- 접근성, 키보드 조작, 다국어 메시지 개선
- 설치 문서나 설정 예시에서 헷갈리는 부분

## English summary

Whale is inspired by the Liberty-style wiki reading experience, but it is not just a visual recolor. It reorganizes the skin for MediaWiki 1.39+, adds first-class user preferences, palette-based themes, dark mode, bottom tools, short URLs, missing-page help, responsive table/image helpers, and maintained localization files.

You can test the current behavior at <https://beta.gaonwiki.com>.

Feature requests, bug reports, documentation fixes, and pull requests are welcome.
