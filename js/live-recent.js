(() => {
	const ready = (callback) => {
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', callback, { once: true });
			return;
		}
		callback();
	};

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

	ready(() => {
		const liveRecent = document.querySelector('.live-recent');
		const list = document.getElementById('live-recent-list');
		const articleTab = document.getElementById('whale-recent-tab1');
		const talkTab = document.getElementById('whale-recent-tab2');

		if (!liveRecent || !list || !articleTab || !talkTab) {
			return;
		}

		const articleNamespaces = liveRecent.dataset.articleNs;
		const talkNamespaces = liveRecent.dataset.talkNs;
		const limit = list.childElementCount;
		let isArticleTab = true;

		const refreshLiveRecent = async () => {
			if (!list || list.offsetParent === null) {
				return;
			}

			const parameters = {
				action: 'query',
				list: 'recentchanges',
				rcprop: 'title|timestamp',
				rcshow: '!bot|!redirect',
				rctype: 'edit|new',
				rclimit: limit,
				format: 'json',
				rcnamespace: isArticleTab ? articleNamespaces : talkNamespaces,
				rctoponly: true,
			};

			try {
				await mw.loader.using('mediawiki.api');
				const api = new mw.Api();
				const data = await api.get(parameters);
				const fragment = new DocumentFragment();

				for (const item of data.query.recentchanges) {
					const time = new Date(item.timestamp);
					const listItem = document.createElement('li');
					const link = document.createElement('a');
					const title =
						item.title.length > 13
							? `${item.title.slice(0, 13)}...`
							: item.title;

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
					fragment.append(listItem);
				}

				list.replaceChildren(fragment);
			} catch {
				// Keep the current list when refresh fails.
			}
		};

		articleTab.addEventListener('click', () => {
			articleTab.classList.add('active');
			talkTab.classList.remove('active');
			isArticleTab = true;
			refreshLiveRecent();
		});

		talkTab.addEventListener('click', () => {
			talkTab.classList.add('active');
			articleTab.classList.remove('active');
			isArticleTab = false;
			refreshLiveRecent();
		});

		setInterval(refreshLiveRecent, 5 * 60 * 1000);
		refreshLiveRecent();
	});
})();
