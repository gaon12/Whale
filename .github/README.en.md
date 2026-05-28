# Whale MediaWiki Skin

Default skin of [LibreWiki](https://librewiki.net). This skin will be the main skin for the Whale Wiki Engine.

## Development

Our canonical source is [GitHub.com](https://github.com/librewiki/whale-skin), and we receive bug reports via [GitHub.com](https://github.com/librewiki/whale-skin/issues) and patches via Github.com only. Any source code found elsewhere is mirrored there, and developers do not guarantee about the code found elsewhere to work.

Security vulnerability should be reported using email (dev (골뱅이!) librewiki.net) (replace (Korean text) with @).

## Installation

* Unzip to the MediaWiki Skins folder or perform a git clone. The name of the unzipped folder should be `Whale`.
* Add `wfLoadSkin( 'Whale' );` to your LocalSettings.php file.

## Configurations

Please set these variables in the LocalSettings.php file.

| Name | Description | Example Variable | Default Variable |
| ---- | ---- | ---- | ---- |
| `$wgWhaleMainColor` | `theme-color` configurations, main color of site | `#4188F1` | `#4188F1` |
| `$wgWhaleSecondColor` | Configure of second color of site | `#2774DC` | The value of `$wgWhaleMainColor` subtracted by `1A1415` |
| `$wgTwitterAccount` | Default Twitter account to set a mention | `librewiki` | (none) |
| `$wgWhaleOgLogo` | OpenGraph Image Logo | `https://librewiki.net/images/6/6a/Libre_favicon.png` | (Value of `$wgLogo`) |
| `$wgNaverVerification` | Naver Webmater Tool Verification Code | (Value supplied by Naver.com) | (none) |
| `$wgWhaleAdSetting` | Google Adsense Settings | `array( 'client' => '(Value supplied by Google)', 'header' => '1234567890', 'right' => '0987654321', 'belowarticle' => 1313135452 )` | (none) |
| `$wgWhaleAdGroupwgWhaleAdGroup` | Differentiation of ads by usergroup | `differ` | `null`|
| `$wgWhaleMobileReplaceAd` | In a mobile environment, move the sidebar ads to the bottom. | `true` | `false` |
| `$wgWhaleEnableLiveRC` | Enables 'Recent Cahnges' on the right side | `true` | `true` |
| `$wgWhaleMaxRecent` | Recent X edits appearing in 'Recent Changes' | `10` | `10` |
| `$wgWhaleNavBarLogoImage` | Logo image displayed on navigation bar  | `./image.png` | `null` |
| `$wgWhaleLiveRCArticleNamespaces` | Namespaces for the first tab in 'Recent Changes' | `[NS_MAIN, NS_PROJECT, NS_TEMPLATE, NS_HELP, NS_CATEGORY]` | `[NS_MAIN, NS_PROJECT, NS_TEMPLATE, NS_HELP, NS_CATEGORY]` |
| `$wgWhaleLiveRCTalkNamespaces` | Namespaces for the second tab in 'Recent Changes' | `[NS_TALK, NS_USER_TALK, NS_PROJECT_TALK, NS_FILE_TALK, NS_MEDIAWIKI_TALK, NS_TEMPLATE_TALK, NS_HELP_TALK, NS_CATEGORY_TALK]` | `[NS_TALK, NS_USER_TALK, NS_PROJECT_TALK, NS_FILE_TALK, NS_MEDIAWIKI_TALK, NS_TEMPLATE_TALK, NS_HELP_TALK, NS_CATEGORY_TALK]` |

## Navbar

Please fill out `MediaWiki:Whale-Navbar` article in the following format.

* First-Level menu:
  * `* icon=icon | display=display text | title=hover text | link=link | access=shortcut key | class=custom HTML classes | group=required user group | right=required user right`
* Second-Level menu:
  * `** icon=icon | display=display text | title=hover text | link=link | access=shortcut key | class=custom HTML classes | group=required user group | right=required user right`
* Third-Level menu:
  * `*** icon=icon | display=display text | title=hover text | link=link | access=shortcut key | class=custom HTML classes | group=required user group | right=required user right`

---

* All values are optional, but at least one of `icon` or `display` must be set.
* If `title` is not set, `display` is used instead.
* If you don't want to set some parameters, you can skip them. As an example, if you don't want to set an icon, skip `icon=...`.
* You can use i18n message names of MediaWiki for the values of `display` and `title` to show the i18n messages (e.g., write `recentchanges` to show `Recent changes`).
* Shortcut keys can be used as `Alt-Shift-(Key)`.
* When setting shortcuts, be careful not to overlap with the default shortcuts provided by MediaWiki.
* Custom classes are separated by `,` (e.g., write `classA, classB` to add `classA` and `classB` class).

You can see an example on [LibreWiki](https://librewiki.net/wiki/MediaWiki:Whale-Navbar).

## Rights

Four rights have been added to this to implement ad differentiation by user rights. if $wgWhaleAdGroup is set to 'differ', add user preferences to remove ads.
* blockads-header : User can remove header ads.
* blockads-right : User can remove header ads.
* blockads-belowarticle : User can remove ads below article.
* blockads-bottom : User can remove bottom ads.
