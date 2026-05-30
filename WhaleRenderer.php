<?php // @codingStandardsIgnoreLine

use MediaWiki\Html\Html;
use MediaWiki\Linker\Linker;
use MediaWiki\MediaWikiServices;
use MediaWiki\Content\Content;
use MediaWiki\Content\TextContent;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Title\Title;

if ( !class_exists( Html::class ) ) {
	class_alias( \Html::class, Html::class );
}

if ( !class_exists( Linker::class ) ) {
	class_alias( \Linker::class, Linker::class );
}

class WhaleRenderer {
	private SkinWhale $skin;

	public function __construct( SkinWhale $skin ) {
		$this->skin = $skin;
	}

	public function getNavMenu(): string {
		return $this->capture( function () {
			$this->navMenu();
		} );
	}

	public function getLoginModal(): string {
		return $this->capture( function () {
			$this->loginModal();
		} );
	}

	public function getLiveRecent(): string {
		return $this->capture( function () {
			$this->liveRecent();
		} );
	}

	public function getContentsToolbox(): string {
		return $this->capture( function () {
			$this->contentsToolbox();
		} );
	}

	public function getFooter(): string {
		return $this->capture( function () {
			$this->footer();
		} );
	}

	public function getAd( string $position ): string {
		return $this->capture( function () use ( $position ) {
			$this->buildAd( $position );
		} );
	}

	public function getIcon( string $icon ): string {
		return $this->renderIcon( $icon );
	}

	private function capture( callable $render ): string {
		ob_start();

		try {
			$render();
			$html = ob_get_clean();
		} catch ( Throwable $exception ) {
			ob_end_clean();
			throw $exception;
		}

		return $html === false ? '' : $html;
	}

	/**
	 * Nav menu function, build top menu.
	 */
	protected function navMenu() {
		$linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();
		$skin = $this->skin;
	?>
		<nav class="navbar navbar-dark">
			<a class="navbar-brand" href="<?php echo Title::newMainPage()->getLocalURL(); ?>"></a>
			<ul class="nav navbar-nav">
				<li class="nav-item">
					<?php echo $linkRenderer->makeKnownLink(
						new TitleValue( NS_SPECIAL, 'Recentchanges' ),
						// @codingStandardsIgnoreStart
						new HtmlArmor( $this->renderIcon( 'sync' ) . '<span class="hide-title">' . $skin->msg( 'recentchanges' )->escaped() . '</span>' ),
						// @codingStandardsIgnoreEnd )
						[
							'class' => 'nav-link',
							'title' => Linker::titleAttrib( 'n-recentchanges', 'withaccess' ),
							'accesskey' => Linker::accesskey( 'n-recentchanges' )
						] );?>
				</li>
				<li class="nav-item">
					<?php echo $linkRenderer->makeKnownLink(
						new TitleValue( NS_SPECIAL, 'Randompage' ),
						// @codingStandardsIgnoreStart
						new HtmlArmor( $this->renderIcon( 'random' ) . '<span class="hide-title">' . $skin->msg( 'randompage' )->escaped() . '</span>' ),
						// @codingStandardsIgnoreEnd
						[
							'class' => 'nav-link',
							'title' => Linker::titleAttrib( 'n-randompage', 'withaccess' ),
							'accesskey' => Linker::accesskey( 'n-randompage' )
						]
					); ?>
				</li>
				<?php echo $this->renderPortal( $this->parseNavbar() ); ?>
			</ul>
			<?php $this->loginBox(); ?>
			<?php $this->getNotification(); ?>
			<?php $this->searchBox(); ?>
		</nav>
	<?php
	}

	/**
	 * Search box function, build top menu's search box.
	 */
	protected function searchBox() {
		$skin = $this->skin;
		$request = $skin->getRequest();
	?>
		<form action="<?php echo htmlspecialchars( $skin->getConfig()->get( 'Script' ), ENT_QUOTES ); ?>" id="searchform" class="form-inline">
			<input type="hidden" name="title" value="<?php echo htmlspecialchars( SpecialPage::getTitleFor( 'Search' )->getPrefixedDBkey(), ENT_QUOTES ); ?>" />
			<div class="input-group">
				<input
					type="search"
					name="search"
					placeholder="<?php echo $skin->msg( 'searchsuggest-search' )->escaped(); ?>"
					title="<?php echo htmlspecialchars( Linker::titleAttrib( 'search' ), ENT_QUOTES ); ?>"
					accesskey="<?php echo htmlspecialchars( Linker::accesskey( 'search' ), ENT_QUOTES ); ?>"
					id="searchInput"
					class="form-control"
					value="<?php echo htmlspecialchars( $request->getText( 'search' ), ENT_QUOTES ); ?>"
					autocomplete="off">
				<span class="input-group-btn">
					<?php
					// @codingStandardsIgnoreStart 
					?>
					<button type="submit" name="go" value="<?php echo $skin->msg( 'go' )->escaped() ?>" id="searchGoButton" class="btn btn-secondary"><?php echo $this->renderIcon( 'eye' ); ?></button>
					<button type="submit" name="fulltext" value="<?php echo $skin->msg( 'searchbutton' )->escaped() ?>" id="mw-searchButton" class="btn btn-secondary">
						<?php echo $this->renderIcon( 'search' ); ?></button>
					<?php
					// @codingStandardsIgnoreEnd
					?>
				</span>
			</div>
		</form>
	<?php
	}

