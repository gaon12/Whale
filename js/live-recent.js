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

		const refreshLiveRecent = () => {
			if (!list || list.offsetParent === null) {
				return;
			}

			const getParameter = {
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

			mw.loader
				.using('mediawiki.api')
				.then(() => {
					const api = new mw.Api();
					return api.get(getParameter);
				})
				.then((data) => {
					const recentChanges = data.query.recentchanges;
					list.innerHTML = recentChanges
						.map((item) => {
							const time = new Date(item.timestamp);
							let text = item.type === 'new' ? '[New]' : '';
							text += item.title;

							if (text.length > 13) {
								text = `${text.slice(0, 13)}...`;
							}

							text = text.replace(
								'[New]',
								`<span class="new">${mw.message('whale-feed-new').escaped()} </span>`,
							);
							return `<li><a class="recent-item" href="${mw.util.getUrl(item.title)}" title="${item.title}">[${timeFormat(time)}] ${text}</a></li>`;
						})
						.join('\n');
				})
				.catch(() => {});
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
