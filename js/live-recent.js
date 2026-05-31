(() => {
	const AUTO_REFRESH_INTERVAL = 5 * 60 * 1000;

	const timeFormat = (time) => {
		const aDayAgo = new Date();
		aDayAgo.setDate(aDayAgo.getDate() - 1);

		if (time < aDayAgo) {
			return `${time.getFullYear()}/${time.getMonth() + 1}/${time.getDate()}`;
		}

		return [time.getHours(), time.getMinutes(), time.getSeconds()]
			.map((value) => String(value).padStart(2, '0'))
			.join(':');
	};

	const createPlaceholderRow = (isLoading) => {
		const listItem = document.createElement('li');
		const placeholder = document.createElement('span');

		listItem.className = 'live-recent-row live-recent-empty';
		placeholder.className = isLoading
			? 'recent-item recent-item-placeholder is-loading'
			: 'recent-item recent-item-placeholder';
		placeholder.innerHTML = '&nbsp;';
		listItem.append(placeholder);

		return listItem;
	};

	const createChangeRow = (item) => {
		const time = new Date(item.timestamp);
		const listItem = document.createElement('li');
		const link = document.createElement('a');
		const title =
			item.title.length > 13 ? `${item.title.slice(0, 13)}...` : item.title;

		link.className = 'recent-item';
		link.href = mw.util.getUrl(item.title);
		link.title = item.title;
		link.append(`[${timeFormat(time)}] `);

		if (item.type === 'new') {
			const badge = document.createElement('span');
			badge.className = 'new';
			badge.textContent = `${mw.message('whale-feed-new').text()} `;
			link.append(badge);
		}

		link.append(title);
		listItem.append(link);

		return listItem;
	};

	const fillRows = (list, limit, rowFactory) => {
		const fragment = new DocumentFragment();

		for (let index = 0; index < limit; index++) {
			fragment.append(rowFactory(index));
		}

		list.replaceChildren(fragment);
	};

	whale.ready(() => {
		const liveRecent = document.querySelector('.live-recent');
		const list = document.getElementById('live-recent-list');
		const tabs = [
			{
				element: document.getElementById('whale-recent-tab1'),
				namespaces: liveRecent?.dataset.articleNs,
			},
			{
				element: document.getElementById('whale-recent-tab2'),
				namespaces: liveRecent?.dataset.talkNs,
			},
		];

		if (!liveRecent || !list || tabs.some((tab) => !tab.element)) {
			return;
		}

		const sidebarMedia = window.matchMedia('(min-width: 1024px)');
		const limit = list.childElementCount;
		let activeTabIndex = 0;
		let refreshInterval = null;
		let requestId = 0;
		let hasLoaded = false;

		const isLiveRecentVisible = () =>
			sidebarMedia.matches && list.offsetParent !== null;

		const showSkeletonRows = () => {
			list.setAttribute('aria-busy', 'true');
			fillRows(list, limit, () => createPlaceholderRow(true));
		};

		const showEmptyRows = () => {
			list.setAttribute('aria-busy', 'false');
			fillRows(list, limit, () => createPlaceholderRow(false));
		};

		const refreshLiveRecent = async ({ showLoading = !hasLoaded } = {}) => {
			if (!isLiveRecentVisible()) {
				return;
			}

			const currentRequestId = ++requestId;

			if (showLoading) {
				showSkeletonRows();
			}

			try {
				const api = await whale.getApi();
				const data = await api.get({
					action: 'query',
					list: 'recentchanges',
					rcprop: 'title|timestamp',
					rcshow: '!bot|!redirect',
					rctype: 'edit|new',
					rclimit: limit,
					format: 'json',
					rcnamespace: tabs[activeTabIndex].namespaces,
					rctoponly: true,
				});

				if (currentRequestId !== requestId) {
					return;
				}

				const changes = (data.query?.recentchanges ?? []).slice(0, limit);

				fillRows(list, limit, (index) =>
					changes[index]
						? createChangeRow(changes[index])
						: createPlaceholderRow(false),
				);
				list.setAttribute('aria-busy', 'false');
				hasLoaded = true;
			} catch {
				if (currentRequestId === requestId) {
					showEmptyRows();
				}
			}
		};

		const setActiveTab = (nextIndex) => {
			if (activeTabIndex === nextIndex) {
				return;
			}

			tabs[activeTabIndex].element.classList.remove('is-active');
			tabs[nextIndex].element.classList.add('is-active');
			activeTabIndex = nextIndex;
			hasLoaded = false;
			refreshLiveRecent();
		};

		tabs.forEach((tab, index) => {
			tab.element.addEventListener('click', () => setActiveTab(index));
		});

		const stopAutoRefresh = () => {
			if (refreshInterval === null) {
				return;
			}

			window.clearInterval(refreshInterval);
			refreshInterval = null;
		};

		const startAutoRefresh = () => {
			if (!isLiveRecentVisible()) {
				stopAutoRefresh();
				return;
			}

			if (refreshInterval === null) {
				refreshInterval = window.setInterval(
					() => refreshLiveRecent({ showLoading: false }),
					AUTO_REFRESH_INTERVAL,
				);
			}

			refreshLiveRecent();
		};

		const syncAutoRefresh = () => {
			if (isLiveRecentVisible()) {
				startAutoRefresh();
				return;
			}

			stopAutoRefresh();
		};

		whale.onMediaChange(sidebarMedia, syncAutoRefresh);
		syncAutoRefresh();
	});
})();
