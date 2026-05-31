// eslint-disable-next-line no-unused-vars
function LoginManage() {
	return mw.loader
		.using('mediawiki.api')
		.then(() => {
			const api = new mw.Api();
			return api
				.post({
					action: 'query',
					meta: 'tokens',
					type: 'login',
				})
				.then((result) =>
					api.post({
						action: 'clientlogin',
						loginreturnurl: location.href,
						username: document.getElementById('wpName1')?.value || '',
						password: document.getElementById('wpPassword1')?.value || '',
						rememberMe: document.getElementById('lgremember')?.checked ? 1 : 0,
						logintoken: result.query.tokens.logintoken,
					}),
				)
				.then((result) => {
					const alert = document.getElementById('modal-login-alert');

					if (result.clientlogin.status !== 'PASS') {
						if (result.clientlogin.status === 'FAIL' && alert) {
							alert.classList.add('whale-alert-warning');
							alert.classList.remove('whale-alert-hidden');
							alert.style.display = 'block';
							alert.textContent = result.clientlogin.message;
						}
						return false;
					}

					if (mw.config.get('wgNamespaceNumber') === -1) {
						location.href = mw.config.get('wgArticlePath').replace('$1', '');
					} else {
						window.location.reload();
					}

					return true;
				})
				.catch(() => false);
		})
		.catch(() => false);
}

(() => {
	const ready = (callback) => {
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', callback, { once: true });
			return;
		}
		callback();
	};

	ready(() => {
		const form = document.getElementById('modal-loginform');

		form?.addEventListener('keypress', (event) => {
			if (event.key === 'Enter') {
				event.preventDefault();
				LoginManage();
			}
		});

		form?.addEventListener('submit', (event) => {
			event.preventDefault();
			LoginManage();
		});
	});
})();
