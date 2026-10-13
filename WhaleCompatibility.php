<?php

/** Keep the declared MediaWiki 1.39 minimum compatible with later namespaces. */
class WhaleCompatibility {
	/**
	 * @param array<array-key,mixed> $links
	 * @return array<string,array{array-items:list<array{name:string,html:string}>}>
	 */
	public static function normalizeLegacyFooter( array $links ): array {
		$groups = [];
		foreach ( $links as $category => $values ) {
			if ( !is_string( $category ) || !is_array( $values ) ) {
				continue;
			}
			$items = [];
			foreach ( $values as $name => $html ) {
				if ( is_string( $name ) && is_string( $html ) && $html !== '' ) {
					$items[] = [ 'name' => $name, 'html' => $html ];
				}
			}
			$groups['data-' . $category] = [ 'array-items' => $items ];
		}
		return $groups;
	}

	public static function onRegistration(): void {
		$aliases = [
			'MediaWiki\Html\Html' => 'Html',
			'MediaWiki\Output\OutputPage' => 'OutputPage',
			'MediaWiki\Title\Title' => 'Title',
			'MediaWiki\Linker\Linker' => 'Linker',
			'MediaWiki\Content\Content' => 'Content',
			'MediaWiki\Content\TextContent' => 'TextContent',
			'MediaWiki\SpecialPage\SpecialPage' => 'SpecialPage',
			'MediaWiki\ResourceLoader\SkinModule' => 'ResourceLoaderSkinModule',
		];
		foreach ( $aliases as $modern => $legacy ) {
			if (
				!class_exists( $modern ) && !interface_exists( $modern ) &&
				( class_exists( $legacy ) || interface_exists( $legacy ) )
			) {
				class_alias( $legacy, $modern );
			}
		}
	}
}