	/**
	 * Login box function, build top menu's login button.
	 */
	protected function loginBox() {
		global $wgWhaleUseGravatar;

		$skin = $this->skin;
		$user = $skin->getUser();
		$linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();
	?>
		<div class="navbar-login">
			<?php
			// If the user is logged in...
			if ( $user->isRegistered() ) {
				$personalTools = $this->skin->getWhalePersonalTools();
				// ...and Gravatar is enabled in site config...
				if ( $wgWhaleUseGravatar ) {
					// ...and the user has a confirmed email...
					if ( $user->getEmailAuthenticationTimestamp() ) {
						// ...then, and only then, build the correct Gravatar URL
						$email = trim( $user->getEmail() );
						$email = strtolower( $email );
						$email = md5( $email ) . '?d=identicon';
					} else {
						$email = '00000000000000000000000000000000?d=identicon&f=y';
					}
					$avatar = Html::element( 'img', [
						'class' => 'profile-img',
						'src' => '//secure.gravatar.com/avatar/' . $email
					] );
				} else {
					$avatar = '';
				}

				// SocialProfile support
				if ( class_exists( 'wAvatar' ) ) {
					$avatar = new wAvatar( $user->getId(), 'm' );
					$avatar = $avatar->getAvatarURL( [
						'class' => 'profile-img'
					] );
				}
			?>
				<div class="dropdown login-menu">
					<a class="dropdown-toggle" type="button" id="login-menu" 
						data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
						<?php echo $avatar; ?>
					</a>
					<div class="dropdown-menu dropdown-menu-right login-dropdown-menu" 
						aria-labelledby="login-menu">
						<?php echo $linkRenderer->makeKnownLink(
							Title::makeTitle( NS_USER, $user->getName() ),
							$user->getName(),
							[
								'id' => 'pt-userpage',
								'class' => 'dropdown-item',
								'title' => Linker::titleAttrib( 'pt-userpage', 'withaccess' ),
								'accesskey' => Linker::accesskey( 'pt-userpage' )
							]
						); ?>
						<div class="dropdown-divider"></div>
						<?php
						if ( class_exists( 'EchoEvent' ) ) {
							$notiCount = 0;
							if (
								isset( $personalTools['notifications-alert'] ) &&
								$personalTools['notifications-alert'] &&
								isset( $personalTools['notifications-notice'] ) &&
								$personalTools['notifications-notice']
							) {
								$notiCount = $personalTools['notifications-alert']['links'][0]['data']['counter-num'] +
									$personalTools['notifications-notice']['links'][0]['data']['counter-num'];
							}
							echo $linkRenderer->makeKnownLink(
								new TitleValue( NS_SPECIAL, 'Notifications' ),
								$skin->msg( 'notifications' )->plain() . ( $notiCount ? " ($notiCount)" : '' ),
								[
									'class' => 'dropdown-item',
									'title' => $skin->msg( 'tooltip-pt-notifications-notice' )->text()
								]
							);
						}
						?>
						<?php echo $linkRenderer->makeKnownLink(
							SpecialPage::getTitleFor( 'Contributions', $user->getName() ),
							$skin->msg( 'mycontris' )->plain(),
							[
								'class' => 'dropdown-item',
								'title' => Linker::titleAttrib( 'pt-mycontris', 'withaccess' ),
								'accesskey' => Linker::accesskey( 'pt-mycontris' )
							]
						); ?>
						<?php echo $linkRenderer->makeKnownLink(
							Title::makeTitle( NS_USER_TALK, $user->getName() ),
							$skin->msg( 'mytalk' )->plain(),
							[
								'class' => 'dropdown-item',
								'title' => Linker::titleAttrib( 'pt-mytalk', 'withaccess' ),
								'accesskey' => Linker::accesskey( 'pt-mytalk' )
							]
						); ?>
						<?php echo $linkRenderer->makeKnownLink(
							SpecialPage::getTitleFor( 'Watchlist' ),
							$skin->msg( 'watchlist' )->plain(),
							[
								'class' => 'dropdown-item',
								'title' => Linker::titleAttrib( 'pt-watchlist', 'withaccess' ),
								'accesskey' => Linker::accesskey( 'pt-watchlist' )
							]
						); ?>
						<div class="dropdown-divider"></div>
						<?php echo $linkRenderer->makeKnownLink(
							SpecialPage::getTitleFor( 'Preferences' ),
							$skin->msg( 'preferences' )->plain(),
							[
								'class' => 'dropdown-item',
								'title' => Linker::titleAttrib( 'pt-preferences', 'withaccess' ),
								'accesskey' => Linker::accesskey( 'pt-preferences' )
							]
						); ?>
						<div class="dropdown-divider view-logout"></div>
						<a href="<?php echo $personalTools['logout']['links'][0]['href']; ?>" 
							class="dropdown-item view-logout" 
							title="<?php
							// @codingStandardsIgnoreStart
							echo htmlspecialchars( Linker::titleAttrib( 'pt-logout', 'withaccess' ), ENT_QUOTES )
							// @codingStandardsIgnoreEnd
							?>">
							<?php echo $skin->msg( 'logout' )->escaped(); ?></a>
					</div>
				</div>
				<a href="<?php echo $personalTools['logout']['links'][0]['href']; ?>"
					class="hide-logout logout-btn" 
					title="<?php
					// @codingStandardsIgnoreStart
					echo htmlspecialchars( Linker::titleAttrib( 'pt-logout', 'withaccess' ), ENT_QUOTES );
					// @codingStandardsIgnoreEnd
					?>">
					<?php echo $this->renderIcon( 'sign-out' ); ?></a>
			<?php } else { ?>
				<a href="#" class="none-outline" data-toggle="modal" data-target="#login-modal">
					<?php echo $this->renderIcon( 'sign-in' ); ?>
				</a>
			<?php } ?>
		</div>
	<?php
	}

	/**
	 * Login model function, build login menu model.
	 */
	protected function loginModal() {
		$skin = $this->skin;
		$title = $skin->getTitle();
		$linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();

		// Probably no point in rendering a login window for the users who are
		// already logged in?
		if ( $skin->getUser()->isRegistered() ) {
			return;
		}

		// Turn off Continuous Integration warnings about "too long" lines which are
		// perfectly acceptable in this particular context
		// @codingStandardsIgnoreStart
	?>
		<div class="modal fade login-modal" id="login-modal" tabindex="-1" role="dialog" aria-labelledby="login-modalLabel" aria-hidden="true">
			<div class="modal-dialog modal-sm" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
						<h4 class="modal-title"><?php echo $skin->msg( 'whale-login' )->escaped() ?></h4>
					</div>
					<div class="modal-body">
						<div id="modal-login-alert" class="alert alert-hidden alert-danger" role="alert">
						</div>
						<form id="modal-loginform" name="userlogin" class="modal-loginform" method="post">
							<input class="loginText form-control" id="wpName1" tabindex="1" placeholder="<?php echo $skin->msg( 'userlogin-yourname-ph' )->escaped() ?>" value="" name="lgname">
							<label for="inputPassword" class="sr-only"><?php echo $skin->msg( 'userlogin-yourpassword' )->escaped() ?></label>
							<input class="loginPassword form-control" id="wpPassword1" tabindex="2" placeholder="<?php echo $skin->msg( 'userlogin-yourpassword-ph' )->escaped() ?>" type="password" name="lgpassword">
							<div class="modal-checkbox">
								<input name="lgremember" type="checkbox" value="1" id="lgremember" tabindex="3">
								<label for="lgremember"><?php echo $skin->msg( 'whale-remember' )->escaped() ?></label>
							</div>
							<input class="btn btn-success btn-block" type="submit" value="<?php echo $skin->msg( 'whale-login-btn' )->escaped() ?>" tabindex="4">
							<?php echo $linkRenderer->makeKnownLink(
								SpecialPage::getTitleFor( 'Userlogin' ),
								$skin->msg( 'userlogin-joinproject' ),
								[
									'class' => 'btn btn-primary btn-block',
									'tabindex' => 5,
									'type' => 'submit'
								],
								[
									'type' => 'signup',
									'returnto' => $title
								]
							); ?>
							<?php echo $linkRenderer->makeKnownLink(
								SpecialPage::getTitleFor( 'PasswordReset' ),
								$skin->msg( 'whale-forgot-pw' )->plain()
							); ?>
							<br>
							<?php echo $linkRenderer->makeKnownLink(
								SpecialPage::getTitleFor( 'Userlogin' ),
								$skin->msg( 'whale-login-alter' )->plain()
							); ?>
							<input type="hidden" name="action" value="login" />
							<input type="hidden" name="format" value="json" />
						</form>
					</div>
				</div>
			</div>
		</div>
	<?php
		// Turn Continuous Integration stuff back on
		// @codingStandardsIgnoreEnd
	}

