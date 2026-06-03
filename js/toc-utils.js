(() => {
	const normalizeText = (text) => text?.trim().replace(/\s+/g, ' ') || '';

	const getTocLevel = (link) => {
		const item = link.closest('li');
		const levelClass = [...(item?.classList || [])].find((className) =>
			/^toclevel-\d+$/.test(className),
		);
		const level = Number(levelClass?.replace('toclevel-', ''));

		return Number.isInteger(level) && level > 0 ? Math.min(level, 6) : 1;
	};

	const getTargetText = (target) => {
		if (!target) {
			return '';
		}

		const label = target.querySelector?.('.mw-headline') || target;
		const clone = label.cloneNode(true);
		clone
			.querySelectorAll?.('.whale-heading-anchor, .mw-editsection')
			.forEach((node) => {
				node.remove();
			});

		return normalizeText(clone.textContent);
	};

	const getLinkText = (link, target) => {
		const targetText = getTargetText(target);
		if (targetText) {
			return targetText;
		}

		const text = normalizeText(link.querySelector('.toctext')?.textContent);
		if (text) {
			return text;
		}

		const clone = link.cloneNode(true);
		clone.querySelectorAll?.('.tocnumber').forEach((node) => {
			node.remove();
		});

		return normalizeText(clone.textContent);
	};

	window.whale = {
		...(window.whale || {}),
		tocUtils: {
			getLinkText,
			getTargetText,
			getTocLevel,
		},
	};
})();
