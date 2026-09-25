/**
 * @file plugins/themes/meridian/js/meridian.js
 *
 * @brief Meridian UI behaviour: drawer, search overlay, disclosure toggles,
 *  copy buttons and the "On this page" outline highlighting.
 *  Vanilla JS, no dependencies; everything degrades to plain links.
 */
(function () {
	'use strict';

	var lastTrigger = null;

	function focusables(root) {
		return Array.prototype.slice.call(root.querySelectorAll(
			'a[href], button:not([disabled]), input:not([disabled]), select, textarea, [tabindex]:not([tabindex="-1"])'
		)).filter(function (el) { return el.offsetParent !== null; });
	}

	function setExpanded(id, value) {
		document.querySelectorAll('[aria-controls="' + id + '"]').forEach(function (btn) {
			btn.setAttribute('aria-expanded', value ? 'true' : 'false');
		});
	}

	function open(id, trigger) {
		var el = document.getElementById(id);
		if (!el) return;
		lastTrigger = trigger || document.activeElement;
		el.hidden = false;
		setExpanded(id, true);
		if (id === 'm-drawer') document.body.classList.add('m-lock');
		var target = el.querySelector('input[type="search"]') || focusables(el)[0];
		if (target) target.focus();
	}

	function close(id) {
		var el = document.getElementById(id);
		if (!el || el.hidden) return;
		el.hidden = true;
		setExpanded(id, false);
		if (id === 'm-drawer') document.body.classList.remove('m-lock');
		if (lastTrigger && lastTrigger.focus) lastTrigger.focus();
	}

	document.addEventListener('click', function (e) {
		var opener = e.target.closest('[data-m-open]');
		if (opener) {
			e.preventDefault();
			var id = opener.getAttribute('data-m-open');
			var el = document.getElementById(id);
			if (el && !el.hidden) close(id); else open(id, opener);
			return;
		}
		var closer = e.target.closest('[data-m-close]');
		if (closer) {
			e.preventDefault();
			close(closer.getAttribute('data-m-close'));
			return;
		}

		// Disclosure toggles (abstracts, contents panel)
		var toggle = e.target.closest('.m-toggle[aria-controls]');
		if (toggle) {
			var panel = document.getElementById(toggle.getAttribute('aria-controls'));
			if (!panel) return;
			var expanded = toggle.getAttribute('aria-expanded') === 'true';
			toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
			panel.hidden = expanded;
			return;
		}

		// Copy-to-clipboard buttons
		var copy = e.target.closest('[data-m-copy]');
		if (copy && navigator.clipboard) {
			navigator.clipboard.writeText(copy.getAttribute('data-m-copy')).then(function () {
				var label = copy.querySelector('.m-copy-label');
				if (!label) return;
				var original = label.textContent;
				label.textContent = copy.getAttribute('data-m-copied') || original;
				setTimeout(function () { label.textContent = original; }, 1600);
			});
		}
	});

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape') {
			close('m-drawer');
			close('m-search');
			return;
		}
		// Keep focus inside the open drawer
		var drawer = document.getElementById('m-drawer');
		if (e.key === 'Tab' && drawer && !drawer.hidden) {
			var items = focusables(drawer);
			if (!items.length) return;
			var first = items[0], last = items[items.length - 1];
			if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
			else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
		}
	});

	// Highlight the current section in "On this page" outlines
	var outline = document.querySelector('[data-m-outline]');
	if (outline && 'IntersectionObserver' in window) {
		var links = {};
		outline.querySelectorAll('a[href^="#"]').forEach(function (a) {
			links[decodeURIComponent(a.getAttribute('href').slice(1))] = a;
		});
		var observer = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (!entry.isIntersecting) return;
				Object.keys(links).forEach(function (id) { links[id].removeAttribute('aria-current'); });
				var link = links[entry.target.id];
				if (link) link.setAttribute('aria-current', 'true');
			});
		}, { rootMargin: '-20% 0px -70% 0px' });
		Object.keys(links).forEach(function (id) {
			var section = document.getElementById(id);
			if (section) observer.observe(section);
		});
	}
})();
