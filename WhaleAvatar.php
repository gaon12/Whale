<?php

use Composer\InstalledVersions;
use DiceBear\Avatar;
use DiceBear\OptionsDescriptor;
use DiceBear\Style;

if ( is_file( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

class WhaleAvatar {
	private const DEFAULT_STYLE = 'identicon';
	private const DEFAULT_SIZE = 64;

	/** @var array<string,Style> */
	private static array $styleCache = [];

	/**
	 * @param User $user MediaWiki user object
	 * @return string
	 */
	public static function getSeedForUser( User $user ): string {
		return hash( 'sha256', $user->getId() . ':' . $user->getName() );
	}

	/**
	 * @param string $seed Stable deterministic seed
	 * @param string|null $styleName DiceBear style name
	 * @param array<string,mixed> $options DiceBear avatar options
	 * @return string|null
	 */
	public static function createDataUri(
		string $seed,
		?string $styleName = null,
		array $options = []
	): ?string {
		if (
			!class_exists( InstalledVersions::class ) ||
			!class_exists( Style::class ) ||
			!class_exists( Avatar::class )
		) {
			return null;
		}

		$style = self::getStyle( $styleName ) ?? self::getStyle( self::DEFAULT_STYLE );
		if ( $style === null ) {
			return null;
		}

		$avatarOptions = self::normalizeOptions( $options, $style );
		$avatarOptions += [
			'size' => self::DEFAULT_SIZE,
			'idRandomization' => true,
		];
		$avatarOptions['seed'] = $seed;

		try {
			return ( new Avatar( $style, $avatarOptions ) )->toDataUri();
		} catch ( \Throwable ) {
			return ( new Avatar( $style, [
				'seed' => $seed,
				'size' => self::DEFAULT_SIZE,
				'idRandomization' => true,
			] ) )->toDataUri();
		}
	}

	private static function normalizeStyleName( ?string $styleName ): string {
		$styleName = strtolower( trim( (string)$styleName ) );
		if ( !preg_match( '/^[a-z0-9][a-z0-9-]*$/', $styleName ) ) {
			return self::DEFAULT_STYLE;
		}

		return $styleName;
	}

	private static function getStyle( ?string $styleName ): ?Style {
		$styleName = self::normalizeStyleName( $styleName );
		if ( isset( self::$styleCache[$styleName] ) ) {
			return self::$styleCache[$styleName];
		}

		$stylesPath = InstalledVersions::getInstallPath( 'dicebear/styles' );
		if ( !is_string( $stylesPath ) || $stylesPath === '' ) {
			return null;
		}

		$definitionPath = $stylesPath . DIRECTORY_SEPARATOR . 'src' .
			DIRECTORY_SEPARATOR . $styleName . '.json';
		if ( !is_file( $definitionPath ) ) {
			return null;
		}

		$definition = json_decode( file_get_contents( $definitionPath ) ?: '', true );
		if ( !is_array( $definition ) ) {
			return null;
		}

		try {
			self::$styleCache[$styleName] = new Style( $definition );
		} catch ( \Throwable ) {
			return null;
		}

		return self::$styleCache[$styleName];
	}

	/**
	 * @param array<string,mixed> $options
	 * @return array<string,mixed>
	 */
	private static function normalizeOptions( array $options, Style $style ): array {
		$descriptor = ( new OptionsDescriptor( $style ) )->toJSON();
		$normalized = [];
		foreach ( $options as $name => $value ) {
			if ( !preg_match( '/^[a-zA-Z][a-zA-Z0-9]*$/', $name ) ) {
				continue;
			}

			if ( isset( $descriptor[$name] ) && self::isSupportedOptionValue( $value ) ) {
				$normalized[$name] = $value;
			}
		}

		return $normalized;
	}

	/**
	 * @param mixed $value
	 * @return bool
	 */
	private static function isSupportedOptionValue( $value ): bool {
		if ( is_string( $value ) || is_int( $value ) || is_float( $value ) || is_bool( $value ) ) {
			return true;
		}

		if ( !is_array( $value ) ) {
			return false;
		}

		foreach ( $value as $nestedValue ) {
			if (
				!is_string( $nestedValue ) &&
				!is_int( $nestedValue ) &&
				!is_float( $nestedValue ) &&
				!is_bool( $nestedValue )
			) {
				return false;
			}
		}

		return true;
	}
}
