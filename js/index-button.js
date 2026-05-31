/* Make button to make fixed toc */
(() => {
	const ready = (callback) => {
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', callback, { once: true });
			return;
		}
		callback();
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

		return document.getElementById(decodedId) || document.getElementById(rawId);
	};

	const getNavHeight = () =>
		document.querySelector('.nav-wrapper')?.offsetHeight || 0;

	const scrollToTarget = (target) => {
		if (!target) {
			return;
		}

		window.scrollTo({
			top:
				target.getBoundingClientRect().top +
				window.scrollY -
				getNavHeight() -
				10,
			behavior: 'smooth',
		});
	};

	const bindSmoothTocLinks = (tocRoot) => {
		tocRoot.querySelectorAll('ul li > a[href^="#"]').forEach((link) => {
			link.addEventListener('click', (event) => {
				const target = getAnchorTarget(link.getAttribute('href'));

				if (target) {
					event.preventDefault();
					scrollToTarget(target);
				}
			});
		});
	};

	ready(() => {
		const toc = document.getElementById('toc');

		if (!toc?.innerHTML) {
			return;
		}

		bindSmoothTocLinks(toc);

		const contentHeader = document.querySelector('.whale-content-header');

		if (!contentHeader || window.innerWidth <= 1649) {
			return;
		}

		const contentHeaderOffset = contentHeader.getBoundingClientRect();
		const indexButton = document.createElement('button');
		indexButton.id = 'fixed-toc-button';
		indexButton.type = 'button';
		indexButton.className = 'whale-btn whale-btn-primary';
		indexButton.innerHTML =
			'<svg class="whale-icon whale-icon-list" aria-hidden="true" focusable="false" viewBox="0 0 24 24"><path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h13"/><path d="M3 6h.01"/><path d="M3 12h.01"/><path d="M3 18h.01"/></svg>';
		indexButton.style.position = 'fixed';
		indexButton.style.top = `${contentHeaderOffset.top + window.scrollY}px`;
		indexButton.style.left = `${contentHeaderOffset.left + window.scrollX - 62}px`;

		window.damezuma = { doc: null };

		indexButton.addEventListener('click', () => {
			indexButton.style.display = 'none';
			if (window.damezuma.doc) {
				return;
			}

			const fixedToc = toc.cloneNode(true);
			fixedToc.id = 'fixed-toc';
			Object.assign(fixedToc.style, {
				position: 'fixed',
				top: '44px',
				left: '0',
				backgroundColor: '#f5f8fa',
				borderRight: '1px solid #e1e8ed',
				color: '#373a3c',
				padding: '16px',
				bottom: '0',
				overflowY: 'auto',
				maxWidth: '200px',
				zIndex: '3000',
			});

			window.damezuma.doc = fixedToc;
			document.body.append(fixedToc);

			fixedToc
				.querySelector(':scope > .togglelink')
				?.addEventListener('click', (event) => {
					event.preventDefault();
					indexButton.style.display = '';
					fixedToc.remove();
					window.damezuma.doc = null;
				});

			bindSmoothTocLinks(fixedToc);
		});

		document.body.append(indexButton);
	});
})();
