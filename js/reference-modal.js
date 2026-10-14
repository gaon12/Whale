(() => {
	const ID_REFERENCE_ATTRIBUTES = [
		'id',
		'for',
		'aria-controls',
		'aria-describedby',
		'aria-labelledby',
		'aria-owns',
	];
	const desktop = window.matchMedia('(min-width: 1024px)');
	let activeReferenceTarget = null;
	let activeTrigger = null;
	let hideTimer = null;
	let restoringFocus = false;

	const getReferenceTarget = (link) => {
		const href = link.getAttribute('href');
		if (!href) {
			return null;
		}
		try {
			const url = new URL(href, window.location.href);
			const current = new URL(window.location.href);
			if (
				!url.hash ||
				url.origin !== current.origin ||
				url.pathname !== current.pathname ||
				url.search !== current.search
			) {
				return null;
			}
			const target = whale.getAnchorTarget(url.hash);
			const source = target?.querySelector(
				'.reference-text, .mw-reference-text',
			);
			return target?.matches('li[id^="cite_note"]') &&
				target.closest('.references, .mw-references') &&
				source
				? { hash: url.hash, target, source }
				: null;
		} catch {
			return null;
		}
	};

	const getReferenceLink = (target) => {
		const link = whale.closest(target, 'a[href]');
		return link?.closest('.reference, .mw-ref') ? link : null;
	};

	const fillContent = (content, link, resolved) => {
		const clone = resolved.source.cloneNode(true);
		for (const node of [clone, ...clone.querySelectorAll('*')]) {
			for (const attribute of ID_REFERENCE_ATTRIBUTES) {
				node.removeAttribute(attribute);
			}
		}
		const number = (link.textContent || '')
			.trim()
			.replace(/^\[\s*|\s*\]$/g, '');
		const marker = document.createElement('a');
		marker.className = 'whale-reference-marker';
		marker.textContent = number ? `[${number}]` : '[*]';
		marker.setAttribute('href', resolved.hash);
		marker.setAttribute('data-whale-reference-jump', '');
		content.replaceChildren(marker, document.createTextNode(' '), clone);
		activeReferenceTarget = resolved.target;
		return number
			? mw.message('whale-reference-title-numbered', number).text()
			: mw.message('whale-reference-title').text();
	};

	const hidePopover = (restoreFocus = false) => {
		window.clearTimeout(hideTimer);
		const popover = document.getElementById('whale-reference-popover');
		const trigger = activeTrigger;
		const returnFocus =
			restoreFocus && popover?.contains(document.activeElement);
		if (popover) {
			popover.hidden = true;
		}
		trigger?.removeAttribute('aria-controls');
		trigger?.removeAttribute('aria-expanded');
		activeTrigger = null;
		if (returnFocus) {
			restoringFocus = true;
			trigger?.focus({ preventScroll: true });
			restoringFocus = false;
		}
	};

	const positionPopover = (popover, link) => {
		const anchor = link.getBoundingClientRect();
		const box = popover.getBoundingClientRect();
		const margin = 8;
		const topLimit = whale.getNavHeight() + margin;
		const above = anchor.top - box.height - 10 >= topLimit;
		const left = Math.max(
			margin,
			Math.min(
				anchor.left + anchor.width / 2 - box.width / 2,
				document.documentElement.clientWidth - box.width - margin,
			),
		);
		const top = Math.max(
			topLimit,
			Math.min(
				above ? anchor.top - box.height - 10 : anchor.bottom + 10,
				window.innerHeight - box.height - margin,
			),
		);
		popover.style.left = `${left}px`;
		popover.style.top = `${top}px`;
		popover.dataset.placement = above ? 'above' : 'below';
		popover.style.setProperty(
			'--whale-reference-arrow-left',
			`${Math.max(12, Math.min(anchor.left + anchor.width / 2 - left, box.width - 12))}px`,
		);
	};

	const showReference = (link) => {
		const resolved = getReferenceTarget(link);
		if (!resolved) {
			return false;
		}
		window.clearTimeout(hideTimer);
		if (desktop.matches) {
			const popover = document.getElementById('whale-reference-popover');
			const content = popover?.querySelector(
				'[data-whale-reference-popover-content]',
			);
			if (!content) {
				return false;
			}
			if (activeTrigger !== link) {
				hidePopover();
				popover.setAttribute(
					'aria-label',
					fillContent(content, link, resolved),
				);
			}
			activeTrigger = link;
			link.setAttribute('aria-controls', popover.id);
			link.setAttribute('aria-expanded', 'true');
			popover.hidden = false;
			positionPopover(popover, link);
			return true;
		}
		const modal = document.getElementById('whale-reference-modal');
		const title = modal?.querySelector('[data-whale-reference-title]');
		const content = modal?.querySelector('[data-whale-reference-content]');
		if (!title || !content) {
			return false;
		}
		title.textContent = fillContent(content, link, resolved);
		document.dispatchEvent(
			new CustomEvent('whale:openModal', { detail: { modal, trigger: link } }),
		);
		return true;
	};

	whale.ready(() => {
		const popover = document.getElementById('whale-reference-popover');
		document.addEventListener('pointerover', (event) => {
			if (!desktop.matches || event.pointerType === 'touch') {
				return;
			}
			if (popover?.contains(event.target)) {
				window.clearTimeout(hideTimer);
				return;
			}
			const link = getReferenceLink(event.target);
			if (link && !link.contains(event.relatedTarget)) {
				showReference(link);
			}
		});
		document.addEventListener('pointerout', (event) => {
			if (
				!activeTrigger ||
				(!activeTrigger.contains(event.target) &&
					!popover?.contains(event.target))
			) {
				return;
			}
			if (
				activeTrigger.contains(event.relatedTarget) ||
				popover?.contains(event.relatedTarget)
			) {
				return;
			}
			hideTimer = window.setTimeout(() => {
				if (
					document.activeElement !== activeTrigger &&
					!popover?.contains(document.activeElement)
				) {
					hidePopover();
				}
			}, 180);
		});
		document.addEventListener('focusin', (event) => {
			if (restoringFocus || !desktop.matches) {
				return;
			}
			const link = getReferenceLink(event.target);
			if (link) {
				showReference(link);
			} else if (!popover?.contains(event.target)) {
				hidePopover();
			}
		});
		document.addEventListener('click', (event) => {
			if (
				event.defaultPrevented ||
				(event.button !== undefined && event.button !== 0) ||
				event.metaKey ||
				event.ctrlKey ||
				event.shiftKey ||
				event.altKey
			) {
				return;
			}
			const jump = whale.closest(event.target, '[data-whale-reference-jump]');
			if (jump && activeReferenceTarget) {
				event.preventDefault();
				hidePopover();
				document.dispatchEvent(
					new CustomEvent('whale:closeModal', {
						detail: {
							modal: document.getElementById('whale-reference-modal'),
							restoreFocus: false,
						},
					}),
				);
				whale.scrollToTarget(activeReferenceTarget);
				return;
			}
			const link = getReferenceLink(event.target);
			if (link && showReference(link)) {
				event.preventDefault();
				if (desktop.matches) {
					popover?.focus({ preventScroll: true });
				}
			} else if (!popover?.contains(event.target)) {
				hidePopover();
			}
		});
		document.addEventListener('keydown', (event) => {
			if (event.key === 'Escape' && activeTrigger) {
				hidePopover(true);
			}
		});
		window.addEventListener('scroll', () => hidePopover(), { passive: true });
		window.addEventListener('resize', () => hidePopover());
		document.addEventListener(
			'whale:openModal',
			(event) => {
				if (activeTrigger && popover?.contains(event.detail?.trigger)) {
					event.detail.trigger = activeTrigger;
				}
				hidePopover();
			},
			{ capture: true },
		);
		whale.onMediaChange(desktop, () => {
			hidePopover();
			document.dispatchEvent(
				new CustomEvent('whale:closeModal', {
					detail: { modal: document.getElementById('whale-reference-modal') },
				}),
			);
		});
	});
})();
