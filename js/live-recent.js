(() => {
	const AUTO_REFRESH_INTERVAL = 5 * 60 * 1000;
	const RELATIVE_TIME_INTERVAL = 30 * 1000;
	const feedCache = new Map();
	const relativeTimeFormatter = new Intl.RelativeTimeFormat(
		mw.config.get('wgUserLanguage') || document.documentElement.lang || 'ko',
		{ numeric: 'always' },
	);

	const formatRelativeTime = (timestamp) => {
		const seconds = Math.max(0, Math.round((Date.now() - timestamp) / 1000));

		if (seconds < 60) {
			return relativeTimeFormatter.format(-seconds, 'second');
		}

		const minutes = Math.round(seconds / 60);
		if (minutes < 60) {
			return relativeTimeFormatter.format(-minutes, 'minute');
		}

		const hours = Math.round(minutes / 60);
		if (hours < 24) {
			return relativeTimeFormatter.format(-hours, 'hour');
		}

		const days = Math.round(hours / 24);
		if (days < 7) {
			return relativeTimeFormatter.format(-days, 'day');
		}

		const date = new Date(timestamp);
		return `${date.getFullYear()}/${date.getMonth() + 1}/${date.getDate()}`;
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
		const timestamp = new Date(item.timestamp).getTime();
		const listItem = document.createElement('li');
		const link = document.createElement('a');
		const title = document.createElement('span');
		const time = document.createElement('time');

		listItem.className = 'live-recent-row';
		link.className = 'recent-item';
		link.href = mw.util.getUrl(item.title);
		link.title = item.title;
		title.className = 'recent-item-title';
		time.className = 'recent-item-time';
		time.dateTime = item.timestamp;
		time.dataset.timestamp = String(timestamp);
		time.textContent = formatRelativeTime(timestamp);

		if (item.type === 'new') {
			const badge = document.createElement('span');
			badge.className = 'new';
			badge.textContent = `${mw.message('whale-feed-new').text()} `;
			title.append(badge);
		}

		title.append(item.title);
		link.append(title, time);
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

	const renderFeed = (feed, changes) => {
		fillRows(feed.list, feed.limit, (index) =>
			changes[index]
				? createChangeRow(changes[index])
				: createPlaceholderRow(false),
		);
		feed.list.setAttribute('aria-busy', 'false');
		feed.loaded = true;
	};

	const showSkeletonRows = (feed) => {
		feed.list.setAttribute('aria-busy', 'true');
		fillRows(feed.list, feed.limit, () => createPlaceholderRow(true));
	};

	const showEmptyRows = (feed) => {
		feed.list.setAttribute('aria-busy', 'false');
		fillRows(feed.list, feed.limit, () => createPlaceholderRow(false));
	};

	const refreshFeed = async (
		feed,
		{ force = false, showLoading = false } = {},
	) => {
		const cached = feedCache.get(feed.namespaces);

		if (
			cached &&
			!force &&
			Date.now() - cached.fetchedAt < AUTO_REFRESH_INTERVAL
		) {
			renderFeed(feed, cached.changes);
			return;
		}

		const currentRequestId = ++feed.requestId;

		if (showLoading && !feed.loaded) {
			showSkeletonRows(feed);
		}

		try {
			const api = await whale.getApi();
			const data = await api.get({
				action: 'query',
				list: 'recentchanges',
				rcprop: 'title|timestamp',
				rcshow: '!bot|!redirect',
				rctype: 'edit|new',
				rclimit: feed.limit,
				format: 'json',
				rcnamespace: feed.namespaces,
				rctoponly: true,
			});

			if (currentRequestId !== feed.requestId) {
				return;
			}

			const changes = (data.query?.recentchanges ?? []).slice(0, feed.limit);
			feedCache.set(feed.namespaces, {
				changes,
				fetchedAt: Date.now(),
			});
			renderFeed(feed, changes);
		} catch {
			if (currentRequestId === feed.requestId) {
				showEmptyRows(feed);
			}
		}
	};

	const updateRelativeTimes = (root) => {
		root
			.querySelectorAll('.recent-item-time[data-timestamp]')
			.forEach((time) => {
				time.textContent = formatRelativeTime(Number(time.dataset.timestamp));
			});
	};

	whale.ready(() => {
		const liveRecent = document.querySelector('.live-recent');

		if (!liveRecent) {
			return;
		}

		const feeds = [...liveRecent.querySelectorAll('.live-recent-feed')]
			.map((element) => ({
				element,
				list: element.querySelector('.live-recent-list'),
				limit: Number(liveRecent.dataset.limit) || 10,
				namespaces: element.dataset.namespaces,
				loaded: false,
				requestId: 0,
			}))
			.filter((feed) => feed.list && feed.namespaces);

		if (feeds.length === 0) {
			return;
		}

		const sidebarMedia = window.matchMedia('(min-width: 1024px)');
		let refreshInterval = null;
		let relativeTimeInterval = null;

		const isLiveRecentVisible = () =>
			sidebarMedia.matches && liveRecent.offsetParent !== null;

		const refreshFeeds = (options) => {
			if (!isLiveRecentVisible()) {
				return;
			}

			feeds.forEach((feed) => {
				refreshFeed(feed, options);
			});
		};

		const stopTimers = () => {
			window.clearInterval(refreshInterval);
			window.clearInterval(relativeTimeInterval);
			refreshInterval = null;
			relativeTimeInterval = null;
		};

		const startTimers = () => {
			if (!isLiveRecentVisible()) {
				stopTimers();
				return;
			}

			refreshFeeds({ showLoading: true });

			refreshInterval ??= window.setInterval(() => {
				refreshFeeds({ force: true });
			}, AUTO_REFRESH_INTERVAL);
			relativeTimeInterval ??= window.setInterval(() => {
				updateRelativeTimes(liveRecent);
			}, RELATIVE_TIME_INTERVAL);
		};

		whale.onMediaChange(sidebarMedia, startTimers);
		startTimers();
	});
})();
