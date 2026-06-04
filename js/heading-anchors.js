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

		content.querySelectorAll('h1, h2, h3, h4, h5, h6').forEach((heading) => {
			const id =
				heading.id ||
				heading.querySelector('.mw-headline[id]')?.getAttribute('id');

			if (!id || heading.querySelector('.whale-heading-anchor')) {
				return;
			}

			const button = document.createElement('button');
			button.type = 'button';
			button.className = 'whale-heading-anchor';
			button.setAttribute('aria-label', mw.msg('whale-heading-link-copy'));
			button.title = mw.msg('whale-heading-link-copy');
			button.textContent = '#';
			button.addEventListener('click', async () => {
				const url = `${location.origin}${location.pathname}${location.search}#${encodeURIComponent(id)}`;
				history.replaceState(null, '', `#${encodeURIComponent(id)}`);
				try {
					if (await whale.copyText(url)) {
						button.title = mw.msg('whale-heading-link-copied');
						showCopyAlert(mw.msg('whale-heading-link-copied'));
					}
				} catch (error) {
					console.error('Heading link copy failed: ', error);
				}
			});

			const label = heading.querySelector('.mw-headline') || heading;
			button.dataset.heading = getHeadingText(label);
			heading.append(button);
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
