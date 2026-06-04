(() => {
	let pendingUrl = '';
	let decodedVisible = false;

	const getModal = () => document.getElementById('whale-external-link-modal');
	const getUrlBox = () =>
		getModal()?.querySelector('[data-whale-external-url]');
	const getContinueLink = () =>
		getModal()?.querySelector('[data-whale-external-continue]');
	const getToggle = () =>
		getModal()?.querySelector('[data-whale-external-toggle]');

	const decodeUrl = (url) => {
		try {
			return decodeURI(url);
		} catch {
			return url;
		}
	};

	const getMessage = (key) => mw.message(key).text();

	const setDisplayedUrl = () => {
		const urlBox = getUrlBox();
		const toggle = getToggle();
		if (!urlBox || !toggle) {
			return;
		}

		const decodedUrl = decodeUrl(pendingUrl);
		const canToggle = decodedUrl !== pendingUrl;

		urlBox.textContent = decodedVisible && canToggle ? decodedUrl : pendingUrl;
		toggle.hidden = !canToggle;
		toggle.textContent = getMessage(
			decodedVisible
				? 'whale-external-link-show-original'
				: 'whale-external-link-show-decoded',
		);
	};

	const isExternalLink = (link) => {
		if (!link || link.closest('#whale-external-link-modal')) {
			return false;
		}

		const href = link.getAttribute('href');
		if (!href || href.startsWith('#')) {
			return false;
		}

		try {
			const url = new URL(href, location.href);
			if (url.protocol !== 'http:' && url.protocol !== 'https:') {
				return false;
			}

			return url.origin !== location.origin;
		} catch {
			return false;
		}
	};

	const openWarning = (link) => {
		const modal = getModal();
		const continueLink = getContinueLink();
		if (!modal || !continueLink) {
			return false;
		}

		pendingUrl = new URL(link.getAttribute('href'), location.href).href;
		decodedVisible = false;

		continueLink.href = pendingUrl;
		continueLink.target = link.target || '_blank';
		setDisplayedUrl();
		document.dispatchEvent(
			new CustomEvent('whale:openModal', {
				detail: { modal, trigger: link },
			}),
		);
		return true;
	};

	whale.ready(() => {
		document.addEventListener('click', (event) => {
			const toggle = whale.closest(
				event.target,
				'[data-whale-external-toggle]',
			);
			if (toggle) {
				event.preventDefault();
				decodedVisible = !decodedVisible;
				setDisplayedUrl();
				return;
			}

			if (event.defaultPrevented || event.button !== 0) {
				return;
			}

			if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
				return;
			}

			const link = whale.closest(event.target, 'a[href]');
			if (!isExternalLink(link)) {
				return;
			}

			if (openWarning(link)) {
				event.preventDefault();
			}
		});
	});
})();
