(() => {
	const ready = (callback) => {
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', callback, { once: true });
			return;
		}
		callback();
	};

	ready(() => {
		document.querySelectorAll('.whale-notice').forEach((notice) => {
			notice.addEventListener('closed.bs.alert', () => {
				mw.cookie.set('disable-notice', true, {
					expires: 3600 * 24,
					secure: false,
				});
			});
		});
	});
})();
