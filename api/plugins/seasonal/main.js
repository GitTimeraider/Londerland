/*
 * Seasonal Themes: on special days shows a small scene in the bottom left corner (a snowman with a bit of
 * snow, a glowing pumpkin, ...), sometimes a figure that moves along the bottom edge, a decoration next to
 * the logo and a greeting. Nothing covers the middle of the screen. The season is picked here, from the
 * viewer's local date and the plugin settings.
 */
(function () {
	'use strict';

	const OFF_KEY = 'londerland-seasonal-off';
	const GREETED_KEY = 'londerland-seasonal-greeted';
	// Particles around the corner figure
	const AMOUNT = { low: 8, medium: 16, high: 30 };
	// Checked in this order: special days before the longer periods, so April Fools' wins over Easter
	const ORDER = ['aprilfools', 'programmersday', 'sysadminday', 'kingsday', 'valentine', 'easter', 'newyear', 'halloween', 'christmas'];
	/*
	 * star: the figure in the corner; motion: how it moves (bob, glow, hop or still); particles: the small
	 * effect that stays around it (snow, confetti, rise, steam, binary or none). wanderer: a figure that moves
	 * along the bottom of the screen (float, crawl or waddle).
	 */
	const SEASONS = {
		newyear: {
			star: '🍾', motion: 'bob', particles: 'confetti', colors: ['#ff5252', '#ffd740', '#69f0ae', '#40c4ff', '#e040fb'],
			decoration: '🎉', greeting: 'Happy New Year! May your uptime be high and your tickets few.',
		},
		valentine: {
			star: '💌', motion: 'bob', particles: 'rise', symbols: ['❤️', '💕', '💖'],
			decoration: '💘', greeting: "Happy Valentine's Day! ❤️",
		},
		easter: {
			star: '🐰', motion: 'hop', particles: null, wanderer: { symbol: '🐣', path: 'waddle' },
			decoration: '🐰', greeting: 'Happy Easter!',
		},
		aprilfools: {
			star: '🃏', motion: 'bob', particles: null, wanderer: { symbol: '🐛', path: 'crawl' }, flipLogo: true,
			decoration: '🃏', greeting: "Warning: bugs detected in production. Happy April Fools' Day!",
		},
		kingsday: {
			star: '👑', motion: 'bob', particles: 'confetti', colors: ['#ff6d00', '#ff9100', '#ffab40', '#ffffff', '#1e88e5'],
			decoration: '👑', greeting: 'Fijne Koningsdag! 🧡',
		},
		sysadminday: {
			star: '☕', motion: 'still', particles: 'steam',
			decoration: '☕', greeting: 'Happy SysAdmin Day! Have you thanked your sysadmin today?',
		},
		programmersday: {
			star: '🖥️', motion: 'still', particles: 'binary',
			decoration: '💾', greeting: "Happy Programmers' Day! Day 256 = 0x100.",
		},
		halloween: {
			star: '🎃', motion: 'glow', particles: null, wanderer: { symbol: '👻', path: 'float' },
			decoration: '🎃', greeting: 'Happy Halloween! 🎃',
		},
		christmas: {
			star: '⛄', motion: 'bob', particles: 'snow',
			decoration: '🎅', greeting: 'Happy holidays! ❄️',
		},
	};

	let season = null;
	let includes = {};
	let effect = null;

	$('body').arrive('#activeInfo', { onceOnly: true }, function () {
		seasonalLaunch();
	});

	function seasonalLaunch() {
		const plugins = activeInfo.plugins || {};
		includes = plugins.includes || {};
		if (plugins['SEASONAL-enabled'] !== true || !forThisViewer()) {
			return;
		}
		season = currentSeason(new Date());
		if (!season) {
			return;
		}
		// Achievements counts the seasons a user has seen
		window.LonderlandSeason = season;
		$(document).trigger('londerland:season', [season]);
		$('body').addClass('seasonal-' + season);
		if (setting('decorations')) {
			decorate();
		}
		if (setting('userToggle')) {
			addMenuToggle();
		}
		if (setting('greeting')) {
			greet();
		}
		if (setting('effects') && !userSwitchedOff()) {
			startEffect();
		}
	}

	/* ===================== settings & dates ===================== */

	function setting(name) {
		const value = includes['SEASONAL-' + name + '-include'];
		return value === true || value === 'true';
	}

	function forThisViewer() {
		const audience = includes['SEASONAL-audience-include'] || 'both';
		const loggedIn = !!(activeInfo.user && activeInfo.user.loggedin);
		return audience === 'both' || (audience === 'users' && loggedIn) || (audience === 'guests' && !loggedIn);
	}

	function currentSeason(today) {
		const forced = includes['SEASONAL-force-include'] || 'auto';
		if (forced !== 'auto') {
			return SEASONS[forced] ? forced : null;
		}
		return ORDER.find((key) => setting(key) && isInSeason(key, today)) || null;
	}

	function sameDay(a, b) {
		return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
	}

	function isInSeason(key, today) {
		const year = today.getFullYear();
		switch (key) {
			case 'easter': {
				const easter = easterSunday(year);
				const from = new Date(year, easter.getMonth(), easter.getDate() - 2);
				const to = new Date(year, easter.getMonth(), easter.getDate() + 1);
				const day = new Date(year, today.getMonth(), today.getDate());
				return day >= from && day <= to;
			}
			case 'sysadminday': {
				const lastFriday = new Date(year, 7, 0); // 31 July
				lastFriday.setDate(31 - ((lastFriday.getDay() + 2) % 7));
				return sameDay(today, lastFriday);
			}
			case 'programmersday':
				return sameDay(today, new Date(year, 0, 256));
			default:
				return inDateRange(includes['SEASONAL-' + key + '-dates-include'], today, key);
		}
	}

	// Anonymous Gregorian algorithm
	function easterSunday(year) {
		const a = year % 19;
		const b = Math.floor(year / 100);
		const c = year % 100;
		const d = Math.floor(b / 4);
		const e = b % 4;
		const f = Math.floor((b + 8) / 25);
		const g = Math.floor((b - f + 1) / 3);
		const h = (19 * a + b - d - g + 15) % 30;
		const i = Math.floor(c / 4);
		const k = c % 4;
		const l = (32 + 2 * e + 2 * i - h - k) % 7;
		const m = Math.floor((a + 11 * h + 22 * l) / 451);
		const month = Math.floor((h + l - 7 * m + 114) / 31);
		const day = ((h + l - 7 * m + 114) % 31) + 1;
		return new Date(year, month - 1, day);
	}

	// "MM-DD" or "MM-DD - MM-DD"; a period may run past 31 December
	function inDateRange(value, today, key) {
		const parts = String(value || '').match(/^\s*(\d{1,2})-(\d{1,2})\s*(?:-\s*(\d{1,2})-(\d{1,2}))?\s*$/);
		if (!parts) {
			if (String(value || '').trim() !== '') {
				console.warn('Seasonal Themes: dates of ' + key + ' are not like MM-DD or MM-DD - MM-DD: ' + value);
			}
			return false;
		}
		const toNumber = (month, day) => parseInt(month, 10) * 100 + parseInt(day, 10);
		const from = toNumber(parts[1], parts[2]);
		const to = parts[3] ? toNumber(parts[3], parts[4]) : from;
		const now = toNumber(today.getMonth() + 1, today.getDate());
		return from <= to ? now >= from && now <= to : now >= from || now <= to;
	}

	/* ===================== decorations, greeting, user toggle ===================== */

	function t(text) {
		return window.lang && typeof window.lang.translate === 'function' ? window.lang.translate(text) : text;
	}

	function storage(action, key, value) {
		try {
			if (action === 'get') {
				return window.localStorage.getItem(key);
			}
			if (value === null) {
				window.localStorage.removeItem(key);
			} else {
				window.localStorage.setItem(key, value);
			}
		} catch (e) {
			return null;
		}
		return null;
	}

	function decorate() {
		const info = SEASONS[season];
		// on the logo link: the logo itself hides anything that sticks out
		$('.top-left-part .logo').addClass('seasonal-logo').attr('data-seasonal-decoration', info.decoration);
		if (info.flipLogo) {
			$('body').addClass('seasonal-flip-logo');
		}
	}

	function greet() {
		const today = new Date().toDateString() + '|' + season;
		if (storage('get', GREETED_KEY) === today) {
			return;
		}
		storage('set', GREETED_KEY, today);
		const info = SEASONS[season];
		message(info.decoration + ' ' + t(info.greeting), '', activeInfo.settings.notifications.position, '#FFF', 'info', '8000');
	}

	function userSwitchedOff() {
		return setting('userToggle') && storage('get', OFF_KEY) === '1';
	}

	function addMenuToggle() {
		const item = `<li><a href="javascript:void(0)" class="seasonal-toggle"><i class="fa fa-snowflake-o fa-fw"></i> <span lang="en">Seasonal effects</span>: <span class="seasonal-toggle-state"></span></a></li>`;
		$('.append-menu').first().after(item);
		updateToggleLabel();
		$(document).on('click', '.seasonal-toggle', function () {
			if (userSwitchedOff()) {
				storage('set', OFF_KEY, null);
				if (setting('effects')) {
					startEffect();
				}
			} else {
				storage('set', OFF_KEY, '1');
				stopEffect();
			}
			updateToggleLabel();
		});
	}

	function updateToggleLabel() {
		$('.seasonal-toggle-state').text(t(userSwitchedOff() ? 'off' : 'on'));
	}

	/* ===================== moving effects ===================== */

	function startEffect() {
		if (effect) {
			return;
		}
		const reducedMotion = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
		effect = new CornerScene(SEASONS[season], AMOUNT[includes['SEASONAL-intensity-include']] || AMOUNT.medium, reducedMotion);
	}

	function stopEffect() {
		if (effect) {
			effect.destroy();
			effect = null;
		}
	}

	function random(min, max) {
		return min + Math.random() * (max - min);
	}

	function pick(list) {
		return list[Math.floor(Math.random() * list.length)];
	}

	/**
	 * Calls back with false while the browser tab is hidden or, unless allowed in the settings,
	 * while a website (iFrame) tab is showing; with true otherwise.
	 */
	function Visibility(callback) {
		this.callback = callback;
		this.check = this.check.bind(this);
		document.addEventListener('visibilitychange', this.check);
		this.observer = new MutationObserver(this.check);
		$('.iFrame-listing, .iFrame-listing-right').each((i, element) => {
			this.observer.observe(element, { attributes: true, attributeFilter: ['class'] });
		});
		this.check();
	}

	Visibility.prototype.check = function () {
		const overTab = !setting('overTabs') && $('.iFrame-listing, .iFrame-listing-right').is('.show');
		this.callback(!document.hidden && !overTab, overTab);
	};

	Visibility.prototype.destroy = function () {
		document.removeEventListener('visibilitychange', this.check);
		this.observer.disconnect();
	};

	/**
	 * Particles on the small canvas above the corner figure; they stay in that box.
	 */
	function Particles(canvas, type, amount, info) {
		this.canvas = canvas;
		this.context = canvas.getContext('2d');
		this.type = type;
		this.info = info;
		// snowflakes are tiny, so there are a few more of them
		this.amount = Math.round(amount * ({ binary: 0.8, snow: 1.6 }[type] || 1));
		this.list = [];
		this.running = false;
		this.frame = null;
		this.last = 0;
		this.tick = this.tick.bind(this);
		this.resize = this.resize.bind(this);
		this.resize();
		for (let i = 0; i < this.amount; i++) {
			this.list.push(this.spawn(true));
		}
		window.addEventListener('resize', this.resize);
	}

	Particles.prototype.resize = function () {
		const ratio = window.devicePixelRatio || 1;
		this.width = this.canvas.clientWidth;
		this.height = this.canvas.clientHeight;
		this.canvas.width = this.width * ratio;
		this.canvas.height = this.height * ratio;
		this.context.setTransform(ratio, 0, 0, ratio, 0, 0);
	};

	Particles.prototype.run = function (on) {
		if (on && !this.running) {
			this.running = true;
			this.last = performance.now();
			this.frame = requestAnimationFrame(this.tick);
		} else if (!on && this.running) {
			this.running = false;
			cancelAnimationFrame(this.frame);
		}
	};

	// New particle above the box (or anywhere, for the first ones); rising ones start at the figure
	Particles.prototype.spawn = function (anywhere) {
		const p = {
			x: random(0, this.width),
			y: anywhere ? random(0, this.height) : -30,
			speed: random(18, 48), // pixels per second
			drift: random(-9, 9),
			wobble: random(0, Math.PI * 2),
			wobbleSpeed: random(0.5, 1.5),
			rotation: random(0, Math.PI * 2),
			spin: random(-1.5, 1.5),
			size: random(9, 17),
			opacity: 0.9,
		};
		switch (this.type) {
			case 'snow':
				p.size = random(1, 3.2);
				p.speed = random(12, 36);
				p.opacity = random(0.5, 0.95);
				break;
			case 'confetti':
				p.size = random(4, 8);
				p.speed = random(30, 66);
				p.color = pick(this.info.colors);
				break;
			case 'binary':
				p.size = random(8, 14);
				p.speed = random(36, 84);
				p.drift = 0;
				p.symbol = Math.random() < 0.5 ? '0' : '1';
				p.opacity = random(0.35, 0.8);
				break;
			case 'steam':
				// small puffs above the middle of the box, where the cup is
				p.x = this.width / 2 + random(-10, 10);
				p.y = anywhere ? random(this.height * 0.2, this.height * 0.6) : this.height * 0.6;
				p.speed = -random(12, 24);
				p.drift = random(-4, 4);
				p.size = random(3, 5);
				break;
			default:
				p.symbol = pick(this.info.symbols || ['❤️']);
		}
		if (this.type === 'rise') {
			p.x = this.width / 2 + random(-30, 30);
			p.y = anywhere ? random(0, this.height * 0.6) : this.height * 0.6;
			p.speed = -random(15, 36);
		}
		return p;
	};

	Particles.prototype.gone = function (p) {
		if (this.type === 'steam') {
			return p.y < this.height * 0.05;
		}
		if (this.type === 'rise') {
			return p.y < -20;
		}
		return p.y > this.height + 40 || p.y < -60 || p.x < -60 || p.x > this.width + 60;
	};

	Particles.prototype.tick = function (now) {
		if (!this.running) {
			return;
		}
		// seconds since the last frame, capped so a hiccup does not teleport everything
		const step = Math.min(0.05, (now - this.last) / 1000);
		this.last = now;
		this.context.clearRect(0, 0, this.width, this.height);
		for (let i = 0; i < this.list.length; i++) {
			const p = this.list[i];
			p.wobble += p.wobbleSpeed * step;
			p.rotation += p.spin * step;
			p.y += p.speed * step;
			p.x += (p.drift + Math.sin(p.wobble) * 8) * step;
			if (this.gone(p)) {
				this.list[i] = this.spawn(false);
				continue;
			}
			this.draw(p);
		}
		this.frame = requestAnimationFrame(this.tick);
	};

	Particles.prototype.draw = function (p) {
		const context = this.context;
		switch (this.type) {
			case 'snow':
				context.globalAlpha = p.opacity;
				context.fillStyle = '#ffffff';
				context.beginPath();
				context.arc(p.x, p.y, p.size, 0, Math.PI * 2);
				context.fill();
				break;
			case 'steam': {
				// grows and fades while it rises
				const left = Math.max(0, (p.y - this.height * 0.05) / (this.height * 0.55));
				context.globalAlpha = 0.45 * left;
				context.fillStyle = '#d8dde6';
				context.beginPath();
				context.arc(p.x, p.y, p.size + (1 - left) * 6, 0, Math.PI * 2);
				context.fill();
				break;
			}
			case 'confetti':
				context.save();
				context.translate(p.x, p.y);
				context.rotate(p.rotation);
				// scaling by the wobble makes the piece look like it flips over
				context.scale(1, Math.cos(p.wobble * 3));
				context.globalAlpha = 0.9;
				context.fillStyle = p.color;
				context.fillRect(-p.size / 2, -p.size / 4, p.size, p.size / 2);
				context.restore();
				break;
			case 'binary':
				context.globalAlpha = p.opacity;
				context.fillStyle = '#00e676';
				context.font = Math.round(p.size) + 'px monospace';
				context.fillText(p.symbol, p.x, p.y);
				break;
			default:
				context.save();
				context.translate(p.x, p.y);
				context.rotate(Math.sin(p.rotation) * 0.4);
				context.globalAlpha = p.opacity;
				context.font = Math.round(p.size) + 'px sans-serif';
				context.textAlign = 'center';
				context.textBaseline = 'middle';
				context.fillText(p.symbol, 0, 0);
				context.restore();
		}
		context.globalAlpha = 1;
	};

	Particles.prototype.destroy = function () {
		this.run(false);
		window.removeEventListener('resize', this.resize);
	};

	/**
	 * Corner scene: a figure in the bottom left corner with its own small effect, and for some seasons
	 * a second figure that moves along the bottom of the screen. Nothing catches clicks.
	 */
	function CornerScene(info, amount, reducedMotion) {
		const $corner = $(`
			<div class="seasonal-corner" aria-hidden="true">
				${info.particles && !reducedMotion ? '<canvas class="seasonal-corner-canvas"></canvas>' : ''}
				<div class="seasonal-star seasonal-motion-${reducedMotion ? 'still' : info.motion}">${info.star}</div>
			</div>`).appendTo('body');
		this.$elements = $corner;
		if (info.wanderer && !reducedMotion) {
			const $wanderer = $(`
				<div class="seasonal-wanderer seasonal-path-${info.wanderer.path}" aria-hidden="true">
					<div class="seasonal-walker"><span>${info.wanderer.symbol}</span></div>
				</div>`).appendTo('body');
			this.$elements = this.$elements.add($wanderer);
		}
		const canvas = $corner.find('canvas')[0];
		this.particles = canvas ? new Particles(canvas, info.particles, amount, info) : null;
		this.visibility = new Visibility((on, overTab) => {
			// hidden on top of website tabs; only paused while the browser tab is in the background
			this.$elements.toggleClass('hidden', overTab).toggleClass('seasonal-paused', !on);
			if (this.particles) {
				this.particles.run(on);
			}
		});
	}

	CornerScene.prototype.destroy = function () {
		this.visibility.destroy();
		if (this.particles) {
			this.particles.destroy();
		}
		this.$elements.remove();
	};
})();
