# Whale MediaWiki Skin

Whale is a responsive MediaWiki skin. It is based on the skin used by LibreWiki and provides a compact wiki reading layout with a top navigation bar, right sidebar, live recent changes, reading progress bar, share controls, dark mode, and optional ad slots.

This guide explains the setup slowly so a first-time MediaWiki skin installer can follow it.

## 1. Requirements

| Item | Required value |
| --- | --- |
| MediaWiki | 1.39 or later |
| Skin directory name | Exactly `Whale` |
| Install location | `skins/Whale` inside your MediaWiki directory |

The directory name matters. If the extracted folder is named `whale`, `whale-skin`, or `Whale-main`, rename it to `Whale`.

## 2. Install

Put this repository inside MediaWiki's `skins` directory.

```bash
cd /path/to/mediawiki/skins
git clone https://github.com/librewiki/whale-skin.git Whale
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

## 3. Enable the Skin

Open `LocalSettings.php` and add:

```php
wfLoadSkin( 'Whale' );
```

Users can now choose Whale in their preferences. To make Whale the default skin for the whole site, also add:

```php
$wgDefaultSkin = 'whale';
```

## 4. User Preferences

Logged-in users can customize Whale from MediaWiki preferences.

| Area | What users can change |
| --- | --- |
| Theme settings | Theme palette and dark-mode behavior |
| Layout settings | Content width, fixed navigation bar, right sidebar, bottom control bar |
| Ads settings | Optional ad hiding when the user has the required rights |

Color customization no longer asks users to type `primary` and `secondary` color values. Users choose a `Theme palette` from a dropdown instead. This keeps light-mode and dark-mode colors paired correctly and avoids broken color input.

## 5. Theme Palettes

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

## 6. Main Features

| Feature | Description |
| --- | --- |
| Responsive layout | Adjusts for desktop and mobile screens. |
| Top navigation | Configured from `MediaWiki:Whale-Navbar`. |
| Search area | Search directly from the top bar. |
| Login modal | Shows a login dialog for anonymous users. |
| Right sidebar | Shows live recent changes and recent discussions. |
| Reading progress | Displays article reading progress at the top. |
| Dark mode | Follows the system setting or a user-selected mode. |
| Ad slots | Supports header, right, below-article, and bottom slots. |
| Localization | Maintains Korean, English, Japanese, Simplified Chinese, and Traditional Chinese messages. |

## 7. Navigation Menu

Create or edit `MediaWiki:Whale-Navbar` on your wiki.

```text
* icon=sync | display=recentchanges | title=Recent changes | link=Special:RecentChanges | access=r
* icon=book | display=Help | title=Help | link=Help:Contents
* icon=link | display=Official site | link=https://example.org
```

Use more asterisks for submenus.

```text
* icon=book | display=Help | link=Help:Contents
** icon=link | display=Beginner guide | link=Help:Beginner
*** icon=link | display=Syntax help | link=Help:Syntax
```

Supported fields:

| Field | Meaning |
| --- | --- |
| `icon` | Icon name shown before the item |
| `display` | Visible menu label |
| `title` | Tooltip text |
| `link` | Page name or URL |
| `access` | Access-key suffix |
| `class` | Additional CSS classes |
| `group` | Required user group |
| `right` | Required user right |

At least one of `icon` or `display` must be present.

## 8. Live Recent Sidebar

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

## 9. Ads

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

Each ad position may also be configured as an array instead of a string slot ID.
Use this when a slot needs its own `data-ad-format`, `data-ad-layout`,
`data-ad-layout-key`, or `data-full-width-responsive` value.

```php
$wgWhaleAdSetting = [
	'client' => 'ca-pub-0000000000000000',
	'header' => [
		'slot' => '1234567890',
		'format' => 'auto',
		'fullWidthResponsive' => false,
	],
	'belowarticle' => [
		'slot' => '1313135452',
		'format' => 'fluid',
		'layout' => 'in-article',
	],
];
```

| Value | Position |
| --- | --- |
| `client` | Google AdSense client ID |
| `header` | Ad above the article |
| `right` | Right sidebar ad |
| `belowarticle` | Ad below the article body |
| `bottom` | Bottom ad |

The client ID must use the `ca-pub-...` form and slots must be numeric IDs.
Whale loads the modern AdSense script URL with the `client` query parameter and
`crossorigin="anonymous"`.

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

## 10. Configuration Reference

| Setting | Description | Example | Default |
| --- | --- | --- | --- |
| `$wgWhaleTheme` | Default site theme palette | `'han-river-blue'` | `null` |
| `$wgWhalePrimaryColor` | Advanced site-wide primary override | `'#336699'` | `null` |
| `$wgWhaleSecondaryColor` | Advanced site-wide secondary override | `'#003366'` | `null` |
| `$wgWhaleMainColor` | Site-wide primary color | `'#4188F1'` | `'#4188F1'` |
| `$wgWhaleSecondColor` | Site-wide secondary color | `'#2774DC'` | `null` |
| `$wgWhaleOgLogo` | OpenGraph image logo | `'https://example.org/logo.png'` | `$wgLogo` |
| `$wgTwitterAccount` | Twitter/X card account | `'gaonwiki'` | unset |
| `$wgNaverVerification` | Naver site verification token | `'abcdef...'` | unset |
| `$wgWhaleAvatarStyle` | DiceBear profile icon style for the login menu | `'bottts'` | `'identicon'` |
| `$wgWhaleAvatarOptions` | Profile icon options passed to DiceBear PHP | `[ 'borderRadius' => 12 ]` | `[]` |
| `$wgWhaleEnableContentSkeleton` | Expose the article content skeleton preference | `true` | `false` |
| `$wgWhaleEnableLiveRC` | Enable right-sidebar recent changes | `true` | `true` |
| `$wgWhaleMaxRecent` | Number of recent rows | `10` | `10` |
| `$wgWhaleNavBarLogoImage` | Top navigation logo image | `'/images/logo.svg'` | `null` |
| `$wgWhaleLiveRCArticleNamespaces` | Namespaces for recent changes | `[ NS_MAIN, NS_PROJECT ]` | Main, project, template, help, category |
| `$wgWhaleLiveRCTalkNamespaces` | Namespaces for recent discussions | `[ NS_TALK, NS_PROJECT_TALK ]` | Talk namespaces |
| `$wgWhaleAdSetting` | Ad client and slot settings | See above | `null` |
| `$wgWhaleAdGroup` | Enable per-right ad hiding | `'differ'` | `null` |
| `$wgWhaleMobileReplaceAd` | Move right ad to bottom on mobile | `true` | `false` |

## 11. Maintained Languages

This repository intentionally maintains these message files:

| File | Language |
| --- | --- |
| `i18n/ko.json` | Korean |
| `i18n/en.json` | English |
| `i18n/ja.json` | Japanese |
| `i18n/zh-hans.json` | Simplified Chinese |
| `i18n/zh-hant.json` | Traditional Chinese |

Other languages can fall back through MediaWiki's normal language fallback chain.
