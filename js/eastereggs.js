/*
 * Easter eggs. custom.js listens for the triggers (the Konami code, typed words, ten clicks on the logo, magic phrases
 * in the command palette) and fetches this file the first time one is found (londerlandLoadLibrary('eastereggs')).
 * Admins switch the effects off under Settings > Customize > Appearance > Options > Show Easter Eggs.
 */
(function () {
	'use strict';

	const SNAKE_BEST_KEY = 'londerland-snake-best';
	// a Snake score from which the Achievements plugin gives its badge
	const SNAKE_BADGE_SCORE = 15;
	let styled = false;
	let matrixStop = null;

	/* ===================== helpers ===================== */

	function t(text) {
		return window.lang && typeof window.lang.translate === 'function' ? window.lang.translate(text) : text;
	}

	function esc(value) {
		return $('<div>').text(value == null ? '' : String(value)).html();
	}

	function reducedMotion() {
		return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	}

	function user() {
		return (window.activeInfo && activeInfo.user) || {};
	}

	function addStyle() {
		if (!styled) {
			styled = true;
			$('<style id="eastereggs-style">').text(STYLE).appendTo('head');
		}
	}

	function toast(heading, text, icon) {
		if (typeof window.message === 'function') {
			window.message(heading, esc(text), undefined, '#FFF', icon || 'info', 8000);
		}
	}

	// A big emoji with a line of text in the middle of the screen, gone again after a few seconds
	function banner(emoji, title, text) {
		addStyle();
		const $banner = $('<div class="egg-banner" role="status" aria-live="polite">')
			.append($('<div class="egg-banner-emoji" aria-hidden="true">').text(emoji))
			.append($('<div class="egg-banner-title">').text(title))
			.append(text ? $('<div class="egg-banner-text">').text(text) : null)
			.appendTo('body');
		setTimeout(function () {
			$banner.remove();
		}, 3600);
	}

	function overlayCanvas(className) {
		const canvas = document.createElement('canvas');
		canvas.className = className;
		document.body.appendChild(canvas);
		const fit = function () {
			canvas.width = window.innerWidth;
			canvas.height = window.innerHeight;
		};
		fit();
		return { canvas: canvas, context: canvas.getContext('2d'), fit: fit };
	}

	/* ===================== confetti ===================== */

	function confetti() {
		addStyle();
		const { canvas, context } = overlayCanvas('egg-canvas');
		const colors = ['#2cabe3', '#ff7676', '#53e69d', '#ffc36d', '#9675ce', '#f0f0f0'];
		const pieces = [];
		const count = reducedMotion() ? 60 : 180;
		for (let i = 0; i < count; i++) {
			pieces.push({
				x: canvas.width / 2 + (Math.random() - 0.5) * canvas.width * 0.3,
				y: canvas.height + 10,
				vx: (Math.random() - 0.5) * 14,
				vy: -(Math.random() * 14 + 10),
				size: Math.random() * 7 + 5,
				angle: Math.random() * Math.PI,
				spin: (Math.random() - 0.5) * 0.3,
				color: colors[i % colors.length],
			});
		}
		const started = performance.now();
		(function frame(now) {
			context.clearRect(0, 0, canvas.width, canvas.height);
			pieces.forEach(function (piece) {
				piece.vy += 0.35;
				piece.vx *= 0.99;
				piece.x += piece.vx;
				piece.y += piece.vy;
				piece.angle += piece.spin;
				context.save();
				context.translate(piece.x, piece.y);
				context.rotate(piece.angle);
				context.fillStyle = piece.color;
				context.fillRect(-piece.size / 2, -piece.size / 4, piece.size, piece.size / 2);
				context.restore();
			});
			if (now - started < 4500) {
				requestAnimationFrame(frame);
			} else {
				canvas.remove();
			}
		})(started);
	}

	/* ===================== the eggs ===================== */

	function konami() {
		confetti();
		banner('🎮', t('+30 lives'), t('Cheat code accepted.'));
	}

	function party() {
		confetti();
		banner('🎉', t('Party time!'));
	}

	function duck() {
		addStyle();
		$('.egg-duck').remove();
		const $duck = $('<div class="egg-duck" aria-hidden="true">')
			.append($('<span class="egg-duck-bird">').text('🦆'))
			.append($('<span class="egg-duck-bubble">').text(t('Quack! Explain your problem to me, line by line.')))
			.appendTo('body');
		setTimeout(function () {
			$duck.remove();
		}, 9000);
	}

	function sudo() {
		const name = user().username || 'guest';
		const admin = parseInt(user().groupID, 10) <= 1;
		addStyle();
		$('html').removeClass('egg-shake');
		void document.documentElement.offsetWidth;
		$('html').addClass('egg-shake');
		setTimeout(function () {
			$('html').removeClass('egg-shake');
		}, 600);
		if (admin) {
			toast('sudo', t('We trust you have received the usual lecture from the local System Administrator. Respect the privacy of others, think before you type, and with great power comes great responsibility.'), 'warning');
		} else {
			toast('sudo', name + ' ' + t('is not in the sudoers file. This incident will be reported.'), 'error');
		}
	}

	function roll() {
		if (reducedMotion()) {
			banner('🛩️', t('Do a barrel roll!'), t('(Your device asks for less motion, so we only pretended.)'));
			return;
		}
		addStyle();
		$('html').removeClass('egg-roll');
		void document.documentElement.offsetWidth;
		$('html').addClass('egg-roll');
		setTimeout(function () {
			$('html').removeClass('egg-roll');
		}, 1300);
	}

	function teapot() {
		banner('🫖', t("418 I'm a teapot"), t('The server refuses to brew coffee, because it is, permanently, a teapot.'));
	}

	function answer() {
		banner('🌌', '42', t('The Answer to the Ultimate Question of Life, the Universe, and Everything.'));
	}

	function xyzzy() {
		toast('xyzzy', t('Nothing happens.'), 'info');
	}

	/* ===================== matrix ===================== */

	function matrix() {
		if (matrixStop) {
			matrixStop();
			return;
		}
		addStyle();
		const { canvas, context, fit } = overlayCanvas('egg-canvas egg-matrix');
		const size = 16;
		const glyphs = 'ｱｲｳｴｵｶｷｸｹｺｻｼｽｾｿﾀﾁﾂﾃﾄﾅﾆﾇﾈﾉﾊﾋﾌﾍﾎﾏﾐﾑﾒﾓﾔﾕﾖﾗﾘﾙﾚﾛﾜﾝ0123456789';
		let drops = [];
		const reset = function () {
			fit();
			drops = Array.from({ length: Math.ceil(canvas.width / size) }, function () {
				return Math.floor((Math.random() * -canvas.height) / size);
			});
		};
		reset();
		const message = t('Wake up, Neo...');
		const $text = $('<div class="egg-matrix-text" aria-hidden="true">').appendTo('body');
		let shown = 0;
		const typing = setInterval(function () {
			shown++;
			$text.text(message.slice(0, shown));
			if (shown >= message.length) {
				clearInterval(typing);
			}
		}, 120);
		let last = 0;
		let frameId = 0;
		const frame = function (now) {
			frameId = requestAnimationFrame(frame);
			if (now - last < 50) {
				return;
			}
			last = now;
			context.fillStyle = 'rgba(0, 0, 0, 0.08)';
			context.fillRect(0, 0, canvas.width, canvas.height);
			context.font = size + 'px monospace';
			drops.forEach(function (drop, column) {
				context.fillStyle = Math.random() > 0.95 ? '#d9ffd9' : '#3bff6b';
				context.fillText(glyphs.charAt(Math.floor(Math.random() * glyphs.length)), column * size, drop * size);
				drops[column] = drop * size > canvas.height && Math.random() > 0.975 ? 0 : drop + 1;
			});
		};
		frameId = requestAnimationFrame(frame);
		const stop = function () {
			cancelAnimationFrame(frameId);
			clearInterval(typing);
			clearTimeout(timer);
			window.removeEventListener('resize', reset);
			window.removeEventListener('keydown', onKey, true);
			$(canvas).add($text).remove();
			matrixStop = null;
		};
		const onKey = function (event) {
			if (event.key === 'Escape') {
				event.stopPropagation();
				stop();
			}
		};
		const timer = setTimeout(stop, 15000);
		window.addEventListener('resize', reset);
		window.addEventListener('keydown', onKey, true);
		$(canvas).on('click', stop);
		matrixStop = stop;
	}

	/* ===================== snake ===================== */

	function best(value) {
		try {
			if (value === undefined) {
				return parseInt(window.localStorage.getItem(SNAKE_BEST_KEY) || '0', 10) || 0;
			}
			window.localStorage.setItem(SNAKE_BEST_KEY, String(value));
		} catch (e) {
			return 0;
		}
		return value;
	}

	function snake() {
		if ($('.egg-snake').length) {
			return;
		}
		addStyle();
		const cells = 20;
		const $root = $(`
			<div class="egg-snake" role="dialog" aria-modal="true" aria-label="Snake">
				<div class="egg-snake-box">
					<div class="egg-snake-head">
						<strong>🐍 Snake</strong>
						<span class="egg-snake-score"></span>
						<button type="button" class="egg-snake-close" aria-label="${esc(t('Close'))}"><i class="fa fa-times"></i></button>
					</div>
					<canvas class="egg-snake-board"></canvas>
					<div class="egg-snake-help"></div>
				</div>
			</div>`).appendTo('body');
		const canvas = $root.find('canvas')[0];
		const context = canvas.getContext('2d');
		const accent = getComputedStyle(document.documentElement).getPropertyValue('--aichat-accent').trim() || '#2cabe3';
		let cell = 20;
		let body = [];
		let direction = { x: 1, y: 0 };
		let queued = [];
		let food = null;
		let score = 0;
		let state = 'ready';
		let timer = 0;
		const previousFocus = document.activeElement;

		function fit() {
			const room = Math.min(window.innerWidth - 48, window.innerHeight - 150, 480);
			cell = Math.max(10, Math.floor(room / cells));
			canvas.width = canvas.height = cell * cells;
			draw();
		}

		function place() {
			const free = [];
			for (let x = 0; x < cells; x++) {
				for (let y = 0; y < cells; y++) {
					if (!body.some((part) => part.x === x && part.y === y)) {
						free.push({ x: x, y: y });
					}
				}
			}
			food = free[Math.floor(Math.random() * free.length)] || null;
		}

		function reset() {
			body = [{ x: 8, y: 10 }, { x: 7, y: 10 }, { x: 6, y: 10 }];
			direction = { x: 1, y: 0 };
			queued = [];
			score = 0;
			place();
		}

		function labels() {
			$root.find('.egg-snake-score').text(t('Score') + ' ' + score + ' · ' + t('Best') + ' ' + best());
			const help = {
				ready: t('Arrow keys, WASD or swipe to steer. Press Space or tap to start.'),
				playing: t('Esc closes the game.'),
				over: t('Game over! Press Space or tap to play again.'),
			}[state];
			$root.find('.egg-snake-help').text(help);
		}

		function draw() {
			context.fillStyle = '#15171d';
			context.fillRect(0, 0, canvas.width, canvas.height);
			if (food) {
				context.font = Math.floor(cell * 0.85) + 'px sans-serif';
				context.textAlign = 'center';
				context.textBaseline = 'middle';
				context.fillText('🍎', food.x * cell + cell / 2, food.y * cell + cell / 2 + 1);
			}
			body.forEach(function (part, index) {
				context.globalAlpha = index === 0 ? 1 : Math.max(0.45, 1 - index / (body.length + 6));
				context.fillStyle = index === 0 ? '#ffffff' : accent;
				context.fillRect(part.x * cell + 1, part.y * cell + 1, cell - 2, cell - 2);
			});
			context.globalAlpha = 1;
			labels();
		}

		function speed() {
			return Math.max(60, 140 - score * 4);
		}

		function step() {
			const next = queued.shift();
			if (next) {
				direction = next;
			}
			const head = { x: body[0].x + direction.x, y: body[0].y + direction.y };
			const hitWall = head.x < 0 || head.y < 0 || head.x >= cells || head.y >= cells;
			const grows = food && head.x === food.x && head.y === food.y;
			const hitSelf = body.slice(0, grows ? body.length : body.length - 1).some((part) => part.x === head.x && part.y === head.y);
			if (hitWall || hitSelf) {
				gameOver();
				return;
			}
			body.unshift(head);
			if (grows) {
				score++;
				place();
			} else {
				body.pop();
			}
			draw();
			timer = setTimeout(step, speed());
		}

		function start() {
			reset();
			state = 'playing';
			draw();
			clearTimeout(timer);
			timer = setTimeout(step, speed());
		}

		function gameOver() {
			state = 'over';
			if (score > best()) {
				best(score);
			}
			draw();
			if (score >= SNAKE_BADGE_SCORE) {
				document.dispatchEvent(new CustomEvent('londerland:easteregg', { detail: { egg: 'snake_score', score: score } }));
			}
		}

		function turn(x, y) {
			const last = queued.length ? queued[queued.length - 1] : direction;
			if ((x !== 0 && last.x === 0) || (y !== 0 && last.y === 0)) {
				queued.push({ x: x, y: y });
				queued = queued.slice(-3);
			}
		}

		function close() {
			clearTimeout(timer);
			window.removeEventListener('keydown', onKey, true);
			window.removeEventListener('resize', fit);
			$root.remove();
			if (previousFocus && typeof previousFocus.focus === 'function') {
				previousFocus.focus();
			}
		}

		const keys = {
			ArrowUp: [0, -1], w: [0, -1], ArrowDown: [0, 1], s: [0, 1], ArrowLeft: [-1, 0], a: [-1, 0], ArrowRight: [1, 0], d: [1, 0],
		};
		// Captured before Londerland's own shortcuts ("s s", "d d" and the easter egg words), which would otherwise
		// fire while steering with WASD
		function onKey(event) {
			const key = event.key && event.key.length === 1 ? event.key.toLowerCase() : event.key;
			event.stopPropagation();
			if (key === 'Escape') {
				event.preventDefault();
				close();
			} else if (key === ' ' || key === 'Enter') {
				event.preventDefault();
				if (state !== 'playing') {
					start();
				}
			} else if (keys[key]) {
				event.preventDefault();
				if (state === 'playing') {
					turn(keys[key][0], keys[key][1]);
				} else {
					start();
				}
			}
		}

		let touch = null;
		$(canvas)
			.on('touchstart', function (event) {
				const point = event.originalEvent.touches[0];
				touch = { x: point.clientX, y: point.clientY };
			})
			.on('touchmove', function (event) {
				event.preventDefault();
				if (!touch || state !== 'playing') {
					return;
				}
				const point = event.originalEvent.touches[0];
				const dx = point.clientX - touch.x;
				const dy = point.clientY - touch.y;
				if (Math.max(Math.abs(dx), Math.abs(dy)) > 24) {
					if (Math.abs(dx) > Math.abs(dy)) {
						turn(dx > 0 ? 1 : -1, 0);
					} else {
						turn(0, dy > 0 ? 1 : -1);
					}
					touch = { x: point.clientX, y: point.clientY };
				}
			})
			.on('click', function () {
				if (state !== 'playing') {
					start();
				}
			});
		$root.on('click', '.egg-snake-close', close);
		$root.on('click', function (event) {
			if (event.target === this) {
				close();
			}
		});
		window.addEventListener('keydown', onKey, true);
		window.addEventListener('resize', fit);
		reset();
		fit();
		$root.find('.egg-snake-close').trigger('focus');
	}

	/* ===================== entry point ===================== */

	const EGGS = { konami: konami, party: party, duck: duck, sudo: sudo, roll: roll, teapot: teapot, answer: answer, xyzzy: xyzzy, matrix: matrix, snake: snake };

	window.londerlandEasterEggs = {
		run: function (egg) {
			if (EGGS[egg]) {
				try {
					EGGS[egg]();
				} catch (e) {
					console.error(e);
				}
			}
		},
	};

	const STYLE = `
.egg-canvas{position:fixed;inset:0;z-index:2100;pointer-events:none}
.egg-matrix{pointer-events:auto;cursor:pointer;background:rgba(0,0,0,.35)}
.egg-matrix-text{position:fixed;top:18%;left:50%;z-index:2101;transform:translateX(-50%);font:600 clamp(18px,4vw,34px) monospace;color:#3bff6b;text-shadow:0 0 12px #3bff6b;pointer-events:none;white-space:nowrap}
.egg-banner{position:fixed;top:50%;left:50%;z-index:2101;max-width:min(560px,calc(100vw - 32px));padding:22px 28px;border-radius:16px;background:rgba(20,22,28,.88);color:#fff;text-align:center;box-shadow:0 18px 50px rgba(0,0,0,.5);transform:translate(-50%,-50%);pointer-events:none;animation:egg-banner 3.6s ease forwards}
.egg-banner-emoji{font-size:64px;line-height:1.1}
.egg-banner-title{margin-top:6px;font-size:24px;font-weight:700}
.egg-banner-text{margin-top:6px;font-size:15px;opacity:.85}
@keyframes egg-banner{0%{opacity:0;transform:translate(-50%,-40%) scale(.9)}10%,80%{opacity:1;transform:translate(-50%,-50%) scale(1)}100%{opacity:0;transform:translate(-50%,-55%) scale(.98)}}
.egg-duck{position:fixed;bottom:12px;left:-90px;z-index:2101;display:flex;flex-direction:column;align-items:flex-start;gap:6px;pointer-events:none;animation:egg-duck-walk 9s linear forwards}
.egg-duck-bird{display:inline-block;font-size:54px;line-height:1;transform:scaleX(-1);animation:egg-duck-bob .35s ease-in-out infinite alternate}
.egg-duck-bubble{order:-1;width:max-content;max-width:220px;padding:8px 12px;border-radius:12px;background:#fff;color:#222;font-size:13px;box-shadow:0 6px 18px rgba(0,0,0,.3);opacity:0;animation:egg-duck-say 9s linear forwards}
@keyframes egg-duck-walk{to{left:calc(100vw + 20px)}}
@keyframes egg-duck-bob{to{transform:scaleX(-1) translateY(-5px) rotate(-6deg)}}
@keyframes egg-duck-say{0%,25%{opacity:0}30%,70%{opacity:1}75%,100%{opacity:0}}
.egg-roll{animation:egg-roll 1.2s ease-in-out}
@keyframes egg-roll{to{transform:rotate(360deg)}}
.egg-shake{animation:egg-shake .5s ease-in-out}
@keyframes egg-shake{20%,60%{transform:translateX(-8px)}40%,80%{transform:translateX(8px)}}
.egg-snake{position:fixed;inset:0;z-index:2050;display:flex;align-items:center;justify-content:center;padding:16px;background:rgba(0,0,0,.55)}
.egg-snake-box{display:flex;flex-direction:column;gap:10px;padding:14px;border-radius:14px;background:var(--bs-body-bg,#23252e);color:var(--bs-body-color,#e8eaed);border:1px solid rgba(127,127,127,.25);box-shadow:0 18px 50px rgba(0,0,0,.5)}
.egg-snake-head{display:flex;align-items:center;gap:12px}
.egg-snake-score{flex:1;text-align:right;font-variant-numeric:tabular-nums;opacity:.85}
.egg-snake-close{border:0;background:transparent;color:inherit;font-size:18px;cursor:pointer}
.egg-snake-board{display:block;border-radius:8px;touch-action:none;cursor:pointer}
.egg-snake-help{max-width:480px;font-size:13px;opacity:.75;text-align:center}
@media (prefers-reduced-motion: reduce){.egg-duck-bird{animation:none}.egg-banner{animation-duration:4s}}
`;
})();
