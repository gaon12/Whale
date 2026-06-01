(() => {
	const getLazyTarget = (image) =>
		image.parentElement?.tagName === 'PICTURE' ? image.parentElement : image;

	const wrapImage = (image) => {
		const target = getLazyTarget(image);
		const parent = target.parentElement;

		if (!parent || parent.classList.contains('whale-lazy-image-shell')) {
			return parent;
		}

		const shell = document.createElement('span');
		shell.className = 'whale-lazy-image-shell is-loading';

		const width = image.getAttribute('width');
		const height = image.getAttribute('height');
		if (width && height) {
			shell.style.aspectRatio = `${width} / ${height}`;
			shell.style.width = `${width}px`;
		} else {
			shell.classList.add('whale-lazy-image-shell-fluid');
		}

		parent.insertBefore(shell, target);
		shell.append(target);
		return shell;
	};

	const markLoaded = (shell) => {
		shell?.classList.remove('is-loading');
		shell?.classList.add('is-loaded');
	};

	const prepareImage = (image) => {
		if (image.closest('.whale-lazy-image-shell')) {
			return;
		}

		if (!image.hasAttribute('loading')) {
			image.setAttribute('loading', 'lazy');
		}
		if (!image.hasAttribute('decoding')) {
			image.setAttribute('decoding', 'async');
		}

		if (image.complete && image.naturalWidth > 0) {
			image.classList.add('whale-lazy-image');
			return;
		}

		image.classList.add('whale-lazy-image');
		const shell = wrapImage(image);
		image.addEventListener('load', () => markLoaded(shell), { once: true });
		image.addEventListener('error', () => markLoaded(shell), { once: true });
	};

	const prepareImages = (root = document) => {
		root.querySelectorAll?.('#mw-content-text img, img').forEach((image) => {
			if (!image.closest('#mw-content-text')) {
				return;
			}

			prepareImage(image);
		});
	};

	whale.ready(() => {
		prepareImages();

		mw.hook('wikipage.content').add(($content) => {
			prepareImages($content?.[0] || document);
		});

		const content = document.getElementById('mw-content-text');
		if (!content || typeof MutationObserver !== 'function') {
			return;
		}

		const observer = new MutationObserver((mutations) => {
			mutations.forEach((mutation) => {
				mutation.addedNodes.forEach((node) => {
					if (node instanceof Element) {
						prepareImages(node);
					}
				});
			});
		});
		observer.observe(content, { childList: true, subtree: true });
	});
})();
