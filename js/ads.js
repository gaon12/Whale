(() => {
	const ready = (callback) => {
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', callback, { once: true });
			return;
		}
		callback();
	};

	ready(() => {
		const mobileAds = document.querySelector('.mobile-ads');
		const rightAds = document.querySelector('.right-ads');

		if (mobileAds && rightAds && window.innerWidth < 1024) {
			mobileAds.innerHTML = rightAds.innerHTML;
			rightAds.remove();
		}

		document.querySelectorAll('.adsbygoogle').forEach(() => {
			window.adsbygoogle = window.adsbygoogle || [];
			window.adsbygoogle.push({});
		});
	});
})();
