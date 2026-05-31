(() => {
	const getAnchorTarget = (href) => {
		if (!href?.startsWith('#')) {
			return null;
		}

		return href.includes('.')
			? document.getElementById(href.slice(1))
			: document.querySelector(href);
	};

	const scrollToTarget = (target, navHeight) => {
		if (!target) {
			return;
		}

		window.scrollTo({
			top: target.getBoundingClientRect().top + window.scrollY - navHeight - 10,
			behavior: 'smooth',
		});
	};

	const bindAnchorScroll = (selector, navHeight, callback) => {
		document.querySelectorAll(selector).forEach((link) => {
			link.addEventListener('click', (event) => {
				const target = getAnchorTarget(link.getAttribute('href'));

				if (target) {
					event.preventDefault();
					if (callback) {
						callback(target);
						return;
					}
					scrollToTarget(target, navHeight);
				}
			});
		});
	};

	window.addEventListener('load', () => {
		const navHeight =
			document.querySelector('.whale-nav-wrapper')?.offsetHeight || 0;
		const hashTarget = getAnchorTarget(window.location.hash);

		scrollToTarget(hashTarget, navHeight);

		bindAnchorScroll('.toc ul li > a', navHeight);
		bindAnchorScroll('.mw-cite-backlink > a', navHeight);
		bindAnchorScroll('.mw-cite-backlink > * > a', navHeight);
		bindAnchorScroll('.reference > a', navHeight);
		bindAnchorScroll('#preftoc li > a', navHeight, () => {
			window.scrollTo({ top: 0, behavior: 'smooth' });
		});

		document.querySelectorAll('.mw-headline-number').forEach((number) => {
			number.addEventListener('click', (event) => {
				event.preventDefault();
				scrollToTarget(document.getElementById('toctitle'), navHeight);
			});
		});
	});
})();
