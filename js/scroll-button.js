(() => {
	const ready = (callback) => {
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', callback, { once: true });
			return;
		}
		callback();
	};

	ready(() => {
		document
			.getElementById('whale-scrollup')
			?.addEventListener('click', (event) => {
				event.preventDefault();
				window.scrollTo({ top: 0, behavior: 'smooth' });
			});

		document
			.getElementById('whale-scrolldown')
			?.addEventListener('click', (event) => {
				event.preventDefault();
				window.scrollTo({
					top: document.documentElement.scrollHeight,
					behavior: 'smooth',
				});
			});
	});
})();
