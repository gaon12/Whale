(() => {
	const ready = (callback) => {
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', callback, { once: true });
			return;
		}
		callback();
	};

	ready(() => {
		document.querySelectorAll('.tools-share').forEach((button) => {
			button.addEventListener('click', () => {
				let host = mw.config.get('wgServer');
				const namespace = mw.config.get('wgNamespaceNumber');
				let title = mw.config.get('wgTitle');

				if (host.startsWith('//')) {
					host = location.protocol + host;
				}

				if (namespace) {
					title = `${mw.config.get('wgFormattedNamespaces')[namespace]}:${title}`;
				}

				navigator
					.share({
						title,
						text: `${title} - ${mw.config.get('wgSiteName')}`,
						url: `${host}${mw.config.get('wgScriptPath')}/index.php?curid=${mw.config.get('wgArticleId')}`,
						hashtags: [mw.config.get('wgSiteName').replace(/ /g, '_')],
					})
					.catch((error) => {
						console.error('Share API error: ', error);
					});
			});
		});
	});
})();
