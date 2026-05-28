(() => {
	const tablewrap = () => {
		const content = document.getElementById('mw-content-text');

		if (!content) {
			return;
		}

		const contentWidth = content.clientWidth;
		const tables = [...content.querySelectorAll('table')];

		for (const table of tables) {
			const parent = table.parentElement;

			if (
				table.clientWidth > contentWidth &&
				parent?.className !== 'whale-table-wrapper'
			) {
				const wrapper = document.createElement('div');
				wrapper.className = 'whale-table-wrapper';
				parent?.insertBefore(wrapper, table);
				wrapper.append(table);
			} else if (
				table.clientWidth < contentWidth &&
				parent?.className === 'whale-table-wrapper'
			) {
				parent.parentElement?.insertBefore(table, parent);
				parent.remove();
			}
		}
	};

	window.addEventListener('resize', tablewrap);
	window.addEventListener('load', tablewrap);
})();
