(() => {
	const COOKIE_NAME = 'whale-dark-mode';
	const STORAGE_KEY = 'whaleDarkMode';
	const VALID_MODES = new Set(['dark', 'light']);

	const getStoredMode = () => {
		const cookieMode = mw.cookie.get(COOKIE_NAME);
		if (VALID_MODES.has(cookieMode)) {
			return cookieMode;
		}

		try {
			const storageMode = window.localStorage.getItem(STORAGE_KEY);
			return VALID_MODES.has(storageMode) ? storageMode : null;
		} catch {
			return null;
		}
	};

	const getCurrentMode = () => {
		const storedMode = getStoredMode();
		if (storedMode) {
			return storedMode;
		}

		return window.matchMedia?.('(prefers-color-scheme: dark)').matches
			? 'dark'
			: 'light';
	};

	const persistMode = (mode) => {
		mw.cookie.set(COOKIE_NAME, mode, {
			expires: 365 * 24 * 60 * 60,
			secure: location.protocol === 'https:',
			sameSite: 'Lax',
		});

		try {
			window.localStorage.setItem(STORAGE_KEY, mode);
		} catch {}
	};

	const updateBrowserThemeColor = () => {
		const whaleRoot = document.querySelector('.Whale');
		const themeColor = whaleRoot
			? getComputedStyle(whaleRoot)
					.getPropertyValue('--whale-main-color')
					.trim()
			: '';

		if (!themeColor) {
			return;
		}

		document
			.querySelectorAll(
				'meta[name="theme-color"], meta[name="msapplication-navbutton-color"]',
			)
			.forEach((meta) => {
				meta.setAttribute('content', themeColor);
			});
	};

	const applyMode = (mode, explicit = true) => {
		const isDark = mode === 'dark';
		document.body.classList.toggle('whale-dark', isDark);
		document.body.classList.toggle('whale-auto-dark', !explicit);
		updateBrowserThemeColor();
		document.querySelectorAll('[data-whale-theme-toggle]').forEach((button) => {
			const label = mw
				.message(
					isDark ? 'whale-theme-toggle-light' : 'whale-theme-toggle-dark',
				)
				.text();
			button.setAttribute('aria-pressed', String(isDark));
			button.setAttribute('aria-label', label);
			button.setAttribute('title', label);
			button
				.querySelector('[data-whale-theme-toggle-label]')
				?.replaceChildren(document.createTextNode(label));
			button.querySelectorAll('.whale-theme-toggle-icon').forEach((icon) => {
				icon.hidden = !(
					(isDark && icon.classList.contains('whale-theme-toggle-when-dark')) ||
					(!isDark && icon.classList.contains('whale-theme-toggle-when-light'))
				);
			});
		});
	};

	whale.ready(() => {
		const mediaQuery = window.matchMedia?.('(prefers-color-scheme: dark)');
		applyMode(getCurrentMode(), Boolean(getStoredMode()));

		document.addEventListener('click', (event) => {
			const button = whale.closest(event.target, '[data-whale-theme-toggle]');
			if (!button) {
				return;
			}

			event.preventDefault();
			const nextMode = getCurrentMode() === 'dark' ? 'light' : 'dark';
			persistMode(nextMode);
			applyMode(nextMode);
		});

		if (mediaQuery) {
			whale.onMediaChange(mediaQuery, () => {
				if (!getStoredMode()) {
					applyMode(getCurrentMode(), false);
				}
			});
		}
	});
})();
