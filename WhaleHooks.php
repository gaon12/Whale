<?php

//phpcs:ignore
class WhaleHooks {
	/**
	 * @since 1.17.0
	 * @param OutputPage $out
	 * @param Skin $sk
	 * @param array &$bodyAttrs
	 */
	public static function onOutputPageBodyAttributes( OutputPage $out, Skin $sk, &$bodyAttrs ) {
		if ( $sk->getSkinName() === 'whale' ) {
			$bodyAttrs['class'] .= ' Whale width-size';
		}
	}

	/**
	 * Set up user preferences specific to the Whale skin.
	 *
	 * @param User $user user
	 * @param Preferences &$preferences preferences
	 */
	public static function onGetPreferences( $user, &$preferences ) {
		global $wgWhaleAdSetting, $wgWhaleAdGroup;

		$service = MediaWiki\MediaWikiServices::getInstance();
		$userGroupManager = $service->getUserGroupManager();
		$userGroups = $userGroupManager->getUserGroups( $user );
		$permissionManager = $service->getPermissionManager();

		$preferences['whale-layout-width'] = [
			'type' => 'select',
			'label-message' => 'whale-pref-layout-width',
			'section' => 'whale/layout',
			'options' => [
				wfMessage( 'whale-layout-select-1000' )->text() => '1000px',
				wfMessage( 'whale-layout-select-1100' )->text() => '1100px',
				wfMessage( 'whale-layout-select-1200' )->text() => null,
				wfMessage( 'whale-layout-select-1300' )->text() => '1300px',
				wfMessage( 'whale-layout-select-1400' )->text() => '1400px',
				wfMessage( 'whale-layout-select-1500' )->text() => '1500px',
				wfMessage( 'whale-layout-select-1600' )->text() => '1600px',
			],
			'help-message' => 'whale-pref-layout-width-help',
			'default' => null
		];

		$preferences['whale-layout-navfix'] = [
			'type' => 'toggle',
			'label-message' => 'whale-pref-layout-navfix',
			'section' => 'whale/layout',
		];

		$preferences['whale-layout-sidebar'] = [
			'type' => 'toggle',
			'label-message' => 'whale-pref-layout-sidebar',
			'section' => 'whale/layout',
		];

		$preferences['whale-layout-controlbar'] = [
			'type' => 'toggle',
			'label-message' => 'whale-pref-layout-controlbar',
			'section' => 'whale/layout',
		];

		if (
			isset( $wgWhaleAdSetting['client'] ) && $wgWhaleAdSetting['client'] &&
			isset( $wgWhaleAdGroup ) && $wgWhaleAdGroup == 'differ'
		) {
			if (
				isset( $wgWhaleAdSetting['belowarticle'] ) && $wgWhaleAdSetting['belowarticle'] &&
				$permissionManager->userHasRight( $user, 'blockads-belowarticle' )
			) {
				$preferences['whale-ads-morearticle'] = [
					'type' => 'toggle',
					'label-message' => 'whale-pref-ads-belowarticle',
					'section' => 'whale/ads',
				];
			}

			if (
				isset( $wgWhaleAdSetting['header'] ) && $wgWhaleAdSetting['header'] &&
				$permissionManager->userHasRight( $user, 'blockads-header' )
			) {
				$preferences['whale-ads-header'] = [
					'type' => 'toggle',
					'label-message' => 'whale-pref-ads-header',
					'section' => 'whale/ads',
				];
			}

			if (
				isset( $wgWhaleAdSetting['right'] ) && $wgWhaleAdSetting['right'] &&
				$permissionManager->userHasRight( $user, 'blockads-right' )
			) {
				$preferences['whale-ads-rightads'] = [
					'type' => 'toggle',
					'label-message' => 'whale-pref-ads-right',
					'section' => 'whale/ads',
				];
			}

			if (
				isset( $wgWhaleAdSetting['bottom'] ) && $wgWhaleAdSetting['bottom'] &&
				$permissionManager->userHasRight( $user, 'blockads-bottom' )
			) {
				$preferences['whale-ads-bottom'] = [
					'type' => 'toggle',
					'label-message' => 'whale-pref-ads-bottom',
					'section' => 'whale/ads',
				];
			}
		}

		$preferences['whale-color-main'] = [
			'type' => 'text',
			'label-message' => 'whale-pref-color-main',
			'section' => 'whale/color',
			'help-message' => 'whale-pref-color-main-help'
		];

		$preferences['whale-color-second'] = [
			'type' => 'text',
			'label-message' => 'whale-pref-color-second',
			'section' => 'whale/color',
			'help-message' => 'whale-pref-color-second-help'
		];

		$preferences['whale-dark'] = [
			'type' => 'select',
			'label-message' => 'whale-pref-dark',
			'section' => 'whale/color',
			'options' => [
				wfMessage( 'whale-dark-default' )->text() => null,
				wfMessage( 'whale-dark-dark' )->text() => 'dark',
				wfMessage( 'whale-dark-light' )->text() => 'light'
			],
			'help-message' => 'whale-pref-dark-help',
			'default' => null
		];
	}
}
