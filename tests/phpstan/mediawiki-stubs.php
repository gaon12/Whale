<?php

namespace {

if ( !defined( 'DB_REPLICA' ) ) {
	define( 'DB_REPLICA', 0 );
}

if ( !defined( 'NS_SPECIAL' ) ) {
	define( 'NS_SPECIAL', -1 );
}

if ( !defined( 'NS_USER' ) ) {
	define( 'NS_USER', 2 );
}

if ( !defined( 'NS_USER_TALK' ) ) {
	define( 'NS_USER_TALK', 3 );
}

function wfLoadSkin( string $skin ): void {
}

function wfMessage( string $key, mixed ...$params ): Message {
	return new Message();
}

class Message {
	public function text(): string {
		return '';
	}

	public function plain(): string {
		return '';
	}

	public function parse(): string {
		return '';
	}

	public function isDisabled(): bool {
		return false;
	}
}

class Config {
	public function get( string $name ): mixed {
		return null;
	}
}

class WebResponse {
	public function header( string $header, bool $replace = true, int $statusCode = 0 ): void {
	}
}

class WebRequest {
	public function getCookie( string $key ): ?string {
		return null;
	}

	public function getText( string $key, string $default = '' ): string {
		return $default;
	}

	public function response(): WebResponse {
		return new WebResponse();
	}
}

class User {
	public function isAnon(): bool {
		return false;
	}

	public function isRegistered(): bool {
		return true;
	}

	public function getName(): string {
		return '';
	}

	public function getId(): int {
		return 0;
	}

	public function getEmail(): string {
		return '';
	}

	public function getEmailAuthenticationTimestamp(): ?string {
		return null;
	}
}

class OutputPage {
	public string $mBodytext = '';

	public function addMeta( string $name, string $value ): void {
	}

	/**
	 * @param string|string[] $modules
	 */
	public function addModules( string|array $modules ): void {
	}

	public function addInlineStyle( string $style ): void {
	}

	public function getPageTitle(): string {
		return '';
	}

	public function getSkin(): ?Skin {
		return null;
	}

	public function showErrorPage( string $title, string $msg ): void {
	}

	public function disable(): void {
	}
}

class Skin {
	public function getSkinName(): string {
		return '';
	}

	public function getUser(): User {
		return new User();
	}

	public function getTitle(): ?MediaWiki\Title\Title {
		return null;
	}

	public function getSkin(): self {
		return $this;
	}

	public function getOutput(): OutputPage {
		return new OutputPage();
	}

	public function getRequest(): WebRequest {
		return new WebRequest();
	}

	public function getConfig(): Config {
		return new Config();
	}

	public function msg( string $key, mixed ...$params ): Message {
		return new Message();
	}

	public function getComponent( string $name ): SkinComponent {
		return new SkinComponent();
	}

	/**
	 * @return array<string,array<string,mixed>>
	 */
	public function getStructuredPersonalTools(): array {
		return [];
	}

	/**
	 * @return array<string,array<int,array<string,mixed>>>
	 */
	public function getFooterIcons(): array {
		return [];
	}

	/**
	 * @param array<string,mixed> $icon
	 */
	public function makeFooterIcon( array $icon ): string {
		return '';
	}

	/**
	 * @param array<string,mixed> $item
	 */
	public function makeListItem( string $key, array $item ): string {
		return '';
	}
}

class SkinMustache extends Skin {
	public function initPage( MediaWiki\Output\OutputPage $out ): void {
	}

	/**
	 * @return array<string,mixed>
	 */
	public function getTemplateData(): array {
		return [];
	}
}

class SkinComponent {
	/**
	 * @return array<string,mixed>
	 */
	public function getTemplateData(): array {
		return [];
	}
}

class SpecialPage {
	public function __construct( string $name = '' ) {
	}

	public static function getTitleFor( string $name, ?string $subPage = null ): MediaWiki\Title\Title {
		return new MediaWiki\Title\Title();
	}

	public function setHeaders(): void {
	}

	public function getOutput(): OutputPage {
		return new OutputPage();
	}

	public function getRequest(): WebRequest {
		return new WebRequest();
	}

	public function getConfig(): Config {
		return new Config();
	}

	public function getUser(): User {
		return new User();
	}
}

class TitleValue {
	public function __construct( int $namespace, string $title ) {
	}
}

class HtmlArmor {
	public function __construct( string $html ) {
	}
}

class Parser {
}

class StripState {
}

class Preferences {
}

class Sanitizer {
	public static function decodeCharReferences( string $text ): string {
		return $text;
	}
}

class MWDebug {
	public static function getHTMLDebugLog(): string {
		return '';
	}
}

class ArticleMetaDescription {
}

class Description2 {
}

class EchoEvent {
}

}

namespace MediaWiki {
	class MediaWikiServices {
		public static function getInstance(): self {
			return new self();
		}

		public function getUserOptionsLookup(): UserOptionsLookup {
			return new UserOptionsLookup();
		}

		public function getUserGroupManager(): UserGroupManager {
			return new UserGroupManager();
		}

