(() => {
	const DESKTOP_QUERY = '(min-width: 1280px)';
	const MAX_ITEMS = 18;
	let activeScrollUpdate = null;

	const getTocLinks = () => [
		...document.querySelectorAll(
			'#toc li > a[href^="#"], .toc li > a[href^="#"]',
		),
	];

	const getLinkText = (link) => {
		const number = link.querySelector('.tocnumber')?.textContent?.trim() || '';
		const text = link.querySelector('.toctext')?.textContent?.trim() || '';
		return [number, text || link.textContent?.trim()]
			.filter(Boolean)
			.join('. ')
			.replace(/^\s*([0-9.]+)\.\s*\1\s*/, '$1. ');
	};

	const getTarget = (link) => whale.getAnchorTarget(link.getAttribute('href'));

	const getMaxScrollY = () =>
		Math.max(0, document.documentElement.scrollHeight - window.innerHeight);

	const getTargetScrollY = (target) => {
		const rawScrollY =
			target.getBoundingClientRect().top +
			window.scrollY -
			whale.getNavHeight() -
			10;

		return Math.min(getMaxScrollY(), Math.max(0, rawScrollY));
	};

	const getTargetPositionRatio = (target) => {
		const targetTop = target.getBoundingClientRect().top + window.scrollY;
		const documentHeight = Math.max(
			1,
			document.documentElement.scrollHeight - whale.getNavHeight(),
		);

		return Math.min(1, Math.max(0, targetTop / documentHeight));
	};

	const removeFloatingToc = () => {
		document.querySelector('.whale-floating-toc')?.remove();
		if (activeScrollUpdate) {
			window.removeEventListener('scroll', activeScrollUpdate);
			activeScrollUpdate = null;
		}
	};

	const buildFloatingToc = () => {
		if (
			!document.body.classList.contains('whale-floating-toc-enabled') ||
			!window.matchMedia(DESKTOP_QUERY).matches
		) {
			removeFloatingToc();
			return;
		}

		const links = getTocLinks()
			.map((link) => ({
				link,
				target: getTarget(link),
				text: getLinkText(link),
			}))
			.filter((item) => item.target && item.text)
			.slice(0, MAX_ITEMS);

		if (links.length < 2) {
			removeFloatingToc();
			return;
		}

		removeFloatingToc();

		const toc = document.createElement('nav');
		const list = document.createElement('ol');
		toc.className = 'whale-floating-toc';
		toc.setAttribute('aria-label', '문단 목차');

		links.forEach(({ link, target, text }) => {
			const item = document.createElement('li');
			const anchor = document.createElement('a');
			anchor.href = link.getAttribute('href');
			anchor.textContent = text;
			anchor.addEventListener('click', (event) => {
				event.preventDefault();
				whale.scrollToTarget(target);
			});
			item.append(anchor);
			list.append(item);
		});

		toc.append(list);
		document.body.append(toc);

		const anchors = [...toc.querySelectorAll('a')];
		const items = [...toc.querySelectorAll('li')];

		const updatePositions = () => {
			const maxScrollY = getMaxScrollY();

			links.forEach((item, index) => {
				item.scrollY = getTargetScrollY(item.target);

				const ratio =
					maxScrollY === 0 || links.length === 1
						? index / Math.max(1, links.length - 1)
						: getTargetPositionRatio(item.target);

				items[index].style.top = `${Math.min(1, Math.max(0, ratio)) * 100}%`;
			});
		};

		const updateActive = whale.rafThrottle(() => {
			updatePositions();

			const scrollY = Math.min(getMaxScrollY(), window.scrollY);
			let activeIndex = 0;

			links.forEach(({ scrollY: targetScrollY }, index) => {
				if (targetScrollY <= scrollY + 1) {
					activeIndex = index;
				}
			});

			anchors.forEach((anchor, index) => {
				anchor.classList.toggle('is-active', index === activeIndex);
			});
		});

		updateActive();
		activeScrollUpdate = updateActive;
		window.addEventListener('scroll', activeScrollUpdate, { passive: true });
	};

	whale.ready(() => {
		window.requestAnimationFrame(buildFloatingToc);
		window.addEventListener('load', buildFloatingToc, { once: true });
		mw.hook('wikipage.content').add(() => {
			window.requestAnimationFrame(buildFloatingToc);
		});
		window.addEventListener('resize', whale.rafThrottle(buildFloatingToc));
	});
})();