	/**
	 * Live recent function, build right side's Recent menus.
	 */
	protected function liveRecent() {
		global $wgWhaleEnableLiveRC,
			$wgWhaleMaxRecent,
			$wgWhaleLiveRCArticleNamespaces,
			$wgWhaleLiveRCTalkNamespaces;

		// Don't bother outputting this if the live RC feature is disabled in
		// site configuration
		if ( !$wgWhaleEnableLiveRC ) {
			return;
		}

		$skin = $this->skin;
		$linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();
		$articleNS = implode( '|', $wgWhaleLiveRCArticleNamespaces );
		$talkNS = implode( '|', $wgWhaleLiveRCTalkNamespaces );
	?>
		<div class="live-recent" data-article-ns="<?php echo $articleNS ?>" 
			data-talk-ns="<?php echo $talkNS ?>">
			<div class="live-recent-header">
				<ul class="nav nav-tabs">
					<li class="nav-item">
						<a href="javascript:" class="nav-link active" id="whale-recent-tab1">
							<?php echo $skin->msg( 'recentchanges' )->escaped() ?>
						</a>
					</li>
					<li class="nav-item">
						<a href="javascript:" class="nav-link" id="whale-recent-tab2">
							<?php echo $skin->msg( 'whale-recent-discussions' )->escaped() ?>
						</a>
					</li>
				</ul>
			</div>
			<div class="live-recent-content">
				<ul class="live-recent-list" id="live-recent-list" aria-busy="true">
					<?php echo str_repeat(
						'<li class="live-recent-row live-recent-empty"><span class="recent-item recent-item-placeholder is-loading">&nbsp;</span></li>',
						$wgWhaleMaxRecent
					); ?>
				</ul>
			</div>
			<div class="live-recent-footer">
				<?php echo $linkRenderer->makeKnownLink(
					SpecialPage::getTitleFor( 'Recentchanges' ),
					new HtmlArmor( '<span class="label label-info">' .
						$skin->msg( 'whale-view-more' )->escaped() .
						'</span>' )
				); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Contents tool box function, build article tool menu that will show at article title right.
	 */
	protected function contentsToolbox() {
		$skin = $this->skin;
		$user = $skin->getUser();
		$services = MediaWikiServices::getInstance();
		$watchlistManager = $services->getWatchlistManager();
		$title = $skin->getTitle();
		$revid = $skin->getRequest()->getText( 'oldid' );
		$permissionManager = $services->getPermissionManager();
		$watched = $watchlistManager->isWatchedIgnoringRights( $user, $skin->getRelevantTitle() ) ? 'unwatch' : 'watch';
		$editable = $permissionManager->quickUserCan( 'edit', $user, $title );
		$action = $skin->getRequest()->getVal( 'action', 'view' );
		$linkRenderer = $services->getLinkRenderer();
		// $hasVisualEditor = ExtensionRegistry::getInstance()->isLoaded( 'VisualEditor' );
		if ( $title->getNamespace() != NS_SPECIAL ) {
			$companionTitle = $title->isTalkPage() ? $title->getSubjectPage() : $title->getTalkPage();
		?>
			<div class="content-tools">
				<div class="btn-group" role="group" aria-label="content-tools">
				<?php
				if ( $action != 'edit' ) {
					$editIcon = $this->renderIcon( $editable ? 'edit' : 'lock' ) . ' ';
					echo $linkRenderer->makeKnownLink(
						$title,
						new HtmlArmor( $editIcon . $skin->msg( 'edit' )->escaped() ),
						[
							'class' => 'btn btn-secondary tools-btn',
							'id' => 'ca-edit',
							'title' => Linker::titleAttrib( 'ca-edit', 'withaccess' ),
							'accesskey' => Linker::accesskey( 'ca-edit' )
						],
						$revid ? [ 'action' => 'edit', 'oldid' => $revid ] : [ 'action' => 'edit' ]
					);
				}
				if ( $action == 'edit' || $action == 'history' ) {
					echo $linkRenderer->makeKnownLink(
						$title,
						$titlename = $skin->msg( 'article' )->plain(),
						[
							'class' => 'btn btn-secondary tools-btn',
							'title' => Linker::titleAttrib( 'ca-nstab-main', 'withaccess' ),
							'accesskey' => Linker::accesskey( 'ca-nstab-main' )
						]
					);
				}
				if ( $companionTitle && $action != 'edit' ) {
					if ( $title->isTalkPage() && $action != 'history' ) {
						$titlename = $skin->msg( 'nstab-main' )->plain();
						$additionalArrayStuff = [
							'title' => Linker::titleAttrib( 'ca-nstab-main', 'withaccess' ),
							'accesskey' => Linker::accesskey( 'ca-nstab-main' )
						];
					} else {
						$titlename = $skin->msg( 'talk' )->plain();
						$additionalArrayStuff = [
							'title' => Linker::titleAttrib( 'ca-talk', 'withaccess' ),
							'accesskey' => Linker::accesskey( 'ca-talk' )
						];
					}
					echo $linkRenderer->makeKnownLink(
						$companionTitle,
						$titlename,
						[
							'class' => 'btn btn-secondary tools-btn',
						] + $additionalArrayStuff
					);
				}
				if ( $action != 'history' ) {
					echo $linkRenderer->makeKnownLink(
						$title,
						$skin->msg( 'history' )->plain(),
						[
							'class' => 'btn btn-secondary tools-btn',
							'title' => Linker::titleAttrib( 'ca-history', 'withaccess' ),
							'accesskey' => Linker::accesskey( 'ca-history' )
						],
						[ 'action' => 'history' ]
					);
				}
				if ( $action == 'view' ) { ?>
						<button type="button" class="btn btn-secondary tools-btn tools-share">
							<?php echo $this->renderIcon( 'share-square' ); ?>
							<?php echo $skin->msg( 'whale-share' )->escaped() ?>
						</button>
				<?php
				}
				// @codingStandardsIgnoreStart 
					?>
					<button type="button" class="btn btn-secondary tools-btn dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
						<span class="caret"></span>
					</button>
					<?php
					// @codingStandardsIgnoreEnd
					?>
					<div class="dropdown-menu dropdown-menu-right" role="menu">
						<?php
						if ( $title->inNamespaces( NS_USER, NS_USER_TALK ) ) {
							// "User contributions" link on user and user talk pages
							echo $linkRenderer->makeKnownLink(
								SpecialPage::getTitleFor( 'Contributions', $title->getText() ),
								$skin->msg( 'contributions' )->escaped(),
								[
									'class' => 'dropdown-item',
									'title' => Linker::titleAttrib( 't-contributions', 'withaccess' ),
									'accesskey' => Linker::accesskey( 't-contributions' )
								]
							);
						}
						echo $linkRenderer->makeKnownLink(
							$title,
							$skin->msg( 'whale-purge' )->plain(),
							[
								'class' => 'dropdown-item',
								'title' => $skin->msg( 'whale-tooltip-purge' )->plain() . ' [alt+shift+p]',
								'accesskey' => 'p'
							],
							[ 'action' => 'purge' ]
						);
						echo $linkRenderer->makeKnownLink(
							$title,
							$skin->msg( $watched )->plain(),
							[
								'class' => 'dropdown-item',
								'title' => Linker::titleAttrib( 'ca-' . $watched, 'withaccess' ),
								'accesskey' => Linker::accesskey( 'ca-' . $watched )
							],
							[ 'action' => $watched ]
						);
						echo $linkRenderer->makeKnownLink(
							SpecialPage::getTitleFor( 'Whatlinkshere', $title ),
							$skin->msg( 'whatlinkshere' )->plain(),
							[
								'class' => 'dropdown-item',
								'title' => Linker::titleAttrib( 't-whatlinkshere', 'withaccess' ),
								'accesskey' => Linker::accesskey( 't-whatlinkshere' )
							]
						);
						echo $linkRenderer->makeKnownLink(
							$title,
							$skin->msg( 'whale-info' )->plain(),
							[
								'class' => 'dropdown-item',
								'title' => $skin->msg( 'whale-tooltip-info' )->plain(),
							],
							[ 'action' => 'info' ]
						);
						if ( $permissionManager->quickUserCan( 'move', $user, $title ) && $title->exists() ) {
							echo $linkRenderer->makeKnownLink(
								SpecialPage::getTitleFor( 'Movepage', $title ),
								$skin->msg( 'move' )->plain(),
								[
									'class' => 'dropdown-item',
									'title' => Linker::titleAttrib( 'ca-move', 'withaccess' ),
									'accesskey' => Linker::accesskey( 'ca-move' )
								]
							);
						}
						if ( $permissionManager->quickUserCan( 'protect', $user, $title ) ) { ?>
							<div class="dropdown-divider"></div>
							<?php
							// different labels depending on whether the page is or isn't protected
							$protectionMsg = $this->isProtectedTitle( $title ) ? 'unprotect' : 'protect';
							echo $linkRenderer->makeKnownLink(
								$title,
								$skin->msg( $protectionMsg )->plain(),
								[
									'class' => 'dropdown-item',
									'title' => Linker::titleAttrib( 'ca-' . $protectionMsg, 'withaccess' ),
									'accesskey' => Linker::accesskey( 'ca-' . $protectionMsg )
								],
								[ 'action' => 'protect' ]
							); ?>
						<?php } ?>
						<?php if ( $permissionManager->quickUserCan( 'delete', $user, $title ) && $title->exists() ) {
						?>
							<div class="dropdown-divider"></div>
							<?php echo $linkRenderer->makeKnownLink(
								$title,
								$skin->msg( 'delete' )->plain(),
								[
									'class' => 'dropdown-item',
									'title' => Linker::titleAttrib( 'ca-delete', 'withaccess' ),
									'accesskey' => Linker::accesskey( 'ca-delete' )
								],
								[ 'action' => 'delete' ]
							); ?>
						<?php } ?>
					</div>
				</div>
			</div>
		<?php
		}
	}

	/**
	 * Footer function, build footer.
	 */
	protected function footer() {
		$footerData = $this->skin->getWhaleFooterData();
		foreach ( $footerData as $category => $categoryData ) {
			$links = $categoryData['array-items'] ?? [];
			if ( !$links ) {
				continue;
			}
			$categoryName = preg_replace( '/^data-/', '', $category );
		?>
			<ul class="footer-<?php echo htmlspecialchars( $categoryName ); ?>">
				<?php foreach ( $links as $link ) {
					$name = $link['name'] ?? '';
					$html = $link['html'] ?? '';
				?>
					<li class="footer-<?php echo htmlspecialchars( $categoryName ); ?>-<?php echo htmlspecialchars( $name ); ?>">
						<?php echo $html; ?>
					</li>
				<?php } ?>
			</ul>
		<?php
		}
		$footericons = $this->skin->getWhaleFooterIcons();
		if ( count( $footericons ) ) {
		?>
			<ul class="footer-icons">
				<?php
				foreach ( $footericons as $blockName => $footerIcons ) {
				?>
					<li class="footer-<?php echo htmlspecialchars( $blockName ); ?>ico">
						<?php
						foreach ( $footerIcons as $icon ) {
							echo $this->skin->makeWhaleFooterIcon( $icon );
						}
						?>
					</li>
				<?php
				}
				?>
				<li class="designedbylibre">
					<a href="//librewiki.net">
						<?php // @codingStandardsIgnoreLine 
						?>
						<img src="<?php echo $this->skin->getConfig()->get( 'StylePath' ); //phpcs:ignore 
									?>/Whale/img/designedbylibre.png" style="height:31px" alt="Designed by Librewiki">
					</a>
				</li>
			</ul>
		<?php
		}
	}

	/**
	 * Get Notification function, build notification menu.
	 */
	protected function getNotification() {
		$personalTools = $this->skin->getWhalePersonalTools();
		if (
			isset( $personalTools['notifications-alert'] ) &&
			$personalTools['notifications-alert']['links'][0]['data']['counter-num']
		) {
			echo $this->skin->makeWhaleListItem( 'notifications-alert', $personalTools['notifications-alert'] );
		}
		if (
			isset( $personalTools['notifications-notice'] ) &&
			$personalTools['notifications-notice']['links'][0]['data']['counter-num']
		) {
			echo $this->skin->makeWhaleListItem( 'notifications-notice', $personalTools['notifications-notice'] );
		}
	}

	/**
	 * Render Portal function, build top menu contents.
	 *
	 * @param array $contents Menu data that will made by parseNavbar function.
	 */
	protected function renderPortal( $contents ) {
		$skin = $this->skin;
		$user = $skin->getUser();
		$services = MediaWikiServices::getInstance();
		$userGroupManager = $services->getUserGroupManager();
		$userGroup = $userGroupManager->getUserGroups( $user );
		$userRights = $services->getPermissionManager()->getUserPermissions( $user );

		foreach ( $contents as $content ) {
			if ( !$content ) {
				break;
			}
			if (
				( $content['right'] && !in_array( $content['right'], $userRights ) ) ||
				( $content['group'] && !in_array( $content['group'], $userGroup ) )
			) {
				continue;
			}

			echo Html::openElement( 'li', [
				'class' => [ 'dropdown', 'nav-item' ]
			] );

			array_push( $content['classes'], 'nav-link' );

			if ( is_array( $content['children'] ) && count( $content['children'] ) > 1 ) {
				array_push( $content['classes'], 'dropdown-toggle', 'dropdown-toggle-fix' );
			}

			echo Html::openElement( 'a', [
				'class' => $content['classes'],
				'data-toggle' => is_array( $content['children'] ) &&
					count( $content['children'] ) > 1 ? 'dropdown' : '',
				'role' => 'button',
				'aria-haspopup' => 'true',
				'aria-expanded' => 'true',
				'title' => $content['title'],
				'href' => $content['href']
			] );

			if ( isset( $content['icon'] ) ) {
				echo $this->renderIcon( $content['icon'] );
			}

			if ( isset( $content['text'] ) && !empty( $content['text'] ) ) {
				echo Html::rawElement( 'span', [
					'class' => 'hide-title'
				], $content['text'] );
			}

			echo Html::closeElement( 'a' );

			if ( is_array( $content['children'] ) && count( $content['children'] ) ) {
				echo Html::openElement( 'div', [
					'class' => 'dropdown-menu',
					'role' => 'menu'
				] );

				foreach ( $content['children'] as $child ) {
					if (
						( $child['right'] && !in_array( $child['right'], $userRights ) ) ||
						( $child['group'] && !in_array( $child['group'], $userGroup ) )
					) {
						continue;
					}
					array_push( $child['classes'], 'dropdown-item' );

					if ( is_array( $child['children'] ) ) {
						array_push( $child['classes'], 'dropdown-toggle', 'dropdown-toggle-sub' );
					}

					echo Html::openElement( 'a', [
						'accesskey' => $child['access'],
						'class' => $child['classes'],
						'href' => $child['href'],
						'title' => $child['title']
					] );

					if ( isset( $child['icon'] ) ) {
						echo $this->renderIcon( $child['icon'] );
					}

					if ( isset( $child['text'] ) ) {
						echo $child['text'];
					}

					echo Html::closeElement( 'a' );

					if (
						is_array( $content['children'] ) &&
						count( $content['children'] ) > 2 &&
						!empty( $child['children'] )
					) {
						echo Html::openElement( 'div', [
							'class' => 'dropdown-menu dropdown-submenu',
							'role' => 'menu'
						] );

						foreach ( $child['children'] as $sub ) {
							if (
								( $sub['right'] && !in_array( $sub['right'], $userRights ) ) ||
								( $sub['group'] && !in_array( $sub['group'], $userGroup ) )
							) {
								continue;
							}
							array_push( $sub['classes'], 'dropdown-item' );

							echo Html::openElement( 'a', [
								'accesskey' => $sub['access'],
								'class' => $sub['classes'],
								'href' => $sub['href'],
								'title' => $sub['title']
							] );

							if ( isset( $sub['icon'] ) ) {
								echo $this->renderIcon( $sub['icon'] );
							}

							if ( isset( $sub['text'] ) ) {
								echo $sub['text'];
							}

							echo Html::closeElement( 'a' );
						}

						echo Html::closeElement( 'div' );
					}
				}

				echo Html::closeElement( 'div' );
			}

			echo Html::closeElement( 'li' );
		}
	}

	/**
	 * Parse [[MediaWiki:Whale-Navbar]].
	 *
	 * Its format is:
	 * * <icon name>|Name of the menu displayed to the user
	 * ** link target|Link title (can be the name of an interface message)
	 *
	 * @return array Menu data
	 */
	protected function parseNavbar() {
		global $wgArticlePath;

		$headings = [];
		$currentHeading = null;
		$skin = $this->skin;
		$user = $skin->getUser();
		$userLang = $skin->getLanguage()->getCode();
		$globalData = $this->getCachedContentText(
			Title::newFromText( 'Whale-Navbar', NS_MEDIAWIKI )
		);
		$globalLangData = $this->getCachedContentText(
			Title::newFromText( 'Whale-Navbar/' . $userLang, NS_MEDIAWIKI )
		);
		$userData = $user->isRegistered()
			? $this->getCachedContentText(
				Title::newFromText( $user->getName() . '/Whale-Navbar', NS_USER )
			)
			: '';
		if ( !empty( $userData ) ) {
			$data = $userData;
		} elseif ( !empty( $globalLangData ) ) {
			$data = $globalLangData;
		} else {
			$data = $globalData;
		}
		// Well, [[MediaWiki:Whale-Navbar]] *should* have some content, but
		// if it doesn't, bail out here so that we don't trigger E_NOTICEs
		// about undefined indexes later on
		if ( empty( $data ) ) {
			return $headings;
		}

		$lines = explode( "\n", $data );

		$types = [ 'icon', 'display', 'title', 'link', 'access', 'class' ];

		foreach ( $lines as $line ) {
			$line = rtrim( $line, "\r" );
			if ( $line === '' ) {
				continue;
			}
			if ( $line[0] !== '*' ) {
				// Line does not start with '*'
				continue;
			}
			if ( $line[1] !== '*' ) {
				// First level menu
				$data = [];
				$split = explode( '|', $line );
				$split[0] = substr( $split[0], 1 );
				foreach ( $split as $key => $value ) {
					$valueArr = explode( '=', trim( $value ) );
					if ( isset( $valueArr[1] ) ) {
						$newValue = implode( '=', array_slice( $valueArr, 1 ) );
						$data[$valueArr[0]] = $newValue;
					} else {
						$data[$types[$key]] = trim( $value );
					}
				}

				// Initialize item
				$icon = isset( $data['icon'] ) ? htmlentities( $data['icon'], ENT_QUOTES, 'UTF-8' ) : null;
				$group = isset( $data['group'] ) ? htmlentities( $data['group'], ENT_QUOTES, 'UTF-8' ) : null;
				$right = isset( $data['right'] ) ? htmlentities( $data['right'], ENT_QUOTES, 'UTF-8' ) : null;

				// Parse display
				$text = '';
				if ( isset( $data['display'] ) ) {
					$textObj = $skin->msg( $data['display'] );
					if ( $textObj->isDisabled() ) {
						$text = htmlentities( $data['display'], ENT_QUOTES, 'UTF-8' );
					} else {
						$text = $textObj->text();
					}
				}

				// Parse iitle
				$title = '';
				if ( isset( $data['title'] ) ) {
					$titleObj = $skin->msg( $data['title'] );
					if ( $titleObj->isDisabled() ) {
						$title = htmlentities( $data['title'], ENT_QUOTES, 'UTF-8' );
					} else {
						$title = $titleObj->text();
					}
				} else {
					$title = $text;
				}
				$split[0] = substr( $split[0], 1 );
				foreach ( $split as $key => $value ) {
					$valueArr = explode( '=', trim( $value ) );
					if ( isset( $valueArr[1] ) ) {
						$newValue = implode( '=', array_slice( $valueArr, 1 ) );
						$data[$valueArr[0]] = $newValue;
					} else {
						$data[$types[$key]] = trim( $value );
					}
				}

				// Parse Icon
				$icon = isset( $data['icon'] ) ? htmlentities( $data['icon'], ENT_QUOTES, 'UTF-8' ) : null;

				// Parse Group
				$group = isset( $data['group'] ) ? htmlentities( $data['group'], ENT_QUOTES, 'UTF-8' ) : null;

				// Parse Right
				$right = isset( $data['right'] ) ? htmlentities( $data['right'], ENT_QUOTES, 'UTF-8' ) : null;

				// support the usual [[MediaWiki:Sidebar]] syntax of
				// ** link target|<some MW: message name> and if the
				// thing on the right side of the pipe isn't the name of a MW:
				// message, then and _only_ then render it as-is
				if ( isset( $data['display'] ) ) {
					$textObj = $skin->msg( $data['display'] );
					if ( $textObj->isDisabled() ) {
						$text = htmlentities( $data['display'], ENT_QUOTES, 'UTF-8' );
					} else {
						$text = $textObj->text();
					}
				} else {
					$text = '';
				}

				// If icon and text both empty
				if ( ( !isset( $icon ) && !isset( $text ) ) || ( empty( $icon ) && empty( $text ) ) ) {
					continue;
				}

				// Title
				if ( isset( $data['title'] ) ) {
					$titleObj = $skin->msg( $data['title'] );
					if ( $titleObj->isDisabled() ) {
						$title = htmlentities( $data['title'], ENT_QUOTES, 'UTF-8' );
					} else {
						$title = $titleObj->text();
					}
				} else {
					if ( isset( $text ) ) {
						$title = $text;
					}
				}

				// Link href
				if ( isset( $data['link'] ) ) {
					// @todo CHECKME: Should this use wfUrlProtocols() or somesuch instead?
					if ( preg_match( '/^((?:(?:http(?:s)?)?:)?\/\/(?:.{4,}))$/i', $data['link'] ) ) {
						$href = htmlentities( $data['link'], ENT_QUOTES, 'UTF-8' );
					} else {
						$href = str_replace( '%3A', ':', urlencode( $data['link'] ) );
						$href = str_replace( '$1', $href, $wgArticlePath );
					}
				} else {
					$href = null;
				}

				if ( isset( $data['access'] ) ) {
					// Access
					$access = preg_match( '/^([0-9a-z]{1})$/i', $data['access'] ) ? $data['access'] : '';
				} else {
					$access = null;
				}

				if ( isset( $data['class'] ) ) {
					// Classes
					$classes = explode( ',', htmlentities( $data['class'], ENT_QUOTES, 'UTF-8' ) );
					foreach ( $classes as $key => $value ) {
						$classes[$key] = trim( $value );
					}
				} else {
					$classes = [];
				}
				// @codingStandardsIgnoreStart
				$item = [
					'access' => $access,
					'classes' => $classes,
					'href' => $href,
					'icon' => @$icon,
					'text' => @$text,
					'title' => $title,
					'group' => $group,
					'right' => $right
				];
				// @codingStandardsIgnoreEnd
				$level2Children = &$item['children'];
				$headings[] = $item;
				continue;
			}
			if ( $line[2] !== '*' ) {
				// Second level menu
				// Initialize item
				$icon = null;
				$text = null;
				$title = null;
				$href = null;
				$access = null;
				$classes = [];
				$group = null;
				$right = null;

				$data = [];
				$split = explode( '|', $line );
				$split[0] = substr( $split[0], 2 );
				foreach ( $split as $key => $value ) {
					$valueArr = explode( '=', trim( $value ) );
					if ( isset( $valueArr[1] ) ) {
						$data[$valueArr[0]] = $valueArr[1];
					} else {
						$data[$types[$key]] = trim( $value );
					}
				}

				// Icon
				$icon = isset( $data['icon'] ) ? htmlentities( $data['icon'], ENT_QUOTES, 'UTF-8' ) : null;

				// Group
				$group = isset( $data['group'] ) ? htmlentities( $data['group'], ENT_QUOTES, 'UTF-8' ) : null;

				// Right
				$right = isset( $data['right'] ) ? htmlentities( $data['right'], ENT_QUOTES, 'UTF-8' ) : null;

				// support the usual [[MediaWiki:Sidebar]] syntax of
				// ** link target|<some MW: message name> and if the
				// thing on the right side of the pipe isn't the name of a MW:
				// message, then and _only_ then render it as-is
				if ( isset( $data['display'] ) ) {
					$textObj = $skin->msg( $data['display'] );
					if ( $textObj->isDisabled() ) {
						$text = htmlentities( $data['display'], ENT_QUOTES, 'UTF-8' );
					} else {
						$text = $textObj->text();
					}
				} else {
					$text = '';
				}

				// If icon and text both empty
				if ( empty( $icon ) && empty( $text ) ) {
					continue;
				}

				// Title
				if ( isset( $data['title'] ) ) {
					$titleObj = $skin->msg( $data['title'] );
					if ( $titleObj->isDisabled() ) {
						$title = htmlentities( $data['title'], ENT_QUOTES, 'UTF-8' );
					} else {
						$title = $titleObj->text();
					}
				} else {
					$title = $text;
				}

				if ( isset( $data['link'] ) ) {
					// Link href
					// @todo CHECKME: Should this use wfUrlProtocols() or somesuch instead?
					if ( preg_match( '/^((?:(?:http(?:s)?)?:)?\/\/(?:.{4,}))$/i', $data['link'] ) ) {
						$href = htmlentities( $data['link'], ENT_QUOTES, 'UTF-8' );
					} else {
						$href = str_replace( '%3A', ':', urlencode( $data['link'] ) );
						$href = str_replace( '$1', $href, $wgArticlePath );
					}
				}

				if ( isset( $data['access'] ) ) {
					// Access
					$access = preg_match( '/^([0-9a-z]{1})$/i', $data['access'] ) ? $data['access'] : '';
				} else {
					$access = null;
				}

				if ( isset( $data['class'] ) ) {
					// Classes
					$classes = explode( ',', htmlentities( $data['class'], ENT_QUOTES, 'UTF-8' ) );
					foreach ( $classes as $key => $value ) {
						$classes[$key] = trim( $value );
					}
				} else {
					$classes = [];
				}

				$item = [
					'access' => $access,
					'classes' => $classes,
					'href' => $href,
					'icon' => $icon,
					'text' => $text,
					'title' => $title,
					'group' => $group,
					'right' => $right
				];
				$level3Children = &$item['children'];
				$level2Children[] = $item;
				continue;
			}
			if ( $line[3] !== '*' ) {
				// Third level menu
				// Initialize item
				$icon = null;
				$text = null;
				$title = null;
				$href = null;
				$access = null;
				$classes = [];
				$group = null;
				$right = null;

				$data = [];
				$split = explode( '|', $line );
				$split[0] = substr( $split[0], 3 );
				foreach ( $split as $key => $value ) {
					$valueArr = explode( '=', trim( $value ) );
					if ( isset( $valueArr[1] ) ) {
						$data[$valueArr[0]] = $valueArr[1];
					} else {
						$data[$types[$key]] = trim( $value );
					}
				}

				// Icon
				$icon = isset( $data['icon'] ) ? htmlentities( $data['icon'], ENT_QUOTES, 'UTF-8' ) : null;

				// Group
				$group = isset( $data['group'] ) ? htmlentities( $data['group'], ENT_QUOTES, 'UTF-8' ) : null;

				// Right
				$right = isset( $data['right'] ) ? htmlentities( $data['right'], ENT_QUOTES, 'UTF-8' ) : null;

				// support the usual [[MediaWiki:Sidebar]] syntax of
				// ** link target|<some MW: message name> and if the
				// thing on the right side of the pipe isn't the name of a MW:
				// message, then and _only_ then render it as-is
				if ( isset( $data['display'] ) ) {
					$textObj = $skin->msg( $data['display'] );
					if ( $textObj->isDisabled() ) {
						$text = htmlentities( $data['display'], ENT_QUOTES, 'UTF-8' );
					} else {
						$text = $textObj->text();
					}
				} else {
					$text = '';
				}

				// If icon and text both empty
				if ( empty( $icon ) && empty( $text ) ) {
					continue;
				}

				// Title
				if ( isset( $data['title'] ) ) {
					$titleObj = $skin->msg( $data['title'] );
					if ( $titleObj->isDisabled() ) {
						$title = htmlentities( $data['title'], ENT_QUOTES, 'UTF-8' );
					} else {
						$title = $titleObj->text();
					}
				} else {
					if ( isset( $text ) ) {
						$title = $text;
					} else {
						$title = '';
					}
				}

				// Link href
				// @todo CHECKME: Should this use wfUrlProtocols() or somesuch instead?
				if ( preg_match( '/^((?:(?:http(?:s)?)?:)?\/\/(?:.{4,}))$/i', $data['link'] ) ) {
					$href = htmlentities( $data['link'], ENT_QUOTES, 'UTF-8' );
				} else {
					$href = str_replace( '%3A', ':', urlencode( $data['link'] ) );
					$href = str_replace( '$1', $href, $wgArticlePath );
				}

				// Access
				if ( isset( $data['access'] ) ) {
					$access = preg_match( '/^([0-9a-z]{1})$/i', $data['access'] ) ? $data['access'] : '';
				} else {
					$access = null;
				}

				if ( isset( $data['class'] ) ) {
					// Classes
					$classes = explode( ',', htmlentities( $data['class'], ENT_QUOTES, 'UTF-8' ) );
					foreach ( $classes as $key => $value ) {
						$classes[$key] = trim( $value );
					}
				} else {
					$classes = [];
				}

				$item = [
					'access' => $access,
					'classes' => $classes,
					'href' => $href,
					'icon' => $icon,
					'text' => $text,
					'title' => $title,
					'group' => $group,
					'right' => $right
				];
				$level3Children[] = $item;
				continue;
			} else {
				// Not supported
				continue;
			}
		}

		return $headings;
	}

	private function renderIcon( ?string $icon ): string {
		if ( $icon === null || !preg_match( '/^[a-z0-9-]+$/i', $icon ) ) {
			return '';
		}

		$iconPaths = [
			'angle-down' => '<path d="m6 9 6 6 6-6"/>',
			'angle-up' => '<path d="m18 15-6-6-6 6"/>',
			'bell' => '<path d="M18 8a6 6 0 1 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/>',
			'book' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/>',
			'calendar' => '<path d="M8 2v4"/><path d="M16 2v4"/><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18"/>',
			'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
			'cog' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.1 2.1-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5V20h-3v-.2a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.9.3l-.1.1-2.1-2.1.1-.1A1.7 1.7 0 0 0 5 15a1.7 1.7 0 0 0-1.5-1H3v-3h.2A1.7 1.7 0 0 0 5 10a1.7 1.7 0 0 0-.3-1.9l-.1-.1 2.1-2.1.1.1A1.7 1.7 0 0 0 8.7 6a1.7 1.7 0 0 0 1-1.5V4h3v.2a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.9-.3l.1-.1 2.1 2.1-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.5 1H21v3h-.2a1.7 1.7 0 0 0-1.4 1z"/>',
			'comment' => '<path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/>',
			'comments' => '<path d="M21 14a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/><path d="M7 9h10"/><path d="M7 13h6"/>',
			'edit' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
			'envelope' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
			'external-link' => '<path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>',
			'eye' => '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
			'file' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h6"/>',
			'folder' => '<path d="M3 6a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
			'globe' => '<circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15 15 0 0 1 0 20"/><path d="M12 2a15 15 0 0 0 0 20"/>',
			'heart' => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 1 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"/>',
			'home' => '<path d="m3 11 9-8 9 8"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/>',
			'image' => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/>',
			'info' => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
			'list' => '<path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h13"/><path d="M3 6h.01"/><path d="M3 12h.01"/><path d="M3 18h.01"/>',
			'lock' => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
			'minus' => '<path d="M5 12h14"/>',
			'plus' => '<path d="M12 5v14"/><path d="M5 12h14"/>',
			'question' => '<circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 1 1 5.3 2c-.8.7-1.4 1.2-1.4 2.5"/><path d="M12 17h.01"/>',
			'random' => '<rect x="4" y="4" width="16" height="16" rx="2"/><path d="M8 8h.01"/><path d="M16 8h.01"/><path d="M12 12h.01"/><path d="M8 16h.01"/><path d="M16 16h.01"/>',
			'search' => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
			'share-square' => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 10.5 6.8-4"/><path d="m8.6 13.5 6.8 4"/>',
			'sign-in' => '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="m10 17 5-5-5-5"/><path d="M15 12H3"/>',
			'sign-out' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
			'star' => '<path d="m12 2 3 6 6.5.9-4.7 4.6 1.1 6.5L12 17l-5.9 3 1.1-6.5-4.7-4.6L9 8z"/>',
			'sync' => '<path d="M21 12a9 9 0 0 1-14.9 6.8"/><path d="M3 12A9 9 0 0 1 17.9 5.2"/><path d="M7 19H3v-4"/><path d="M17 5h4v4"/>',
			'tag' => '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8z"/><path d="M7.5 7.5h.01"/>',
			'tags' => '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8z"/><path d="M7.5 7.5h.01"/><path d="M17 7 21 11"/>',
			'upload' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M17 8 12 3 7 8"/><path d="M12 3v12"/>',
			'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
			'users' => '<path d="M16 21a6 6 0 0 0-12 0"/><circle cx="10" cy="8" r="4"/><path d="M22 21a5 5 0 0 0-4-4.9"/><path d="M17 4.3a4 4 0 0 1 0 7.4"/>',
			'wrench' => '<path d="M14.7 6.3a4 4 0 0 0-5 5L3 18l3 3 6.7-6.7a4 4 0 0 0 5-5L15 12l-3-3z"/>'
		];

		$iconKey = strtolower( $icon );
		if ( !isset( $iconPaths[$iconKey] ) ) {
			return '';
		}

		return Html::rawElement( 'svg', [
			'aria-hidden' => 'true',
			'class' => [ 'whale-icon', 'whale-icon-' . $iconKey ],
			'focusable' => 'false',
			'viewBox' => '0 0 24 24',
			'xmlns' => 'http://www.w3.org/2000/svg'
		], $iconPaths[$iconKey] );
	}

	/**
	 * Build an AdSense ad unit wrapped in a div tag.
	 *
	 * @param string $position Ad position
	 */
	protected function buildAd( $position ) {
		global $wgWhaleAdSetting;

		$adFormat = 'auto';
		$fullWidthResponsive = 'true';
		if ( $position === 'header' ) {
			$adFormat = 'horizontal';
			$fullWidthResponsive = 'false';
		}
		?>
		<div class="<?php echo $position; ?>-ads">
			<ins class="adsbygoogle" 
				data-full-width-responsive="<?php echo $fullWidthResponsive; ?>" 
				data-ad-client="<?php echo $wgWhaleAdSetting['client']; ?>" 
				data-ad-slot="<?php echo $wgWhaleAdSetting[$position]; ?>"
				data-ad-format="<?php echo $adFormat; ?>">
			</ins>
		</div>
<?php
	}

	/**
	 * Helper function for parseNavbar() to not trigger deprecation warnings on MW 1.37+ and to continue
	 * functioning on MW 1.43+.
	 *
	 * @param Content|null $content
	 * @return string|null Textual form of the content, if available.
	 */
	private function getContentText( ?Content $content = null ) {
		if ( $content === null ) {
			return '';
		}

		if ( $content instanceof TextContent ) {
			return $content->getText();
		}

		return null;
	}

	private function getCachedContentText( Title $title ): string {
		$services = MediaWikiServices::getInstance();
		$cache = $services->getMainWANObjectCache();
		$cacheKey = $cache->makeKey(
			'whale',
			'navbar-content',
			$title->getNamespace(),
			$title->getDBkey()
		);

		return $cache->getWithSetCallback(
			$cacheKey,
			6 * 60 * 60,
			function () use ( $title ) {
				return $this->getContentText( $this->getContentOfTitle( $title ) ) ?: '';
			}
		);
	}

	private function getContentOfTitle( Title $title ): ?Content {
		$page = null;

		$wikiPageFactory = MediaWikiServices::getInstance()->getWikiPageFactory();
		$page = $wikiPageFactory->newFromTitle( $title );

		return $page->getContent( RevisionRecord::RAW );
	}

	private function isProtectedTitle( Title $title ): bool {
		return MediaWikiServices::getInstance()->getRestrictionStore()->isProtected( $title );
	}
}
