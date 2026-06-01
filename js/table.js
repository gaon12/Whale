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
		const sortedRows = [...tbody.rows]
			.filter((row) => row !== headerRow)
			.map((row, position) => ({
				position,
				row,
				value: getCellValue(row, index),
			}))
			.sort((first, second) => {
				const result = compareValues(first.value, second.value);
				const sorted = result || first.position - second.position;

				return direction === 'ascending' ? sorted : -sorted;
			});
		const fragment = new DocumentFragment();

		for (const { row } of sortedRows) {
			fragment.append(row);
		}

		tbody.append(fragment);
		table.querySelectorAll('th[aria-sort]').forEach((item) => {
			item.removeAttribute('aria-sort');
		});
		header.setAttribute('aria-sort', direction);
	};

	const initSortableTables = (content) => {
		if (!document.body.classList.contains('whale-sortable-tables-enabled')) {
			return;
		}

		const tables = new Set(content.querySelectorAll('table.sortable'));

		content.querySelectorAll('th.headerSort').forEach((header) => {
			const table = header.closest('table');

			if (table) {
				tables.add(table);
			}
		});

		for (const table of tables) {
			if (table.dataset.whaleSortable === 'ready') {
				continue;
			}

			table.dataset.whaleSortable = 'ready';
			table.classList.add('whale-sortable');

			const headers = table.tHead
				? table.tHead.querySelectorAll('th')
				: table.querySelectorAll('tr:first-child > th');

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

	const syncTableWrappers = (content) => {
		if (!document.body.classList.contains('whale-responsive-tables-enabled')) {
			return;
		}

		const contentWidth = content.clientWidth;

		for (const table of content.querySelectorAll('table')) {
			const parent = table.parentElement;
			const isWrapped = parent?.classList.contains('whale-table-wrapper');

			if (table.clientWidth > contentWidth && !isWrapped) {
				const wrapper = document.createElement('div');
				wrapper.className = 'whale-table-wrapper';
				parent?.insertBefore(wrapper, table);
				wrapper.append(table);
				continue;
			}

			if (table.clientWidth < contentWidth && isWrapped) {
				parent.parentElement?.insertBefore(table, parent);
				parent.remove();
			}
		}
	};

	window.addEventListener('load', () => {
		const content = document.getElementById('mw-content-text');

		if (!content) {
			return;
		}

		const syncWrappers = whale.rafThrottle(() => syncTableWrappers(content));

		initSortableTables(content);
		syncWrappers();
		window.addEventListener('resize', syncWrappers);
	});
})();
