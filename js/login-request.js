(() => {
	let loginInFlight = null;

	const getInputValue = (id) => document.getElementById(id)?.value || '';

	const showLoginError = (message) => {
		const alert = document.getElementById('whale-login-alert');

		if (!alert) {
			return;
		}

		alert.classList.add('whale-alert-warning');
		alert.classList.remove('whale-alert-hidden');
		alert.style.display = 'block';
		alert.textContent = message;
	};

	const redirectAfterLogin = () => {
		if (mw.config.get('wgNamespaceNumber') === -1) {
			location.href = mw.config.get('wgArticlePath').replace('$1', '');
			return;
		}

		window.location.reload();
	};

	const login = async () => {
		if (loginInFlight) {
			return loginInFlight;
		}

		loginInFlight = (async () => {
			try {
				const api = await whale.getApi();
				const tokenResult = await api.post({
					action: 'query',
					meta: 'tokens',
					type: 'login',
				});
				const result = await api.post({
					action: 'clientlogin',
					loginreturnurl: location.href,
					username: getInputValue('wpName1'),
					password: getInputValue('wpPassword1'),
					rememberMe: document.getElementById('wpRemember')?.checked ? 1 : 0,
					logintoken: tokenResult.query.tokens.logintoken,
				});

				if (result.clientlogin.status !== 'PASS') {
					if (result.clientlogin.status === 'FAIL') {
						showLoginError(result.clientlogin.message);
					}
					return false;
				}

				redirectAfterLogin();
				return true;
			} catch {
				return false;
			} finally {
				loginInFlight = null;
			}
		})();

		return loginInFlight;
	};

	window.LoginManage = login;

	whale.ready(() => {
		const form = document.getElementById('whale-login-form');

		form?.addEventListener('submit', (event) => {
			event.preventDefault();
			login();
		});
	});
})();
