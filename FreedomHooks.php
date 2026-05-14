<?php

use MediaWiki\MediaWikiServices;

/**
 * Hook handlers for Freedom skin
 */
class FreedomHooks {
	/**
	 * @param OutputPage $out
	 * @param Skin $sk
	 * @param array &$bodyAttrs
	 */
	public static function onOutputPageBodyAttributes( OutputPage $out, Skin $sk, &$bodyAttrs ) {
		if ( $sk->getSkinName() === 'freedom' ) {
			$bodyAttrs['class'] .= ' Freedom';
		}
	}

	/**
	 * @param User $user
	 * @param array &$preferences
	 */
	public static function onGetPreferences( $user, &$preferences ) {
		$service = MediaWikiServices::getInstance();

		$preferences['freedom-layout-width'] = [
			'type' => 'select',
			'label-message' => 'freedom-pref-layout-width',
			'section' => 'freedom/layout',
			'options' => [
				wfMessage( 'freedom-layout-select-1000' )->text() => '1000px',
				wfMessage( 'freedom-layout-select-1100' )->text() => '1100px',
				wfMessage( 'freedom-layout-select-1200' )->text() => '1200px',
				wfMessage( 'freedom-layout-select-1300' )->text() => '1300px',
				wfMessage( 'freedom-layout-select-1400' )->text() => '1400px',
				wfMessage( 'freedom-layout-select-1500' )->text() => '1500px',
				wfMessage( 'freedom-layout-select-1600' )->text() => '1600px',
				wfMessage( 'freedom-layout-select-full' )->text() => '100%',
			],
			'default' => '1200px'
		];

		$preferences['freedom-dark-mode'] = [
			'type' => 'select',
			'label-message' => 'freedom-pref-dark-mode',
			'section' => 'freedom/appearance',
			'options' => [
				wfMessage( 'freedom-dark-mode-auto' )->text() => 'auto',
				wfMessage( 'freedom-dark-mode-light' )->text() => 'light',
				wfMessage( 'freedom-dark-mode-dark' )->text() => 'dark',
			],
			'default' => 'auto'
		];

		$preferences['freedom-font-size'] = [
			'type' => 'select',
			'label-message' => 'freedom-pref-font-size',
			'section' => 'freedom/appearance',
			'options' => [
				wfMessage( 'freedom-font-size-small' )->text() => '14px',
				wfMessage( 'freedom-font-size-medium' )->text() => '16px',
				wfMessage( 'freedom-font-size-large' )->text() => '18px',
			],
			'default' => '16px'
		];

		$preferences['freedom-sticky-toc'] = [
			'type' => 'toggle',
			'label-message' => 'freedom-pref-sticky-toc',
			'section' => 'freedom/features',
			'default' => true
		];

		$preferences['freedom-reading-progress'] = [
			'type' => 'toggle',
			'label-message' => 'freedom-pref-reading-progress',
			'section' => 'freedom/features',
			'default' => true
		];

		$preferences['freedom-collapsible-sections'] = [
			'type' => 'toggle',
			'label-message' => 'freedom-pref-collapsible-sections',
			'section' => 'freedom/features',
			'default' => true
		];
	}
}
