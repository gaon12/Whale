/* Make button to make fixed toc */
(() => {
	const createIndexButton = (contentHeader) => {
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

		return indexButton;
	};

	whale.ready(() => {
		const toc = document.getElementById('toc');
		const contentHeader = document.querySelector('.whale-content-header');

		if (!toc?.innerHTML || !contentHeader || window.innerWidth <= 1649) {
			return;
		}

		let fixedToc = null;
		const indexButton = createIndexButton(contentHeader);

		indexButton.addEventListener('click', () => {
			indexButton.style.display = 'none';

			if (fixedToc) {
				return;
			}

			fixedToc = toc.cloneNode(true);
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

			fixedToc
				.querySelector(':scope > .togglelink')
				?.addEventListener('click', (event) => {
					event.preventDefault();
					indexButton.style.display = '';
					fixedToc?.remove();
					fixedToc = null;
				});

			document.body.append(fixedToc);
		});

		document.body.append(indexButton);
	});
})();
