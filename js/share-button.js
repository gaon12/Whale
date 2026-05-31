(() => {
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
		document.addEventListener('click', (event) => {
			if (!whale.closest(event.target, '.tools-share')) {
				return;
			}

			const shareData = getShareData();

			if (navigator.share) {
				navigator.share(shareData).catch((error) => {
					console.error('Share API error: ', error);
				});
				return;
			}

			navigator.clipboard?.writeText(shareData.url).catch((error) => {
				console.error('Clipboard API error: ', error);
			});
		});
	});
})();
