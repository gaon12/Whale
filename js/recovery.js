(() => {
	const RECOVERY_CHECKS = [3000, 6500];
	const recoveryState = document.documentElement.dataset;
	const requiredModules = (
		document.currentScript?.dataset.whaleModules || 'skins.whale.layoutjs'
	)
		.split(',')
		.filter(Boolean);
	recoveryState.whaleResourceLoaderRecoveryScript = 'loaded';
	// A loading or partially initialized runtime still owns its module registry.
	const hasRuntime = () => Boolean(window.mw || window.mediaWiki);

	const isResourceLoaderScript = (script) => {
		if (script.dataset.whaleRecovery === 'true') {
			return false;
		}
		const type = script.getAttribute('type') || '';
		if (
			/^(?:|module|text\/javascript|application\/javascript)$/i.test(type) ||
			!/(?:javascript|rocketscript)/i.test(type)
		) {
			return false;
		}

		if (script.src) {
			return (
				/\/load\.php\?/.test(script.src) && /[?&]only=scripts/.test(script.src)
			);
		}

		const source = script.text || script.textContent || '';
		return /(?:RLCONF|RLSTATE|RLPAGEMODULES|RLQ|mw\.config\.set)=?/.test(
			source,
		);
	};

	const evaluateSource = (source) => {
		const replacement = document.createElement('script');
		replacement.text = source;
		document.head.append(replacement);
		replacement.remove();
	};

	const readScript = async (script) => {
		const source = script.src
			? await fetch(script.src, { credentials: 'same-origin' }).then(
					(response) => {
						if (!response.ok) {
							throw new Error('ResourceLoader recovery request failed.');
						}
						return response.text();
					},
				)
			: script.text || script.textContent || '';
		return source;
	};

	const recoverResourceLoader = async () => {
		if (hasRuntime() || recoveryState.whaleResourceLoaderRecovery) {
			return;
		}

		const scripts = [...document.querySelectorAll('script')].filter(
			isResourceLoaderScript,
		);
		if (scripts.length === 0) {
			return;
		}

		recoveryState.whaleResourceLoaderRecovery = 'attempted';
		const snapshots = scripts.map((script) => ({
			script,
			source: script.text || script.textContent || '',
			src: script.src,
		}));

		// Detach every Rocket Loader placeholder before awaiting a fetch. Leaving
		// it mounted lets Cloudflare execute the original while the replacement
		// is downloading, which implements every ResourceLoader module twice.
		scripts.forEach((script) => {
			script.remove();
		});
		let ownedRuntime = null;
		for (const snapshot of snapshots) {
			const source = await readScript({
				src: snapshot.src,
				text: snapshot.source,
			});
			// Another startup may finish while a recovery fetch is pending.
			if (hasRuntime() && window.mw !== ownedRuntime) {
				recoveryState.whaleResourceLoaderRecovery = 'runtime-started';
				return;
			}
			evaluateSource(source);
			if (window.mw) {
				ownedRuntime = window.mw;
			}
		}
		if (!window.mw?.loader) {
			throw new Error('ResourceLoader startup did not initialize.');
		}
		await window.mw.loader.using(requiredModules);
		recoveryState.whaleResourceLoaderRecovery = 'complete';
	};
	const recordRecoveryError = (error) => {
		recoveryState.whaleResourceLoaderRecovery = 'failed';
		recoveryState.whaleResourceLoaderRecoveryError =
			error instanceof Error ? error.name : 'UnknownError';
		recoveryState.whaleResourceLoaderRecoveryMessage =
			error instanceof Error ? error.message.slice(0, 160) : 'Unknown error';
		console.error('Whale ResourceLoader recovery failed.', error);
	};

	RECOVERY_CHECKS.forEach((delay) => {
		window.setTimeout(() => {
			recoverResourceLoader().catch(recordRecoveryError);
		}, delay);
	});
})();
