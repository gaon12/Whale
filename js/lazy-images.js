(() => {
	const MIN_SKELETON_SIZE = 32;

	const canShowSkeleton = (image) => {
		const width = Number.parseInt(image.getAttribute('width') || '', 10);
		const height = Number.parseInt(image.getAttribute('height') || '', 10);
		const box = image.getBoundingClientRect();

		return (
			(width >= MIN_SKELETON_SIZE && height >= MIN_SKELETON_SIZE) ||
			(box.width >= MIN_SKELETON_SIZE && box.height >= MIN_SKELETON_SIZE)
		);
	};

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
		if (!canShowSkeleton(image)) {
			return;
		}

		const shell = wrapImage(image);
		image.addEventListener('load', () => markLoaded(shell), { once: true });
		image.addEventListener('error', () => markLoaded(shell), { once: true });
	};

	whale.ready(() => {
		document.querySelectorAll('#mw-content-text img').forEach((image) => {
			prepareImage(image);
		});
	});
})();
