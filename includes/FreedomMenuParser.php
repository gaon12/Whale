<?php

/**
 * FreedomMenuParser class
 */
class FreedomMenuParser {
	/** @var Skin */
	private $skin;

	/**
	 * @param Skin $skin
	 */
	public function __construct( Skin $skin ) {
		$this->skin = $skin;
	}

	/**
	 * @return array
	 */
	public function parseNavbar() {
		// Logic similar to old LibertyTemplate::parseNavbar
		// but returning a structured array for Mustache
		return [];
	}
}
