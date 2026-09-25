/**
 * plugins/generic/scholarlyReader/js/citations.js
 *
 * Accessible popovers for in-text citation links (a.sr-xref → #ref-N):
 * shows the reference on hover or keyboard focus, closes on Esc/blur, and
 * highlights the reference when the link is followed.
 */
(function () {
	'use strict';

	var links = document.querySelectorAll('a.sr-xref');
	if (!links.length) return;

	var i18n = window.scholarlyReaderI18n || { goToReference: 'Go to reference', reference: 'Reference' };
	var popover = document.createElement('div');
	popover.className = 'sr-popover';
	popover.id = 'sr-popover';
	popover.setAttribute('role', 'tooltip');
	popover.hidden = true;
	document.body.appendChild(popover);

	var current = null;
	var hideTimer = null;

	function referenceText(ref) {
		var clone = ref.cloneNode(true);
		clone.querySelectorAll('.sr-ref-links').forEach(function (el) { el.remove(); });
		return clone.innerHTML;
	}

	function show(link) {
		var id = link.getAttribute('href').slice(1);
		var ref = document.getElementById(id);
		if (!ref) return;
		clearTimeout(hideTimer);
		current = link;
		popover.innerHTML =
			'<span class="sr-popover__label">' + i18n.reference + ' ' + link.getAttribute('data-ref') + '</span>' +
			'<div class="sr-popover__text">' + referenceText(ref) + '</div>' +
			'<a class="sr-popover__go" href="#' + id + '">' + i18n.goToReference + ' →</a>';
		popover.hidden = false;
		link.setAttribute('aria-describedby', 'sr-popover');

		var r = link.getBoundingClientRect();
		var width = Math.min(360, document.documentElement.clientWidth - 24);
		popover.style.maxWidth = width + 'px';
		var left = Math.max(12, Math.min(r.left + window.scrollX - 20, window.scrollX + document.documentElement.clientWidth - width - 12));
		var top = r.bottom + window.scrollY + 8;
		popover.style.left = left + 'px';
		popover.style.top = top + 'px';
		// Flip above the link when there is no room below
		var ph = popover.offsetHeight;
		if (r.bottom + ph + 16 > window.innerHeight && r.top > ph + 16) {
			popover.style.top = (r.top + window.scrollY - ph - 8) + 'px';
		}
	}

	function hide(delay) {
		clearTimeout(hideTimer);
		hideTimer = setTimeout(function () {
			popover.hidden = true;
			if (current) current.removeAttribute('aria-describedby');
			current = null;
		}, delay || 0);
	}

	function flash(id) {
		var ref = document.getElementById(id);
		if (!ref) return;
		ref.classList.remove('sr-flash');
		void ref.offsetWidth;
		ref.classList.add('sr-flash');
	}

	links.forEach(function (link) {
		link.addEventListener('mouseenter', function () { show(link); });
		link.addEventListener('mouseleave', function () { hide(250); });
		link.addEventListener('focus', function () { show(link); });
		link.addEventListener('blur', function () { hide(150); });
		link.addEventListener('click', function () {
			hide();
			flash(link.getAttribute('href').slice(1));
		});
	});

	popover.addEventListener('mouseenter', function () { clearTimeout(hideTimer); });
	popover.addEventListener('mouseleave', function () { hide(200); });
	popover.addEventListener('click', function (e) {
		var go = e.target.closest('.sr-popover__go');
		if (go) {
			hide();
			flash(go.getAttribute('href').slice(1));
		}
	});

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape' && !popover.hidden) hide();
	});
})();
