(() => {
	const setButtonState = (button, label) => {
		const original = button.dataset.originalLabel || button.textContent;
		button.dataset.originalLabel = original;
		button.textContent = label;
		window.setTimeout(() => {
			button.textContent = original;
		}, 1600);
	};

	const getShareData = () => {
		let host = mw.config.get('wgServer');
		const namespace = mw.config.get('wgNamespaceNumber');
		let title = mw.config.get('wgTitle');
		const siteName = mw.config.get('wgSiteName');

		if (host.startsWith('//')) {
			host = location.protocol + host;
		}

		if (namespace) {
			title = `${mw.config.get('wgFormattedNamespaces')[namespace]}:${title}`;
		}

		return {
			title,
			text: `${title} - ${siteName}`,
			url: `${host}${mw.config.get('wgScriptPath')}/index.php?curid=${mw.config.get('wgArticleId')}`,
			hashtags: [siteName.replace(/ /g, '_')],
		};
	};

	whale.ready(() => {
		document.addEventListener('click', async (event) => {
			const button = whale.closest(event.target, '.tools-share');
			if (!button) {
				return;
			}

			event.preventDefault();
			const shareData = getShareData();

			if (navigator.share) {
				navigator.share(shareData).catch((error) => {
					console.error('Share API error: ', error);
				});
				return;
			}

			try {
				if (await whale.copyText(shareData.url)) {
					setButtonState(button, mw.msg('whale-share-copied'));
				}
			} catch (error) {
				console.error('Clipboard API error: ', error);
			}
		});
	});
})();
