(() => {
	const MOBILE_AD_QUERY = '(max-width: 1023px)';

	const moveRightAdToMobileSlot = () => {
		const mobileAds = document.querySelector('.mobile-ads');
		const rightAds = document.querySelector('.right-ads');

		if (
			!mobileAds ||
			!rightAds ||
			!window.matchMedia(MOBILE_AD_QUERY).matches
		) {
			return;
		}

		while (rightAds.firstChild) {
			mobileAds.append(rightAds.firstChild);
		}
		rightAds.remove();
	};

	const queueAds = () => {
		const ads = document.querySelectorAll(
			'ins.adsbygoogle:not([data-whale-ad-queued])',
		);

		if (ads.length < 1) {
			return;
		}

		window.adsbygoogle = window.adsbygoogle || [];
		ads.forEach((ad) => {
			ad.dataset.whaleAdQueued = 'true';
			window.adsbygoogle.push({});
		});
	};

	whale.ready(() => {
		moveRightAdToMobileSlot();
		queueAds();
	});
})();
