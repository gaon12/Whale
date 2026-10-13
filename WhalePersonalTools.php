<?php

/** Read structured core personal tools without assuming optional fields. */
class WhalePersonalTools {
	/**
	 * Safely read a field from the first link of a structured personal tool.
	 */
	public static function getFirstLinkField( mixed $tool, string $field ): mixed {
		if ( !is_array( $tool ) ) {
			return null;
		}
		$links = $tool['links'] ?? null;
		if ( !is_array( $links ) ) {
			return null;
		}
		$first = $links[0] ?? null;
		if ( !is_array( $first ) ) {
			return null;
		}

		return $first[$field] ?? null;
	}

	/**
	 * Read the Echo notification counter from a structured personal tool.
	 */
	public static function getFirstLinkCounter( mixed $tool ): int {
		$data = self::getFirstLinkField( $tool, 'data' );
		if ( !is_array( $data ) ) {
			return 0;
		}
		$counter = $data['counter-num'] ?? null;

		return is_numeric( $counter ) ? (int)$counter : 0;
	}
}
