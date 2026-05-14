(function() {
	'use strict';

	document.addEventListener('DOMContentLoaded', function() {
		initDarkMode();
		initReadingProgressBar();
		initCopyButtons();
		initCollapsibleSections();
	});

	function initDarkMode() {
		const theme = document.documentElement.getAttribute('data-freedom-dark-mode') || 'auto';
		if (theme === 'auto') {
			if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
				document.documentElement.setAttribute('data-theme', 'dark');
			}
		} else {
			document.documentElement.setAttribute('data-theme', theme);
		}
	}

	function initReadingProgressBar() {
		const progressContainer = document.createElement('div');
		progressContainer.className = 'freedom-progress-container';
		const progressBar = document.createElement('div');
		progressBar.className = 'freedom-progress-bar';
		progressContainer.appendChild(progressBar);
		document.body.appendChild(progressContainer);

		window.addEventListener('scroll', function() {
			const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
			const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
			const scrolled = (winScroll / height) * 100;
			progressBar.style.width = scrolled + '%';
		});
	}

	function initCopyButtons() {
		const codeBlocks = document.querySelectorAll('pre');
		codeBlocks.forEach(function(block) {
			const button = document.createElement('button');
			button.innerText = 'Copy';
			button.className = 'freedom-copy-button';
			block.style.position = 'relative';
			block.appendChild(button);

			button.addEventListener('click', function() {
				const code = block.querySelector('code') ? block.querySelector('code').innerText : block.innerText;
				navigator.clipboard.writeText(code).then(function() {
					button.innerText = 'Copied!';
					setTimeout(function() { button.innerText = 'Copy'; }, 2000);
				});
			});
		});
	}

	function initCollapsibleSections() {
		// Basic implementation for collapsible sections
		const headings = document.querySelectorAll('.freedom-body-text h2, .freedom-body-text h3');
		headings.forEach(function(heading) {
			heading.classList.add('freedom-collapsible-heading');
			heading.addEventListener('click', function() {
				let content = heading.nextElementSibling;
				while (content && !['H2', 'H3'].includes(content.tagName)) {
					content.classList.toggle('freedom-collapsed');
					content = content.nextElementSibling;
				}
				heading.classList.toggle('freedom-heading-collapsed');
			});
		});
	}

})();
