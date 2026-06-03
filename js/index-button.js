(() => {
	const DESKTOP_QUERY = '(min-width: 1280px)';
	const MAX_ITEMS = 18;
	let activeScrollUpdate = null;

	const getTocLinks = () => [
		...document.querySelectorAll(
			'#toc li > a[href^="#"], .toc li > a[href^="#"]',
		),
	];

	const getTocLevel = (link) => {
		const item = link.closest('li');
		const levelClass = [...(item?.classList || [])].find((className) =>
			/^toclevel-\d+$/.test(className),
		);
		const level = Number(levelClass?.replace('toclevel-', ''));

		return Number.isInteger(level) && level > 0 ? Math.min(level, 6) : 1;
	};

	const getTargetText = (target) => {
		if (!target) {
			return '';
		}

		const label = target.querySelector?.('.mw-headline') || target;
		const clone = label.cloneNode(true);
		clone
			.querySelectorAll?.('.whale-heading-anchor, .mw-editsection')
			.forEach((node) => {
				node.remove();
			});

		return clone.textContent?.trim().replace(/\s+/g, ' ') || '';
	};

	const getLinkText = (link, target) => {
		const targetText = getTargetText(target);
		if (targetText) {
			return targetText;
		}

		const text = link.querySelector('.toctext')?.textContent?.trim();
		if (text) {
			return text.replace(/\s+/g, ' ');
		}

		const clone = link.cloneNode(true);
		clone.querySelectorAll?.('.tocnumber').forEach((node) => {
			node.remove();
		});

		return clone.textContent?.trim().replace(/\s+/g, ' ') || '';
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
		document.body.classList.remove('whale-floating-toc-hover');
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
			.map((link) => {
				const target = getTarget(link);

				return {
					link,
					target,
					level: getTocLevel(link),
					text: getLinkText(link, target),
				};
			})
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
		toc.setAttribute('aria-label', mw.message('whale-floating-toc').text());
		toc.addEventListener('pointerover', (event) => {
			if (event.target.closest('a')) {
				document.body.classList.add('whale-floating-toc-hover');
			}
		});
		toc.addEventListener('pointerout', (event) => {
			if (!event.relatedTarget || !toc.contains(event.relatedTarget)) {
				document.body.classList.remove('whale-floating-toc-hover');
			}
		});
		toc.addEventListener('focusin', () => {
			document.body.classList.add('whale-floating-toc-hover');
		});
		toc.addEventListener('focusout', (event) => {
			if (!event.relatedTarget || !toc.contains(event.relatedTarget)) {
				document.body.classList.remove('whale-floating-toc-hover');
			}
		});

		links.forEach(({ link, target, text, level }) => {
			const item = document.createElement('li');
			const anchor = document.createElement('a');
			item.className = `whale-floating-toc-level-${level}`;
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
			const minGap = Math.min(0.12, 1 / Math.max(1, links.length - 1));
			let previousRatio = -minGap;

			links.forEach((item, index) => {
				item.scrollY = getTargetScrollY(item.target);

				const ratio =
					maxScrollY === 0 || links.length === 1
						? index / Math.max(1, links.length - 1)
						: getTargetPositionRatio(item.target);
				const remainingItems = links.length - index - 1;
				const maxRatio = 1 - remainingItems * minGap;
				const safeRatio = Math.min(
					maxRatio,
					Math.max(previousRatio + minGap, ratio),
				);

				previousRatio = safeRatio;
				items[index].style.top =
					`${Math.min(1, Math.max(0, safeRatio)) * 100}%`;
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
