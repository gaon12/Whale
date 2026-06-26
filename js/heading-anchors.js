(() => {
	const getHeadingText = (heading) =>
		heading.textContent?.trim().replace(/\s+/g, ' ') || '';
	let toastTimer = null;

	const showCopyAlert = (message) => {
		let alert = document.querySelector('.whale-heading-anchor-alert');
		if (!alert) {
			alert = document.createElement('div');
			alert.className = 'whale-heading-anchor-alert';
			alert.setAttribute('role', 'status');
			alert.setAttribute('aria-live', 'polite');
			document.body.append(alert);
		}

		alert.textContent = message;
		alert.hidden = false;
		window.clearTimeout(toastTimer);
		window.requestAnimationFrame(() => {
			alert.classList.add('is-visible');
		});
		toastTimer = window.setTimeout(() => {
			alert.classList.remove('is-visible');
			window.setTimeout(() => {
				alert.hidden = true;
			}, 220);
		}, 1800);
	};

	const initHeadingAnchors = (content) => {
		if (!document.body.classList.contains('whale-heading-anchors-enabled')) {
			return;
		}

		const bindHeadingAnchor = (button, id) => {
			if (button.dataset.whaleHeadingAnchorBound === '1') {
				return;
			}

			button.dataset.whaleHeadingAnchorBound = '1';
			button.addEventListener('click', async () => {
				const encodedId = encodeURIComponent(id);
				const url = `${location.origin}${location.pathname}${location.search}#${encodedId}`;
				history.replaceState(null, '', `#${encodedId}`);
				try {
					if (await whale.copyText(url)) {
						button.title = mw.msg('whale-heading-link-copied');
						showCopyAlert(mw.msg('whale-heading-link-copied'));
					}
				} catch (error) {
					console.error('Heading link copy failed: ', error);
				}
			});
		};

		content.querySelectorAll('h1, h2, h3, h4, h5, h6').forEach((heading) => {
			const id =
				heading.id ||
				heading.querySelector('.mw-headline[id]')?.getAttribute('id');

			if (!id) {
				return;
			}

			const label = heading.querySelector('.mw-headline') || heading;
			let button = heading.querySelector('.whale-heading-anchor');
			if (!button) {
				button = document.createElement('button');
				button.type = 'button';
				button.className = 'whale-heading-anchor';
				button.setAttribute('aria-label', mw.msg('whale-heading-link-copy'));
				button.title = mw.msg('whale-heading-link-copy');
				button.textContent = '#';
				button.dataset.heading = getHeadingText(label);
				heading.append(button);
			}

			if (!button.dataset.heading) {
				button.dataset.heading = getHeadingText(label);
			}
			bindHeadingAnchor(button, id);
		});
	};

	whale.ready(() => {
		const content = document.getElementById('mw-content-text');
		if (content) {
			initHeadingAnchors(content);
		}
		mw.hook('wikipage.content').add((updatedContent) => {
			initHeadingAnchors(updatedContent[0] || updatedContent);
		});
	});
})();
