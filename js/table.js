(() => {
	const getCellValue = (row, index) => {
		const cell = row.children[index];
		const value =
			cell?.getAttribute('data-sort-value') ||
			cell?.textContent?.trim().replace(/\s+/g, ' ') ||
			'';
		const number = Number(value.replace(/,/g, ''));

		return Number.isNaN(number) ? value.toLocaleLowerCase() : number;
	};

	const compareValues = (first, second) => {
		if (typeof first === 'number' && typeof second === 'number') {
			return first - second;
		}

		return String(first).localeCompare(String(second), undefined, {
			numeric: true,
			sensitivity: 'base',
		});
	};

	const sortTable = (table, header, index) => {
		const tbody = table.tBodies[0];

		if (!tbody) {
			return;
		}

		const direction =
			header.getAttribute('aria-sort') === 'ascending'
				? 'descending'
				: 'ascending';
		const headerRow = header.closest('tr');
		const rows = [...tbody.rows].filter((row) => row !== headerRow);

		rows
			.map((row, position) => ({
				position,
				row,
				value: getCellValue(row, index),
			}))
			.sort((first, second) => {
				const result = compareValues(first.value, second.value);
				const sorted = result || first.position - second.position;

				return direction === 'ascending' ? sorted : -sorted;
			})
			.forEach(({ row }) => {
				tbody.append(row);
			});

		table.querySelectorAll('th[aria-sort]').forEach((item) => {
			item.removeAttribute('aria-sort');
		});
		header.setAttribute('aria-sort', direction);
	};

	const tableSort = () => {
		const content = document.getElementById('mw-content-text');

		if (!content) {
			return;
		}

		const tables = [...content.querySelectorAll('table')].filter(
			(table) =>
				table.classList.contains('sortable') ||
				table.querySelector('th.headerSort'),
		);

		for (const table of tables) {
			if (table.dataset.whaleSortable === 'ready') {
				continue;
			}

			table.dataset.whaleSortable = 'ready';
			table.classList.add('whale-sortable');

			const headers = table.tHead
				? [...table.tHead.querySelectorAll('th')]
				: [...table.querySelectorAll('tr:first-child > th')];

			headers.forEach((header, index) => {
				if (header.classList.contains('unsortable')) {
					return;
				}

				header.classList.add('whale-sortable-header');
				header.tabIndex = 0;
				header.addEventListener('click', () => sortTable(table, header, index));
				header.addEventListener('keydown', (event) => {
					if (event.key === 'Enter' || event.key === ' ') {
						event.preventDefault();
						sortTable(table, header, index);
					}
				});
			});
		}
	};

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
	window.addEventListener('load', () => {
		tableSort();
		tablewrap();
	});
})();
