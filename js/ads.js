(() => {
	whale.ready(() => {
		const mobileAds = document.querySelector('.mobile-ads');
		const rightAds = document.querySelector('.right-ads');
		const ads = document.querySelectorAll('.adsbygoogle');

		if (mobileAds && rightAds && window.innerWidth < 1024) {
			while (rightAds.firstChild) {
				mobileAds.append(rightAds.firstChild);
			}
			rightAds.remove();
		}

		if (ads.length > 0) {
			window.adsbygoogle = window.adsbygoogle || [];
		}

		ads.forEach(() => {
			window.adsbygoogle.push({});
		});
	});
})();
