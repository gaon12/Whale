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
			notice.addEventListener('whale.notice.closed', () => {
				mw.cookie.set('disable-notice', true, {
					expires: 3600 * 24,
					secure: false,
				});
			});
		});
	});
})();
