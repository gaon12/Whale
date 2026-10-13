(() => {
	const COOKIE_NAME = 'whale-dark-mode';
	const STORAGE_KEY = 'whaleDarkMode';
	const VALID_MODES = new Set(['dark', 'light']);
	const storesUserPreference =
		document.body.dataset?.whaleThemeStorage === 'user';
	let selectedMode = document.body.dataset?.whaleThemeMode;
	let persistInFlight = false;

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
		if (VALID_MODES.has(selectedMode)) {
			return selectedMode;
		}

		return window.matchMedia?.('(prefers-color-scheme: dark)').matches
			? 'dark'
			: 'light';
	};

	const persistMode = async (mode) => {
		if (storesUserPreference) {
			const api = await whale.getApi();
			await api.postWithToken('csrf', {
				action: 'options',
				optionname: 'whale-dark',
				optionvalue: mode,
			});
			return;
		}
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
		document.documentElement.style.colorScheme = isDark ? 'dark' : 'light';
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
		if (!VALID_MODES.has(selectedMode) && selectedMode !== 'auto') {
			selectedMode =
				document.body.classList.contains('whale-dark') &&
				!document.body.classList.contains('whale-auto-dark')
					? 'dark'
					: 'auto';
		}
		if (!storesUserPreference) {
			selectedMode = getStoredMode() || selectedMode;
		}
		applyMode(getCurrentMode(), selectedMode !== 'auto');

		document.addEventListener('click', async (event) => {
			const button = whale.closest(event.target, '[data-whale-theme-toggle]');
			if (!button) {
				return;
			}

			event.preventDefault();
			if (persistInFlight) {
				return;
			}
			const previousMode = selectedMode;
			const nextMode = getCurrentMode() === 'dark' ? 'light' : 'dark';
			selectedMode = nextMode;
			applyMode(nextMode);
			persistInFlight = true;
			button.disabled = true;
			try {
				await persistMode(nextMode);
			} catch {
				selectedMode = previousMode;
				applyMode(getCurrentMode(), selectedMode !== 'auto');
				mw.notify?.(mw.message('whale-theme-save-failed').text(), {
					type: 'error',
				});
			} finally {
				persistInFlight = false;
				button.disabled = false;
			}
		});

		if (mediaQuery) {
			whale.onMediaChange(mediaQuery, () => {
				if (selectedMode === 'auto') {
					applyMode(getCurrentMode(), false);
				}
			});
		}
	});
})();
