<?php // @codingStandardsIgnoreLine

class WhaleNavbarParser {
	private SkinWhale $skin;

	public function __construct( SkinWhale $skin ) {
		$this->skin = $skin;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function parse( string $data ): array {
		return $this->parseSimpleNavbar( $data );
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	private function parseSimpleNavbar( string $data ): array {
		$items = [];
		$stack = [];

		foreach ( explode( "\n", $data ) as $line ) {
			$line = rtrim( $line, "\r" );
			if ( trim( $line ) === '' || preg_match( '/^\s*#/', $line ) ) {
				continue;
			}

			if ( preg_match( '/^([ \t]*)-\s*(.*?)\s*$/', $line, $matches ) ) {
				$level = $this->getNavbarIndentLevel( $matches[1], true );
				if ( $level < 1 || $level > 3 ) {
					continue;
				}

				$item = $this->buildNavbarItemFromFields( $this->parseNavbarInlineFields( $matches[2] ) );
				if ( $item === null ) {
					continue;
				}

				$item['children'] = [];
				if ( $level === 1 ) {
					$items[] = $item;
					$stack = [ 1 => &$items[count( $items ) - 1] ];
					continue;
				}

				if ( !isset( $stack[$level - 1] ) ) {
					continue;
				}

				$stack[$level - 1]['children'][] = $item;
				$childIndex = count( $stack[$level - 1]['children'] ) - 1;
				$stack[$level] = &$stack[$level - 1]['children'][$childIndex];
				foreach ( array_keys( $stack ) as $stackLevel ) {
					if ( $stackLevel > $level ) {
						unset( $stack[$stackLevel] );
					}
				}
				continue;
			}

			if ( preg_match( '/^([ \t]+)([a-z][a-z0-9_-]*)\s*:\s*(.*?)\s*$/i', $line, $matches ) ) {
				$level = $this->getNavbarIndentLevel( $matches[1], false );
				if ( !isset( $stack[$level] ) || strtolower( $matches[2] ) === 'children' ) {
					continue;
				}

				$this->applyNavbarField( $stack[$level], $matches[2], $matches[3] );
			}
		}

		return $items;
	}

	private function getNavbarIndentLevel( string $indent, bool $isItem ): int {
		$spaces = strlen( str_replace( "\t", '  ', $indent ) );
		if ( $isItem ) {
			return intdiv( $spaces, 4 ) + 1;
		}

		return intdiv( max( 0, $spaces - 2 ), 4 ) + 1;
	}

	/**
	 * @return array<string,string>
	 */
	private function parseNavbarInlineFields( string $line ): array {
		if ( preg_match( '/^(.+?)\s*->\s*(.+)$/', $line, $matches ) ) {
			return [
				'text' => trim( $matches[1] ),
				'link' => trim( $matches[2] ),
			];
		}

		if ( preg_match( '/^([a-z][a-z0-9_-]*)\s*:\s*(.*?)\s*$/i', $line, $matches ) ) {
			return [ strtolower( $matches[1] ) => trim( $matches[2] ) ];
		}

		return [ 'text' => trim( $line ) ];
	}

	/**
	 * @param array<string,string> $data
	 * @return array<string,mixed>|null
	 */
	private function buildNavbarItemFromFields( array $data ): ?array {
		$data = $this->normalizeNavbarFields( $data );
		$text = $this->messageOrRaw( $this->getNavbarField( $data, [ 'text', 'label', 'name', 'display' ] ) );
		$icon = isset( $data['icon'] ) && preg_match( '/^[a-z0-9-]+$/i', $data['icon'] )
			? strtolower( $data['icon'] )
			: null;
		if ( $icon === null && $text === '' ) {
			return null;
		}

		return [
			'access' => isset( $data['access'] ) && preg_match( '/^[0-9a-z]$/i', $data['access'] ) ? $data['access'] : null,
			'classes' => $this->getNavbarClasses( $this->getNavbarField( $data, [ 'class', 'classes' ] ) ),
			'href' => $this->getNavbarHref( $this->getNavbarField( $data, [ 'link', 'url', 'href' ] ) ),
			'icon' => $icon,
			'text' => $text,
			'title' => $this->messageOrRaw( $this->getNavbarField( $data, [ 'title', 'tooltip' ] ) ) ?: $text,
			'group' => $this->safeToken( $data['group'] ?? null ),
			'right' => $this->safeToken( $data['right'] ?? null ),
			'_fields' => $data,
		];
	}

	/**
	 * @param array<string,mixed> &$item
	 */
	private function applyNavbarField( array &$item, string $field, string $value ): void {
		$fields = $this->getRawNavbarFields( $item['_fields'] ?? null );
		$fields[strtolower( $field )] = $value;
		$updated = $this->buildNavbarItemFromFields( $fields );

		if ( $updated === null ) {
			return;
		}

		$children = $item['children'] ?? [];
		$item = $updated;
		$item['children'] = is_array( $children ) ? $children : [];
	}

	/**
	 * @param mixed $fields
	 * @return array<string,string>
	 */
	private function getRawNavbarFields( $fields ): array {
		if ( !is_array( $fields ) ) {
			return [];
		}

		$result = [];
		foreach ( $fields as $key => $value ) {
			if ( is_string( $key ) && is_string( $value ) ) {
				$result[$key] = $value;
			}
		}

		return $result;
	}

	/**
	 * @param array<string,string> $data
	 * @return array<string,string>
	 */
	private function normalizeNavbarFields( array $data ): array {
		$normalized = [];
		foreach ( $data as $key => $value ) {
			$normalized[strtolower( trim( $key ) )] = trim( $value );
		}

		return $normalized;
	}

	/**
	 * @param array<string,string> $data
	 * @param array<int,string> $names
	 */
	private function getNavbarField( array $data, array $names ): string {
		foreach ( $names as $name ) {
			if ( isset( $data[$name] ) ) {
				return trim( $data[$name] );
			}
		}

		return '';
	}

	private function getNavbarHref( string $link ): string {
		global $wgArticlePath;

		if ( $link === '' ) {
			return '#';
		}

		if ( $this->isSafeExternalNavbarHref( $link ) ) {
			return $link;
		}

		$encoded = str_replace( '%3A', ':', urlencode( $link ) );
		$articlePath = is_string( $wgArticlePath ) ? $wgArticlePath : '/index.php?title=$1';
		return str_replace( '$1', $encoded, $articlePath );
	}

	private function isSafeExternalNavbarHref( string $link ): bool {
		if ( preg_match( '/[\x00-\x20\x7F]/', $link ) ) {
			return false;
		}

		if ( !preg_match( '/^(?:https?:)?\/\//i', $link ) ) {
			return false;
		}

		$url = str_starts_with( $link, '//' ) ? 'https:' . $link : $link;
		$parts = parse_url( $url );

		if ( !is_array( $parts ) ) {
			return false;
		}

		$scheme = strtolower( (string)( $parts['scheme'] ?? '' ) );
		$host = (string)( $parts['host'] ?? '' );

		return ( $scheme === 'http' || $scheme === 'https' ) && $host !== '';
	}

	/**
	 * @return array<int,string>
	 */
	private function getNavbarClasses( string $classes ): array {
		$result = [];
		foreach ( preg_split( '/[\s,]+/', $classes ) ?: [] as $class ) {
			if ( preg_match( '/^[a-z0-9_-]+$/i', $class ) ) {
				$result[] = $class;
			}
		}

		return $result;
	}

	private function messageOrRaw( string $value ): string {
		if ( $value === '' ) {
			return '';
		}

		$message = $this->skin->msg( $value );
		return $message->isDisabled() ? $value : $message->text();
	}

	private function safeToken( ?string $value ): ?string {
		if ( !is_string( $value ) || $value === '' || !preg_match( '/^[a-z0-9_-]+$/i', $value ) ) {
			return null;
		}

		return $value;
	}
}
