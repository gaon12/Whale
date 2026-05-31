(() => {
	const ready = (callback) => {
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', callback, { once: true });
			return;
		}
		callback();
	};

	const getMenuOwner = (toggle) =>
		toggle.closest('.whale-dropdown, .whale-btn-group');

	const closeDropdown = (owner) => {
		if (!owner) {
			return;
		}

		owner.classList.remove('open', 'show');
		owner
			.querySelector('[data-whale-toggle="dropdown"]')
			?.setAttribute('aria-expanded', 'false');
		owner.querySelectorAll('.whale-dropdown-menu.show').forEach((menu) => {
			menu.classList.remove('show');
			menu.style.display = '';
		});
	};

	const closeAllDropdowns = (except) => {
		document
			.querySelectorAll(
				'.whale-dropdown.open, .whale-dropdown.show, .whale-btn-group.open, .whale-btn-group.show',
			)
			.forEach((owner) => {
				if (owner !== except) {
					closeDropdown(owner);
				}
			});
	};

	const toggleDropdown = (toggle) => {
		const owner = getMenuOwner(toggle);
		const menu = owner?.querySelector('.whale-dropdown-menu');

		if (!owner || !menu) {
			return;
		}

		const shouldOpen =
			!owner.classList.contains('open') && !owner.classList.contains('show');
		closeAllDropdowns(owner);
		owner.classList.toggle('open', shouldOpen);
		owner.classList.toggle('show', shouldOpen);
		menu.classList.toggle('show', shouldOpen);
		toggle.setAttribute('aria-expanded', String(shouldOpen));
	};

	const getModal = (trigger) => {
		const selector =
			trigger.getAttribute('data-whale-target') ||
			trigger.getAttribute('data-target') ||
			trigger.getAttribute('href');
		return selector?.startsWith('#') ? document.querySelector(selector) : null;
	};

	const initReadingProgress = () => {
		const skinRoot = document.querySelector('.Whale');

		if (!skinRoot) {
			return;
		}

		const progress = document.createElement('div');
		progress.className = 'whale-reading-progress';
		progress.setAttribute('role', 'progressbar');
		progress.setAttribute('aria-label', 'Reading progress');
		progress.setAttribute('aria-valuemin', '0');
		progress.setAttribute('aria-valuemax', '100');
		progress.setAttribute('aria-valuenow', '0');
		skinRoot.append(progress);

		let scheduled = false;
		const update = () => {
			const scrollTop =
				window.scrollY || document.documentElement.scrollTop || 0;
			const scrollableHeight = Math.max(
				0,
				document.documentElement.scrollHeight - window.innerHeight,
			);
			const ratio =
				scrollableHeight === 0
					? 1
					: Math.min(1, Math.max(0, scrollTop / scrollableHeight));
			const percent = Math.round(ratio * 100);

			progress.style.transform = `scaleX(${ratio})`;
			progress.setAttribute('aria-valuenow', String(percent));
			scheduled = false;
		};
		const scheduleUpdate = () => {
			if (scheduled) {
				return;
			}

			scheduled = true;
			window.requestAnimationFrame(update);
		};

		update();
		window.addEventListener('scroll', scheduleUpdate, { passive: true });
		window.addEventListener('resize', scheduleUpdate);
	};

	const MODAL_TRANSITION_MS = 300;

	const closeModal = (modal) => {
		if (!modal) {
			return;
		}

		modal.dataset.whaleModalState = 'closing';
		modal.classList.remove('is-open');
		modal.setAttribute('aria-hidden', 'true');
		document.querySelectorAll('.whale-modal-backdrop').forEach((backdrop) => {
			backdrop.classList.remove('is-open');
		});

		window.setTimeout(() => {
			if (modal.dataset.whaleModalState !== 'closing') {
				return;
			}

			modal.style.display = 'none';
			delete modal.dataset.whaleModalState;
			document.body.classList.remove('whale-modal-open');
			document.querySelectorAll('.whale-modal-backdrop').forEach((backdrop) => {
				backdrop.remove();
			});
		}, MODAL_TRANSITION_MS);
	};

	const openModal = (modal) => {
		if (!modal) {
			return;
		}

		if (
			modal.dataset.whaleModalState === 'opening' ||
			modal.dataset.whaleModalState === 'open'
		) {
			return;
		}

		document.querySelectorAll('.whale-modal-backdrop').forEach((backdrop) => {
			backdrop.remove();
		});

		modal.dataset.whaleModalState = 'opening';
		modal.style.display = 'block';
		modal.removeAttribute('aria-hidden');
		document.body.classList.add('whale-modal-open');

		const backdrop = document.createElement('div');
		backdrop.className = 'whale-modal-backdrop';
		backdrop.addEventListener('click', () => closeModal(modal));
		document.body.append(backdrop);

		window.requestAnimationFrame(() => {
			if (modal.dataset.whaleModalState !== 'opening') {
				return;
			}

			modal.classList.add('is-open');
			backdrop.classList.add('is-open');
			modal.dataset.whaleModalState = 'open';
			document.getElementById('wpName1')?.focus();
		});
	};

	ready(() => {
		initReadingProgress();

		document.addEventListener('click', (event) => {
			const dropdownToggle = event.target.closest(
				'[data-whale-toggle="dropdown"]',
			);
			if (dropdownToggle) {
				event.preventDefault();
				event.stopPropagation();
				toggleDropdown(dropdownToggle);
				return;
			}

			const submenuToggle = event.target.closest('.whale-dropdown-toggle-sub');
			if (submenuToggle) {
				event.preventDefault();
				event.stopPropagation();
				const submenu = submenuToggle.nextElementSibling;
				if (submenu?.classList.contains('whale-dropdown-menu')) {
					submenu.classList.toggle('show');
					submenu.style.display = submenu.classList.contains('show')
						? 'block'
						: '';
				}
				return;
			}

			const modalTrigger = event.target.closest('[data-whale-toggle="modal"]');
			if (modalTrigger) {
				event.preventDefault();
				openModal(getModal(modalTrigger));
				return;
			}

			if (event.target.classList?.contains('whale-modal')) {
				event.preventDefault();
				closeModal(event.target);
				return;
			}

			const dismiss = event.target.closest('[data-whale-dismiss]');
			if (dismiss?.getAttribute('data-whale-dismiss') === 'modal') {
				event.preventDefault();
				closeModal(dismiss.closest('.whale-modal'));
				return;
			}

			const noticeDismiss = event.target.closest(
				'[data-whale-dismiss="notice"]',
			);
			if (noticeDismiss) {
				event.preventDefault();
				const notice = noticeDismiss.closest('.whale-notice');
				notice?.dispatchEvent(new Event('whale.notice.closed'));
				notice?.remove();
				return;
			}

			if (!event.target.closest('.whale-dropdown, .whale-btn-group')) {
				closeAllDropdowns();
				document
					.querySelectorAll('.whale-dropdown-submenu.show')
					.forEach((submenu) => {
						submenu.classList.remove('show');
						submenu.style.display = '';
					});
			}
		});

		document.addEventListener('keydown', (event) => {
			if (event.key === 'Escape') {
				closeAllDropdowns();
				closeModal(document.querySelector('.whale-modal.is-open'));
			}
		});
	});
})();
