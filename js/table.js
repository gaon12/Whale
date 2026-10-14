(() => {
	const prepareEmptyCells = (content) => {
		// Special-page previews and dynamically parsed content skip PHP decoration.
		for (const cell of content.querySelectorAll('table td, table th')) {
			cell.classList.toggle(
				'whale-empty-cell',
				cell.childElementCount === 0 && cell.textContent.trim() === '',
			);
		}
	};

	const positionCollapsibleToggle = (table) => {
		if (!table.matches('table.mw-collapsible')) return;
		for (const toggle of table.querySelectorAll(
			'.mw-collapsible-toggle-default',
		)) {
			if (
				toggle.closest('table') !== table ||
				toggle.closest('.whale-table-toggle-bar')
			)
				continue;
			const heading = toggle.parentElement;
			if (!heading?.matches('th, td, caption')) continue;
			// Keep the original node and its core handlers in the title row.
			// Inserting another row would change rowspan and sorter mappings.
			const title = document.createElement('div');
			title.className = 'whale-table-folding-title';
			for (const child of [...heading.childNodes]) {
				if (child !== toggle) title.append(child);
			}
			const bar = document.createElement('div');
			bar.className = 'whale-table-toggle-bar';
			bar.append(toggle);
			heading.classList.add('whale-table-folding-heading');
			heading.classList.remove('whale-empty-cell');
			heading.append(title, bar);
		}
	};

	const syncSortState = (table) => {
		for (const header of table.querySelectorAll('th.headerSort')) {
			header.classList.add('whale-sortable-header');
			header.setAttribute(
				'aria-sort',
				header.classList.contains('headerSortUp')
					? 'ascending'
					: header.classList.contains('headerSortDown')
						? 'descending'
						: 'none',
			);
		}
	};

	const initSortableTables = async (content) => {
		if (!document.body.classList.contains('whale-sortable-tables-enabled'))
			return;
		const tables = [...content.querySelectorAll('table.sortable')];
		if (tables.length === 0) return;
		await mw.loader.using('jquery.tablesorter');
		for (const table of tables) {
			if (table.dataset.whaleSortable === 'ready') continue;
			// Keep the core sorter as the sole owner of span mapping and handlers.
			const coreTable = window.jQuery(table);
			if (!coreTable.data('tablesorter')) coreTable.tablesorter();
			coreTable.on('sortEnd.tablesorter.whale', () => syncSortState(table));
			table.dataset.whaleSortable = 'ready';
			table.classList.add('whale-sortable');
			syncSortState(table);
		}
	};

	const syncTableWrappers = (content) => {
		if (
			!content ||
			!document.body.classList.contains('whale-responsive-tables-enabled')
		) {
			return;
		}
		for (const table of content.querySelectorAll('table')) {
			const parent = table.parentElement;
			const isWrapped = parent?.classList.contains('whale-table-wrapper');
			const container = isWrapped ? parent.parentElement : parent;
			const availableWidth = container?.clientWidth || content.clientWidth;
			if (table.scrollWidth > availableWidth && !isWrapped) {
				const wrapper = document.createElement('div');
				wrapper.className = 'whale-table-wrapper';
				parent?.insertBefore(wrapper, table);
				wrapper.append(table);
			} else if (table.scrollWidth <= availableWidth && isWrapped) {
				parent.parentElement?.insertBefore(table, parent);
				parent.remove();
			}
		}
	};

	whale.ready(() => {
		const initialize = (content) => {
			if (!content) return;
			prepareEmptyCells(content);
			for (const table of content.querySelectorAll('table.mw-collapsible')) {
				positionCollapsibleToggle(table);
			}
			void initSortableTables(content).catch((error) => {
				console.error('Whale table initialization failed.', error);
			});
			syncTableWrappers(content);
		};
		const content = document.getElementById('mw-content-text');
		initialize(content);
		mw.hook('wikipage.collapsibleContent').add((tables) => {
			for (const table of tables) positionCollapsibleToggle(table);
		});
		window.addEventListener(
			'resize',
			whale.rafThrottle(() => syncTableWrappers(content)),
		);
		if (content && window.ResizeObserver) {
			new ResizeObserver(
				whale.rafThrottle(() => syncTableWrappers(content)),
			).observe(content);
		}
		mw.hook('wikipage.content').add((loadedContent) =>
			initialize(loadedContent[0]),
		);
	});
})();
