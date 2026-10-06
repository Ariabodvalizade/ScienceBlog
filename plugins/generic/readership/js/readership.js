/**
 * plugins/generic/readership/js/readership.js
 *
 * Distributed under the GNU GPL v3.
 *
 * Refreshes the Readership page's "Online now" count, country list and map
 * dots every minute. Elements are looked up on each refresh because the
 * backend's Vue app renders the page after this script loads.
 */
(function () {
	'use strict';

	var SVG = 'http://www.w3.org/2000/svg';

	function dot(position) {
		var g = document.createElementNS(SVG, 'g');
		g.setAttribute('class', 'rs-live');
		g.setAttribute('transform', 'translate(' + position[0] + ' ' + position[1] + ')');
		['rs-live__pulse', 'rs-live__dot'].forEach(function (cls, i) {
			var c = document.createElementNS(SVG, 'circle');
			c.setAttribute('class', cls);
			c.setAttribute('r', i ? '4' : '5');
			g.appendChild(c);
		});
		return g;
	}

	function render(data) {
		var count = document.querySelector('[data-rs-count]');
		var list = document.querySelector('[data-rs-list]');
		var layer = document.querySelector('.rs-map__live');
		if (count) {
			count.textContent = data.countF;
		}
		if (list) {
			var names = (data.countries || []).map(function (c) {
				return c.name + ' (' + c.count + ')';
			});
			list.textContent = names.length ? list.getAttribute('data-rs-prefix') + ' ' + names.join(', ') : '';
		}
		if (layer) {
			while (layer.firstChild) {
				layer.removeChild(layer.firstChild);
			}
			(data.countries || []).forEach(function (c) {
				if (c.position) {
					layer.appendChild(dot(c.position));
				}
			});
		}
	}

	function refresh() {
		var page = document.querySelector('[data-rs-live]');
		if (!page || document.hidden) {
			return;
		}
		fetch(page.getAttribute('data-rs-live'), {credentials: 'same-origin', headers: {Accept: 'application/json'}})
			.then(function (r) {
				return r.ok ? r.json() : null;
			})
			.then(function (data) {
				if (data) {
					render(data);
				}
			})
			.catch(function () {});
	}

	setInterval(refresh, 60000);
	document.addEventListener('visibilitychange', function () {
		if (!document.hidden) {
			refresh();
		}
	});
})();
