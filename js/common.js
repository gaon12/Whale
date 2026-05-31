(() => {
	if (window.whale?.ready) {
		return;
	}

	let apiPromise = null;

	const ready = (callback) => {
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', callback, { once: true });
			return;
		}

		callback();
	};

	const rafThrottle = (callback) => {
		let scheduled = false;

		return (...args) => {
			if (scheduled) {
				return;
			}

			scheduled = true;
			window.requestAnimationFrame(() => {
				scheduled = false;
				callback(...args);
			});
		};
	};

	const getAnchorTarget = (href) => {
		if (!href?.startsWith('#')) {
			return null;
		}

		const rawId = href.slice(1);
		let decodedId = rawId;

		try {
			decodedId = decodeURIComponent(rawId);
		} catch {
			decodedId = rawId;
		}

		const target =
			document.getElementById(decodedId) || document.getElementById(rawId);

		if (target) {
			return target;
		}

		try {
			return document.querySelector(href);
		} catch {
			return null;
		}
	};

	const getNavHeight = () =>
		document.querySelector('.whale-nav-wrapper')?.offsetHeight || 0;

	const scrollToTarget = (target, options = {}) => {
		if (!target) {
			return;
		}

		window.scrollTo({
			top:
				target.getBoundingClientRect().top +
				window.scrollY -
				(options.offset ?? getNavHeight()) -
				(options.gap ?? 10),
			behavior: options.behavior ?? 'smooth',
		});
	};

	const onMediaChange = (mediaQuery, callback) => {
		if (typeof mediaQuery.addEventListener === 'function') {
			mediaQuery.addEventListener('change', callback);
			return;
		}

		mediaQuery.addListener(callback);
	};

	const getApi = async () => {
		apiPromise ??= mw.loader.using('mediawiki.api').then(() => new mw.Api());
		return apiPromise;
	};

	const closest = (target, selector) =>
		target instanceof Element
			? target.closest(selector)
			: target?.parentElement?.closest(selector) || null;

	window.whale = {
		ready,
		rafThrottle,
		getAnchorTarget,
		getNavHeight,
		scrollToTarget,
		onMediaChange,
		getApi,
		closest,
	};
})();
