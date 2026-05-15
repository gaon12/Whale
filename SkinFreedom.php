<?php

use MediaWiki\MediaWikiServices;

/**
 * SkinFreedom class
 */
class SkinFreedom extends SkinMustache {
	/**
	 * @return array
	 */
	public function getTemplateData() {
		$data = parent::getTemplateData();
		$userOptionsLookup = MediaWikiServices::getInstance()->getUserOptionsLookup();
		$user = $this->getUser();

		$data['freedom-layout-width'] = $userOptionsLookup->getOption( $user, 'freedom-layout-width' )
			?: $this->getConfig()->get( 'FreedomDefaultWidth' );
		$data['freedom-dark-mode'] = $userOptionsLookup->getOption( $user, 'freedom-dark-mode' );
		$data['freedom-font-size'] = $userOptionsLookup->getOption( $user, 'freedom-font-size' );

		$this->getOutput()->addModules( [ 'skins.freedom.js' ] );

		// Sidebar data
		$data['data-sidebar'] = $this->getSidebarData();

		// Personal tools
		$data['data-personal'] = $this->getPersonalToolsData();

		// Content navigation
		$data['data-content-navigation'] = $this->getContentNavigationData();

		return $data;
	}

	/**
	 * @return array
	 */
	private function getSidebarData() {
		// Logic to get sidebar links
		return [];
	}

	/**
	 * @return array
	 */
	private function getPersonalToolsData() {
		$personalTools = $this->getOutput()->getAttributes()['personal_tools'] ?? $this->getPersonalTools();
		$links = [];
		foreach ( $personalTools as $key => $item ) {
			$links[] = $this->makeListItem( $key, $item );
		}
		return $links;
	}

	/**
	 * @return array
	 */
	private function getContentNavigationData() {
		return $this->data['content_navigation'] ?? [];
	}
}
