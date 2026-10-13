(() => {
	const DESKTOP_QUERY = '(min-width: 1024px)';
	const MOBILE_EDGE_SWIPE_PX = 64;
	const MOBILE_SWIPE_DISTANCE_PX = 48;
	const MOBILE_SWIPE_MAX_VERTICAL_PX = 42;
	const SECTION_NUMBER_SELECTOR = '.whale-heading-number';
	// Keep the expanded desktop panel open briefly after the pointer leaves so
	// moving between the dot rail and the panel never flickers it closed.
	const DESKTOP_PANEL_CLOSE_DELAY_MS = 220;
	// How close to the right edge of the page the pointer must come for the
	// section panel to open. Proximity is watched at the document level, so the
	// TOC never sits over page content and never swallows clicks.
	const DESKTOP_EDGE_PROXIMITY_PX = 28;
	const HEADING_SELECTOR = 'h1, h2, h3, h4, h5, h6';
	let activeScrollUpdate = null;
	let edgePointerMove = null;
	let contentObserver = null;
	let mobileGestureStart = null;
	let mobileGesturesBound = false;

	const getTocLinks = () => [
		...document.querySelectorAll(
			'#toc li > a[href^="#"], .toc li > a[href^="#"]',
		),
	];

	const getContentHeadings = () => [
		...document.querySelectorAll(
			'.whale-content-main h1, .whale-content-main h2, .whale-content-main h3, .whale-content-main h4, .whale-content-main h5, .whale-content-main h6',
		),
	];

	const getTarget = (link) => whale.getAnchorTarget(link.getAttribute('href'));
	const getTargetHeading = (target) =>
		target?.matches?.(HEADING_SELECTOR)
			? target
			: target?.closest?.(HEADING_SELECTOR) || null;

	const getRawHeadingLevel = (heading) => {
		const headingLevel = Number(heading.tagName.match(/^H([1-6])$/)?.[1]);
		return Number.isInteger(headingLevel) ? headingLevel : 2;
	};

	const getHeadingLevel = (heading, baseLevel) =>
		Math.min(6, Math.max(1, getRawHeadingLevel(heading) - baseLevel + 1));
	const getHeadingBaseLevel = (headings = getContentHeadings()) => {
		const rawLevels = headings.map(getRawHeadingLevel);
		return rawLevels.length > 0 ? Math.min(...rawLevels) : 2;
	};

	const getHeadingHref = (heading) => {
		const id = heading.querySelector('.mw-headline')?.id || heading.id;
		return id ? `#${id}` : '#';
	};

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

	const isDesktop = () => window.matchMedia(DESKTOP_QUERY).matches;

	const isMobileFloatingTocEnabled = () =>
		document.body.classList.contains('whale-mobile-floating-toc-enabled');
	const isMobileTocOpen = () =>
		document.body.classList.contains('whale-floating-toc-open');

	const setMobileTocOpen = (open) => {
		const toc = document.querySelector('.whale-floating-toc');
		if (!open && toc?.contains(document.activeElement)) {
			const trigger = document.getElementById('whale-scrolltoc');
			if (trigger) {
				trigger.focus({ preventScroll: true });
			} else {
				document.activeElement?.blur?.();
			}
		}

		document.body.classList.toggle('whale-floating-toc-open', open);
		const trigger = document.getElementById('whale-scrolltoc');
		trigger?.setAttribute('aria-expanded', String(open));
		if (toc) {
			toc.inert = !open;
			toc.setAttribute('aria-hidden', String(!open));
		}
		const backdrop = document.querySelector('.whale-floating-toc-backdrop');
		if (backdrop) {
			backdrop.hidden = !open;
		}
		if (open) {
			toc?.querySelector('button, a')?.focus({ preventScroll: true });
		}
	};

	const getFloatingTocItemsFromLinks = () => {
		const baseLevel = getHeadingBaseLevel();

		return getTocLinks()
			.map((link) => {
				const target = getTarget(link);
				const heading = getTargetHeading(target);
				const number = whale.tocUtils.getTocNumber(link);

				return {
					link,
					number,
					target,
					level: heading
						? getHeadingLevel(heading, baseLevel)
						: whale.tocUtils.getTocLevel(link),
					text: whale.tocUtils.getLinkText(link, target),
				};
			})
			.filter((item) => item.target && item.text);
	};

	const getFloatingTocItemsFromHeadings = () => {
		const counters = [];
		const headings = getContentHeadings();
		const baseLevel = getHeadingBaseLevel(headings);

		return headings
			.map((heading) => {
				const level = getHeadingLevel(heading, baseLevel);
				counters[level - 1] = (counters[level - 1] || 0) + 1;
				counters.length = level;

				const number = whale.tocUtils.formatTocNumber(counters.join('.'));
				const label = whale.tocUtils.getTargetText(heading);

				return {
					link: null,
					number,
					target: heading,
					href: getHeadingHref(heading),
					level,
					text: [number, label].filter(Boolean).join(' '),
				};
			})
			.filter((item) => item.target && item.text);
	};

	const getFloatingTocItems = () => {
		const linkItems = getFloatingTocItemsFromLinks();
		return linkItems.length > 0 ? linkItems : getFloatingTocItemsFromHeadings();
	};

	const syncHeadingNumbers = (links) => {
		links.forEach(({ target, number }) => {
			if (!number || !target) {
				return;
			}

			const label = target.querySelector?.('.mw-headline') || target;
			if (!label || label.querySelector?.(SECTION_NUMBER_SELECTOR)) {
				return;
			}

			const numberNode = document.createElement('span');
			numberNode.className = SECTION_NUMBER_SELECTOR.slice(1);
			numberNode.setAttribute('aria-hidden', 'true');
			numberNode.textContent = `${number} `;
			label.insertBefore(numberNode, label.firstChild);
		});
	};

	const removeFloatingToc = () => {
		contentObserver?.disconnect();
		contentObserver = null;
		document.querySelector('.whale-floating-toc')?.remove();
		document.querySelector('.whale-floating-toc-backdrop')?.remove();
		document.body.classList.remove('whale-floating-toc-hover');
		document.body.classList.remove('whale-floating-toc-open');
		if (activeScrollUpdate) {
			window.removeEventListener('scroll', activeScrollUpdate);
			activeScrollUpdate = null;
		}
		if (edgePointerMove) {
			document.removeEventListener?.('pointermove', edgePointerMove);
			edgePointerMove = null;
		}
	};

	const bindMobileGestures = () => {
		if (mobileGesturesBound) {
			return;
		}

		mobileGesturesBound = true;
		const startGesture = (event, point) => {
			const toc = document.querySelector('.whale-floating-toc');
			if (!point || isDesktop() || !isMobileFloatingTocEnabled()) {
				mobileGestureStart = null;
				return;
			}

			const startsAtEdge =
				point.clientX >= window.innerWidth - MOBILE_EDGE_SWIPE_PX;
			const startsInToc = toc?.contains(event.target);
			mobileGestureStart =
				startsAtEdge || startsInToc
					? { x: point.clientX, y: point.clientY, inToc: startsInToc }
					: null;
		};

		const moveGesture = (point) => {
			if (!mobileGestureStart || !point) {
				return;
			}

			const deltaX = point.clientX - mobileGestureStart.x;
			const deltaY = Math.abs(point.clientY - mobileGestureStart.y);
			if (deltaY > MOBILE_SWIPE_MAX_VERTICAL_PX || deltaY > Math.abs(deltaX)) {
				return;
			}

			if (deltaX <= -MOBILE_SWIPE_DISTANCE_PX) {
				setMobileTocOpen(true);
				mobileGestureStart = null;
			} else if (
				mobileGestureStart.inToc &&
				deltaX >= MOBILE_SWIPE_DISTANCE_PX
			) {
				setMobileTocOpen(false);
				mobileGestureStart = null;
			}
		};

		document.addEventListener(
			'touchstart',
			(event) => {
				startGesture(event, event.touches?.[0]);
			},
			{ passive: true },
		);

		document.addEventListener(
			'touchmove',
			(event) => {
				moveGesture(event.touches?.[0]);
			},
			{ passive: true },
		);

		document.addEventListener(
			'pointerdown',
			(event) => {
				if (event.pointerType === 'mouse') {
					return;
				}

				startGesture(event, event);
			},
			{ passive: true },
		);

		document.addEventListener(
			'pointermove',
			(event) => {
				if (event.pointerType === 'mouse') {
					return;
				}

				moveGesture(event);
			},
			{ passive: true },
		);

		document.addEventListener('touchend', () => {
			mobileGestureStart = null;
		});
		document.addEventListener('pointerup', () => {
			mobileGestureStart = null;
		});
		document.addEventListener('pointercancel', () => {
			mobileGestureStart = null;
		});

		document.addEventListener('whale:toggleFloatingToc', () => {
			if (!isDesktop() && isMobileFloatingTocEnabled()) {
				setMobileTocOpen(!isMobileTocOpen());
			}
		});

		document.addEventListener('keydown', (event) => {
			if (event.key === 'Tab' && !isDesktop() && isMobileTocOpen()) {
				const controls = [
					...document
						.querySelector('.whale-floating-toc')
						.querySelectorAll('button, a'),
				];
				const edge = event.shiftKey
					? controls[0]
					: controls[controls.length - 1];
				if (document.activeElement === edge) {
					event.preventDefault();
					(event.shiftKey
						? controls[controls.length - 1]
						: controls[0]
					)?.focus();
				}
			}
			if (
				event.key === 'Escape' &&
				!isDesktop() &&
				isMobileFloatingTocEnabled() &&
				isMobileTocOpen()
			) {
				event.preventDefault();
				setMobileTocOpen(false);
			}
		});
	};

	const buildFloatingToc = () => {
		const desktop = isDesktop();
		const desktopEnabled = document.body.classList.contains(
			'whale-floating-toc-enabled',
		);
		const mobileEnabled = isMobileFloatingTocEnabled();
		const shouldRenderDesktop = desktop && desktopEnabled;
		const shouldRenderMobile = !desktop && mobileEnabled;

		bindMobileGestures();

		if (!desktopEnabled && !mobileEnabled) {
			removeFloatingToc();
			return;
		}

		if (document.querySelector('.mw-parser-output.whale-home')) {
			removeFloatingToc();
			return;
		}
		const links = getFloatingTocItems();
		syncHeadingNumbers(links);

		if ((!shouldRenderDesktop && !shouldRenderMobile) || links.length < 1) {
			removeFloatingToc();
			return;
		}

		removeFloatingToc();

		const toc = document.createElement('nav');
		const list = document.createElement('ol');
		const heading = document.createElement('div');
		heading.className = 'whale-floating-toc-header';
		heading.textContent = mw.message('whale-floating-toc').text();
		toc.id = 'whale-navigation-toc';
		toc.className = shouldRenderMobile
			? 'whale-floating-toc is-mobile'
			: 'whale-floating-toc';
		toc.setAttribute('aria-label', mw.message('whale-floating-toc').text());
		toc.inert = shouldRenderMobile;
		toc.setAttribute('aria-hidden', String(shouldRenderMobile));

		links.forEach(({ link, target, text, level, href }) => {
			const item = document.createElement('li');
			const anchor = document.createElement('a');
			item.className = `whale-floating-toc-level-${level}`;
			anchor.href = link?.getAttribute('href') || href;
			anchor.textContent = text;
			anchor.addEventListener('click', (event) => {
				event.preventDefault();
				setMobileTocOpen(false);
				whale.scrollToTarget(target);
			});
			item.addEventListener('click', (event) => {
				if (event.target === item && shouldRenderDesktop) {
					whale.scrollToTarget(target);
				}
			});
			item.append(anchor);
			list.append(item);
		});

		// Desktop: decorative dots at each section's real document position. The
		// ordered list above is the accessible navigation and becomes the panel
		// that lists every section when the pointer approaches the rail.
		const markers = [];
		if (shouldRenderDesktop) {
			const rail = document.createElement('div');
			rail.className = 'whale-floating-toc-rail';
			rail.setAttribute('aria-hidden', 'true');
			links.forEach(({ level }) => {
				const marker = document.createElement('span');
				marker.className = `whale-floating-toc-marker whale-floating-toc-level-${level}`;
				rail.append(marker);
				markers.push(marker);
			});
			list.className = 'whale-floating-toc-panel';
			toc.append(heading, rail, list);
		} else {
			toc.append(heading, list);
		}
		if (shouldRenderMobile) {
			const close = document.createElement('button');
			close.type = 'button';
			close.className = 'whale-floating-toc-close';
			close.textContent = mw.message('close').text();
			close.addEventListener('click', () => setMobileTocOpen(false));
			heading.append(close);
			const backdrop = document.createElement('button');
			backdrop.type = 'button';
			backdrop.className = 'whale-floating-toc-backdrop';
			backdrop.setAttribute(
				'aria-label',
				mw.message('whale-floating-toc').text(),
			);
			backdrop.hidden = true;
			backdrop.addEventListener('click', () => setMobileTocOpen(false));
			document.body.append(backdrop);
			document.body.append(toc);
		} else {
			document.body.append(toc);
		}
		document
			.getElementById('whale-scrolltoc')
			?.setAttribute('aria-controls', toc.id);

		const anchors = [...toc.querySelectorAll('a')];
		const items = [...toc.querySelectorAll('li')];
		let hoveredIndex = -1;
		let previousActive = -1;
		let closeTimer = null;

		// Scroll the panel (not the page) so an entry sits inside its viewport.
		const revealInPanel = (index, centre) => {
			const item = items[index];
			const panelHeight = list.clientHeight;
			if (!item || !panelHeight || typeof item.offsetTop !== 'number') {
				return;
			}
			const top = item.offsetTop;
			const bottom = top + item.offsetHeight;
			if (centre) {
				list.scrollTop = Math.max(
					0,
					top - (panelHeight - item.offsetHeight) / 2,
				);
			} else if (top < list.scrollTop) {
				list.scrollTop = top;
			} else if (bottom > list.scrollTop + panelHeight) {
				list.scrollTop = bottom - panelHeight;
			}
		};
		const clearHovered = () => {
			if (hoveredIndex >= 0) {
				items[hoveredIndex].classList.remove('is-hovered');
				markers[hoveredIndex]?.classList.remove('is-hovered');
			}
			hoveredIndex = -1;
		};
		const setExpanded = (expanded) => {
			window.clearTimeout(closeTimer);
			closeTimer = null;
			if (toc.classList.contains('is-expanded') === expanded) {
				return;
			}
			toc.classList.toggle('is-expanded', expanded);
			if (expanded) {
				revealInPanel(previousActive, true);
			} else {
				clearHovered();
			}
		};
		const scheduleCollapse = () => {
			window.clearTimeout(closeTimer);
			closeTimer = window.setTimeout(() => {
				if (!toc.contains(document.activeElement)) {
					setExpanded(false);
				}
			}, DESKTOP_PANEL_CLOSE_DELAY_MS);
		};
		const selectNearestMarker = (event) => {
			const frame = toc.getBoundingClientRect();
			const ratio = (event.clientY - frame.top) / Math.max(1, frame.height);
			let nearest = 0;
			let distance = Infinity;
			links.forEach((item, index) => {
				const nextDistance = Math.abs(item.positionRatio - ratio);
				if (nextDistance < distance) {
					nearest = index;
					distance = nextDistance;
				}
			});
			if (nearest !== hoveredIndex) {
				clearHovered();
				items[nearest].classList.add('is-hovered');
				markers[nearest]?.classList.add('is-hovered');
				hoveredIndex = nearest;
				revealInPanel(nearest, false);
			}
			return nearest;
		};
		const isInPanel = (event) => Boolean(event.target.closest?.('ol'));
		const isNearEdge = (event) => {
			const pageWidth = document.documentElement.clientWidth;
			return (
				typeof pageWidth === 'number' &&
				pageWidth > 0 &&
				event.clientX >= pageWidth - DESKTOP_EDGE_PROXIMITY_PX
			);
		};
		if (shouldRenderDesktop) {
			edgePointerMove = (event) => {
				if (event.pointerType === 'touch') {
					return;
				}
				const inToc = toc.contains(event.target);
				if (isNearEdge(event) || inToc) {
					setExpanded(true);
					if (!isInPanel(event)) {
						selectNearestMarker(event);
					}
				} else if (toc.classList.contains('is-expanded')) {
					scheduleCollapse();
				}
			};
			document.addEventListener('pointermove', edgePointerMove, {
				passive: true,
			});
			toc.addEventListener('focusin', () => setExpanded(true));
			toc.addEventListener('focusout', scheduleCollapse);
			toc.addEventListener('click', (event) => {
				if (!isInPanel(event)) {
					whale.scrollToTarget(links[selectNearestMarker(event)].target);
				}
			});
		}

		const updatePositions = () => {
			const documentHeight = Math.max(1, document.documentElement.scrollHeight);
			links.forEach((item, index) => {
				item.scrollY = getTargetScrollY(item.target);
				if (shouldRenderDesktop) {
					const top = item.target.getBoundingClientRect().top + window.scrollY;
					// Use actual document coordinates: never spread or truncate markers.
					item.positionRatio = Math.max(0, Math.min(1, top / documentHeight));
					markers[index].style.top = `${item.positionRatio * 100}%`;
				}
			});
		};

		const updateActive = whale.rafThrottle(() => {
			const scrollY = Math.min(getMaxScrollY(), window.scrollY);
			let activeIndex = 0;

			links.forEach(({ scrollY: targetScrollY }, index) => {
				if (targetScrollY <= scrollY + 1) {
					activeIndex = index;
				}
			});

			if (previousActive === activeIndex) {
				return;
			}
			if (previousActive >= 0) {
				anchors[previousActive].classList.remove('is-active');
				anchors[previousActive].removeAttribute?.('aria-current');
				items[previousActive].classList.remove('is-active');
				markers[previousActive]?.classList.remove('is-active');
			}
			anchors[activeIndex].classList.add('is-active');
			anchors[activeIndex].setAttribute('aria-current', 'location');
			items[activeIndex].classList.add('is-active');
			markers[activeIndex]?.classList.add('is-active');
			previousActive = activeIndex;
			if (toc.classList.contains('is-expanded') && hoveredIndex < 0) {
				revealInPanel(activeIndex, false);
			}
		});

		updatePositions();
		updateActive();
		if (typeof ResizeObserver === 'function') {
			contentObserver = new ResizeObserver(
				whale.rafThrottle(() => {
					updatePositions();
					updateActive();
				}),
			);
			const content = document.querySelector('.whale-content-main');
			if (content) {
				contentObserver.observe(content);
			}
		}
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
