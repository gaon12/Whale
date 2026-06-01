(() => {
	const getHeadingText = (heading) =>
		heading.textContent?.trim().replace(/\s+/g, ' ') || '';

	const copyText = async (text) => {
		if (navigator.clipboard?.writeText) {
			await navigator.clipboard.writeText(text);
			return true;
		}

		return false;
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
					await copyText(url);
					button.title = mw.msg('whale-heading-link-copied');
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
