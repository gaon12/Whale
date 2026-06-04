(() => {
	const setButtonState = (button, label) => {
		const original = button.dataset.originalLabel || button.textContent;
		button.dataset.originalLabel = original;
		button.textContent = label;
		window.setTimeout(() => {
			button.textContent = original;
		}, 1600);
	};

	whale.ready(() => {
		document.addEventListener('click', async (event) => {
			const button = whale.closest(event.target, '.whale-short-url-copy');
			if (!button) {
				return;
			}

			event.preventDefault();
			const input = document.querySelector('.whale-short-url-value');
			if (!input) {
				return;
			}

			input.select();
			try {
				if (await whale.copyText(input.value)) {
					setButtonState(button, mw.msg('whale-short-url-copied'));
				}
			} catch (error) {
				console.error('Short URL copy failed: ', error);
			}
		});
	});
})();
