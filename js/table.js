(() => {
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
			void initSortableTables(content).catch((error) => {
				console.error('Whale table initialization failed.', error);
			});
			syncTableWrappers(content);
		};
		const content = document.getElementById('mw-content-text');
		initialize(content);
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