		public function getPermissionManager(): PermissionManager {
			return new PermissionManager();
		}

		public function getLinkRenderer(): LinkRenderer {
			return new LinkRenderer();
		}

		public function getDBLoadBalancer(): DBLoadBalancer {
			return new DBLoadBalancer();
		}

		public function getMainWANObjectCache(): WANObjectCache {
			return new WANObjectCache();
		}

		public function getWikiPageFactory(): WikiPageFactory {
			return new WikiPageFactory();
		}

		public function getRestrictionStore(): RestrictionStore {
			return new RestrictionStore();
		}
	}

	class UserOptionsLookup {
		public function getOption( \User $user, string $option, mixed $default = null ): mixed {
			return $default;
		}
	}

	class UserGroupManager {
		/**
		 * @return string[]
		 */
		public function getUserGroups( \User $user ): array {
			return [];
		}
	}

	class PermissionManager {
		public function quickUserCan( string $action, \User $user, mixed $target ): bool {
			return true;
		}
	}

	class LinkRenderer {
		/**
		 * @param array<string,string> $attrs
		 */
		public function makeKnownLink( mixed $target, string $text, array $attrs = [] ): string {
			return '';
		}
	}

	class DBLoadBalancer {
		public function getConnection( int $index ): Database {
			return new Database();
		}
	}

	class Database {
		/**
		 * @param array<string,mixed>|string[] $tables
		 * @param array<string,mixed>|string[] $fields
		 * @param array<string,mixed>|string[] $conds
		 * @param array<string,mixed> $options
		 * @param array<string,mixed> $joinConds
		 * @return iterable<object>
		 */
		public function select(
			array $tables,
			array $fields,
			array $conds,
			string $caller = '',
			array $options = [],
			array $joinConds = []
		): iterable {
			return [];
		}

		/**
		 * @param array<string,mixed>|string[] $tables
		 * @param array<string,mixed>|string[] $fields
		 * @param array<string,mixed>|string[] $conds
		 * @param array<string,mixed> $options
		 * @param array<string,mixed> $joinConds
		 */
		public function selectRow(
			array $tables,
			array $fields,
			array $conds,
			string $caller = '',
			array $options = [],
			array $joinConds = []
		): ?object {
			return null;
		}

		public function addQuotes( string $value ): string {
			return "'" . $value . "'";
		}
	}

	class WANObjectCache {
		public function makeKey( mixed ...$parts ): string {
			return implode( ':', array_map( 'strval', $parts ) );
		}

		/**
		 * @template T
		 * @param callable():T $callback
		 * @return T
		 */
		public function getWithSetCallback( string $key, int $ttl, callable $callback ): mixed {
			return $callback();
		}
	}

	class WikiPageFactory {
		public function newFromTitle( Title\Title $title ): WikiPage {
			return new WikiPage();
		}
	}

	class WikiPage {
		public function getLatest(): int {
			return 0;
		}

		public function getLatestRevID(): int {
			return 0;
		}

		public function getRevisionRecord(): ?Revision\RevisionRecord {
			return null;
		}
	}

	class RestrictionStore {
		public function isProtected( Title\Title $title ): bool {
			return false;
		}
	}
}

namespace MediaWiki\Output {
	class OutputPage extends \OutputPage {
	}
}

namespace MediaWiki\Skin {
	class SkinMustache extends \SkinMustache {
	}
}

namespace MediaWiki\SpecialPage {
	class SpecialPage extends \SpecialPage {
	}
}

namespace MediaWiki\Html {
	class Html {
		/**
		 * @param array<string,mixed> $attrs
		 */
		public static function rawElement( string $element, array $attrs = [], string $contents = '' ): string {
			return '';
		}

		/**
		 * @param array<string,mixed> $attrs
		 */
		public static function element( string $element, array $attrs = [], string $contents = '' ): string {
			return '';
		}
	}
}

namespace MediaWiki\Linker {
	class Linker {
		public static function titleAttrib( string $name, string $options = '' ): string {
			return '';
		}

		public static function accesskey( string $name ): string {
			return '';
		}
	}
}

namespace MediaWiki\Title {
	class Title {
		public static function newMainPage(): self {
			return new self();
		}

		public static function makeTitle( int $namespace, string $title ): self {
			return new self();
		}

		public static function makeTitleSafe( int $namespace, string $title ): ?self {
			return new self();
		}

		public function getLocalURL(): string {
			return '';
		}

		public function getFullURL(): string {
			return '';
		}

		public function getPrefixedDBkey(): string {
			return '';
		}

		public function exists(): bool {
			return true;
		}

		public function getNamespace(): int {
			return 0;
		}

		public function getDBkey(): string {
			return '';
		}

		public function getLatestRevID(): int {
			return 0;
		}
	}
}

namespace MediaWiki\Content {
	class Content {
		public function getText(): string {
			return '';
		}
	}

	class TextContent extends Content {
	}
}

namespace MediaWiki\Revision {
	class RevisionRecord {
		public const RAW = 1;

		public function getContent( int $audience ): ?\MediaWiki\Content\Content {
			return null;
		}
	}
}
