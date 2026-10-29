/*
 * Achievements: reports small events (visit, tab opened, ...) for the logged in user, shows a message when
 * a badge is unlocked and adds My Stats to the user menu. Talks only to api/v2/plugins/achievements/*.
 */
(function () {
	'use strict';

	const API = 'api/v2/plugins/achievements/';
	const WEEKDAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
	// the start tab that opens by itself when the page loads is not counted as "opened"
	const IGNORE_TABS_FOR_MS = 4000;
	let queue = $.Deferred().resolve().promise();
	let startedAt = 0;
	let active = false;

	$('body').arrive('#activeInfo', { onceOnly: true }, function () {
		achievementsLaunch();
	});

	function achievementsLaunch() {
		const plugins = activeInfo.plugins || {};
		const includes = plugins.includes || {};
		const user = activeInfo.user || {};
		const group = parseInt(user.groupID, 10);
		if (plugins['ACHIEVEMENTS-enabled'] !== true || !user.loggedin || isNaN(group) || group >= 999) {
			return;
		}
		// the server checks this too; here it only decides whether anything is shown
		const groups = String(includes['ACHIEVEMENTS-groups-include'] ?? 'auto').trim();
		if (groups !== 'auto' && groups !== '' && !groups.split(',').map((id) => parseInt(id, 10)).includes(group)) {
			return;
		}
		active = true;
		startedAt = Date.now();
		buildDom();
		hookFunctions();
		track('visit');
	}

	/* ===================== helpers ===================== */

	function t(text) {
		return window.lang && typeof window.lang.translate === 'function' ? window.lang.translate(text) : text;
	}

	function esc(value) {
		return $('<div>').text(value == null ? '' : String(value)).html();
	}

	function number(value) {
		return (parseInt(value, 10) || 0).toLocaleString();
	}

	function days(value) {
		return number(value) + ' ' + t(parseInt(value, 10) === 1 ? 'day' : 'days');
	}

	function localTime() {
		const now = new Date();
		const pad = (n) => String(n).padStart(2, '0');
		return { date: now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate()), hour: now.getHours() };
	}

	// ISO times from the server are UTC; a bare YYYY-MM-DD is a local day
	function formatDate(value, withTime) {
		if (!value) {
			return '-';
		}
		const day = String(value).match(/^(\d{4})-(\d{2})-(\d{2})$/);
		const date = day ? new Date(+day[1], +day[2] - 1, +day[3]) : new Date(value);
		if (isNaN(date.getTime())) {
			return '-';
		}
		return withTime ? date.toLocaleString(undefined, { dateStyle: 'long', timeStyle: 'short' }) : date.toLocaleDateString(undefined, { dateStyle: 'long' });
	}

	/* ===================== tracking ===================== */

	// One request at a time: the API helper cancels an earlier request to the same address
	function track(event, extra) {
		if (!active) {
			return;
		}
		const body = Object.assign({ event: event, local: localTime() }, extra || {});
		queue = queue.then(function () {
			const done = $.Deferred();
			londerlandAPI2('POST', API + 'track', body)
				.done(function (data) {
					announceAll((data.response && data.response.data) || {});
				})
				.always(function () {
					done.resolve();
				});
			return done.promise();
		});
	}

	function xpOn() {
		return (activeInfo.plugins.includes || {})['ACHIEVEMENTS-xp-include'] !== false;
	}

	// New badges, finished daily quests and a new level, from a track or My Stats response
	function announceAll(result) {
		(result.unlocked || []).forEach(announce);
		(result.quests || []).forEach(announceQuest);
		if (result.levelUp) {
			announceLevel(result.levelUp);
		}
	}

	function notifyOn() {
		return activeInfo.plugins.includes['ACHIEVEMENTS-notify-include'] !== false;
	}

	function announceQuest(quest) {
		$('.achievements-menu-dot').removeClass('hidden');
		if (!notifyOn()) {
			return;
		}
		message(
			quest.icon + ' ' + t(quest.type === 'bonus' ? 'Bonus' : 'Quest complete'),
			'<strong>' + esc(t(quest.name)) + '</strong><br>+' + number(quest.xp) + ' XP',
			activeInfo.settings.notifications.position,
			'#FFF',
			'info',
			'8000'
		);
	}

	function announceLevel(levelUp) {
		$('.achievements-menu-dot').removeClass('hidden');
		if (!notifyOn()) {
			return;
		}
		message(
			'🎉 ' + t('Level up!'),
			'<strong>' + t('Level') + ' ' + number(levelUp.level) + '</strong> · ' + esc(t(levelUp.title)),
			activeInfo.settings.notifications.position,
			'#FFF',
			'success',
			'10000'
		);
	}

	function announce(badge) {
		if (!notifyOn()) {
			return;
		}
		message(
			badge.icon + ' ' + t('Badge unlocked'),
			'<strong>' + esc(t(badge.name)) + '</strong><br>' + esc(t(badge.description)) + (xpOn() && badge.xp ? '<br>+' + number(badge.xp) + ' XP' : ''),
			activeInfo.settings.notifications.position,
			'#FFF',
			'success',
			'10000'
		);
		$('.achievements-menu-dot').removeClass('hidden');
	}

	// Wrap a few global Londerland functions so using them counts, without changing what they do
	function wrap(name, before) {
		const original = window[name];
		if (typeof original !== 'function') {
			return;
		}
		window[name] = function () {
			try {
				before.apply(this, arguments);
			} catch (e) {
				console.error(e);
			}
			return original.apply(this, arguments);
		};
	}

	function hookFunctions() {
		wrap('switchTab', function (id) {
			if (Date.now() - startedAt < IGNORE_TABS_FOR_MS) {
				return;
			}
			const tab = typeof findTab === 'function' ? findTab(id) : null;
			if (tab && parseInt(tab.id, 10) > 0) {
				track('tab', { tab: parseInt(tab.id, 10) });
			}
		});
		// Split view (Ctrl+Alt+click a tab) has its own function, which returns false when the tab can't be shown there
		const openSplit = window.openSplitTab;
		if (typeof openSplit === 'function') {
			window.openSplitTab = function () {
				const result = openSplit.apply(this, arguments);
				if (result !== false) {
					try {
						track('split');
					} catch (e) {
						console.error(e);
					}
				}
				return result;
			};
		}
		// A tab in a new browser window: the top bar button, or Ctrl/middle-click on a tab
		wrap('openInNewBrowserTab', function () {
			track('popout');
		});
		wrap('popTab', function () {
			track('popout');
		});
		// Reloading: the top bar button, or Shift+click on a tab
		wrap('reloadCurrentTab', function () {
			track('reload');
		});
		wrap('reloadTab', function () {
			track('reload');
		});
		// Marathon: this page open for an hour. Measured on the clock instead of counting timer ticks, as browsers slow
		// down or pause timers in background tabs and while the computer sleeps
		const openedAt = Date.now();
		const marathon = setInterval(function () {
			if (Date.now() - openedAt >= 3600000) {
				clearInterval(marathon);
				track('marathon');
			}
		}, 60000);
		// The Bookmarks plugin announces every bookmark that is opened, the command palette (Ctrl+K) every time it opens
		$(document).on('londerland:bookmark-open', function () {
			track('bookmark');
		});
		$(document).on('londerland:palette-open', function () {
			track('palette');
		});
		// Full screen, however it was entered: Londerland's button or its "f f" shortcut (fullscreenchange), or the
		// browser's own full screen (F11), which only shows as a window as big as the screen
		$(document).on('fullscreenchange.achievements webkitfullscreenchange.achievements', function () {
			if (document.fullscreenElement || document.webkitFullscreenElement) {
				track('fullscreen');
			}
		});
		const screenSized = () => window.innerWidth >= screen.width && window.innerHeight >= screen.height;
		let wasScreenSized = screenSized();
		let resizeTimer = null;
		$(window).on('resize.achievements', function () {
			clearTimeout(resizeTimer);
			resizeTimer = setTimeout(function () {
				const now = screenSized();
				if (now && !wasScreenSized && !document.fullscreenElement && !document.webkitFullscreenElement) {
					track('fullscreen');
				}
				wasScreenSized = now;
			}, 300);
		});
		// Lock Screen (the user menu, or the automatic lock after idle time)
		wrap('lock', function () {
			track('lock');
		});
		// AI Chat: after a new chat or message the server counts again (only up, see _achCountAiChat)
		if (typeof window.fetch === 'function') {
			const originalFetch = window.fetch;
			window.fetch = function (input, init) {
				const result = originalFetch.apply(this, arguments);
				try {
					const url = typeof input === 'string' ? input : (input && input.url) || '';
					const method = String((init && init.method) || (input && input.method) || 'GET').toUpperCase();
					if (method === 'POST' && /plugins\/aichat\/chats(\/\d+\/stream)?(\?|$)/.test(url)) {
						result.then(function (response) {
							if (response.ok) {
								track('ai');
							}
						}, function () {});
					}
				} catch (e) {
					console.error(e);
				}
				return result;
			};
		}
		// The easter eggs are listened for by Londerland itself (js/custom.js), which announces every find
		const EGG_EVENTS = { konami: 'konami', duck: 'logo', matrix: 'matrix', sudo: 'sudo', roll: 'roll', teapot: 'teapot', answer: 'answer', xyzzy: 'xyzzy', party: 'party' };
		document.addEventListener('londerland:easteregg', function (event) {
			const detail = event.detail || {};
			if (detail.egg === 'snake_score') {
				track('snake');
			} else if (EGG_EVENTS[detail.egg]) {
				track(EGG_EVENTS[detail.egg]);
			}
		});
	}

	/* ===================== My Stats page ===================== */

	function buildDom() {
		const menuItem = `<li><a href="javascript:void(0)" class="achievements-open"><i class="fa fa-trophy fa-fw"></i> <span lang="en">My Stats</span><span class="achievements-menu-dot hidden"></span></a></li>`;
		$('.append-menu').after(menuItem);
		$('.plugin-listing').append(`<div id="container-plugin-achievements" class="plugin-container hidden"><div class="achievements-page"></div></div>`);
		pageLoad();
		$(document).on('click', '.achievements-open', function () {
			$('.achievements-menu-dot').addClass('hidden');
			switchToPlugin('achievements');
			loadStats();
		});
		$(document).on('click', '.achievements-refresh', loadStats);
		$(document).on('click', '.achievements-filter button', function () {
			const filter = $(this).data('filter');
			$(this).addClass('active').siblings().removeClass('active');
			$('.achievements-badge').each(function () {
				const unlocked = $(this).hasClass('is-unlocked');
				$(this).toggleClass('hidden', (filter === 'unlocked' && !unlocked) || (filter === 'locked' && unlocked));
			});
		});
	}

	function loadStats() {
		const $page = $('#container-plugin-achievements .achievements-page');
		if (!$page.children().length) {
			$page.html('<div class="achievements-loading"><i class="fa fa-spinner fa-pulse fa-2x"></i></div>');
		}
		const local = localTime();
		londerlandAPI2('GET', API + 'stats?' + $.param({ date: local.date, hour: local.hour }))
			.done(function (data) {
				$page.html(renderPage(data.response.data));
				pageLoad();
				announceAll(data.response.data.announce || {});
			})
			.fail(function (xhr) {
				LonderlandApiError(xhr, 'My Stats');
			});
	}

	function renderPage(data) {
		const user = activeInfo.user || {};
		const account = data.account;
		const activity = data.activity;
		const badges = data.achievements || [];
		const unlocked = badges.filter((badge) => badge.unlocked).length;
		const percent = badges.length ? Math.round((unlocked / badges.length) * 100) : 0;
		return `
			<div class="achievements-header">
				<img class="achievements-avatar" src="${esc(user.image)}" alt="">
				<div class="achievements-who">
					<h2>${esc(account.username)}</h2>
					<div class="achievements-muted">${esc(account.group)}${account.email ? ' · ' + esc(account.email) : ''}</div>
					<div class="achievements-muted"><span lang="en">Member since</span> ${formatDate(account.registered)}</div>
				</div>
				${data.level ? renderLevel(data.level) : ''}
				<div class="achievements-score" title="${unlocked} / ${badges.length}">
					<div class="achievements-score-value">${unlocked}<small>/${badges.length}</small></div>
					<div class="achievements-muted" lang="en">badges</div>
					<div class="achievements-progress"><span style="width:${percent}%"></span></div>
				</div>
				<button type="button" class="btn btn-sm btn-secondary achievements-refresh" title="${esc(t('Refresh'))}"><i class="ti-reload"></i></button>
			</div>
			${data.quests ? renderQuests(data.quests, data.questsBonus, data.questsDone) : ''}
			<h3 class="achievements-title" lang="en">Account</h3>
			<div class="achievements-tiles">
				${tile('🗓️', t('Account age'), days(account.accountDays))}
				${tile('🔐', t('Sign-in method'), esc(signInName(account.signIn)))}
				${tile('💻', t('Signed-in devices'), number(account.sessions))}
				${tile('⏮️', t('Previous visit'), formatDate(activity.previousVisit, true))}
			</div>
			<h3 class="achievements-title" lang="en">Activity</h3>
			<div class="achievements-tiles">
				${tile('👣', t('Visits'), number(activity.visits))}
				${tile('📅', t('Active days'), number(activity.days))}
				${tile('🔥', t('Current streak'), days(activity.streak))}
				${tile('🏅', t('Best streak'), days(activity.bestStreak))}
				${tile('🗂️', t('Tabs opened'), number(activity.tabs))}
				${tile('🔍', t('Different tabs'), number(activity.tabsUnique))}
				${activity.aiEnabled ? tile('🤖', t('AI chats'), number(activity.aiChats)) : ''}
				${activity.aiEnabled ? tile('💬', t('AI messages'), number(activity.aiMessages)) : ''}
			</div>
			<p class="achievements-muted achievements-note">${activity.firstDay ? t('Counting since') + ' ' + formatDate(activity.firstDay) + '.' : ''}</p>
			<div class="achievements-charts">
				${barChart(t('When you are active (hour of the day)'), activity.hours, (i) => String(i).padStart(2, '0'), (i) => String(i).padStart(2, '0') + ':00 - ' + String(i).padStart(2, '0') + ':59')}
				${barChart(t('When you are active (day of the week)'), activity.weekdays, (i) => t(WEEKDAYS[i]), (i) => t(WEEKDAYS[i]))}
				${topTabs(activity.topTabs)}
			</div>
			<div class="achievements-badges-head">
				<h3 class="achievements-title" lang="en">Badges</h3>
				<div class="btn-group btn-group-sm achievements-filter" role="group">
					<button type="button" class="btn btn-secondary active" data-filter="all" lang="en">All</button>
					<button type="button" class="btn btn-secondary" data-filter="unlocked" lang="en">Unlocked</button>
					<button type="button" class="btn btn-secondary" data-filter="locked" lang="en">Locked</button>
				</div>
			</div>
			${renderBadges(badges)}
			${data.leaderboard ? renderLeaderboard(data.leaderboard, badges.length) : ''}
		`;
	}

	function renderLevel(level) {
		const percent = level.levelSize ? Math.min(100, Math.round((level.levelXp / level.levelSize) * 100)) : 100;
		return `
			<div class="achievements-level" title="${number(level.levelXp)} / ${number(level.levelSize)} XP">
				<div class="achievements-level-badge"><small lang="en">Level</small>${number(level.level)}</div>
				<div class="achievements-level-text">
					<div class="achievements-level-title">${esc(t(level.title))}</div>
					<div class="achievements-progress achievements-xp-bar"><span style="width:${percent}%"></span></div>
					<div class="achievements-muted">${number(level.xp)} XP · ${number(level.nextLevelXp - level.xp)} ${esc(t('XP to level'))} ${number(level.level + 1)}</div>
				</div>
			</div>`;
	}

	// Time left until the next local midnight, when new quests start
	function untilMidnight() {
		const now = new Date();
		const midnight = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1);
		const minutes = Math.max(1, Math.round((midnight - now) / 60000));
		const hours = Math.floor(minutes / 60);
		return hours ? hours + ' h ' + (minutes % 60) + ' min' : minutes + ' min';
	}

	function renderQuests(quests, bonus, total) {
		if (!quests.length) {
			return '';
		}
		const done = quests.filter((quest) => quest.done).length;
		const cards = quests
			.map((quest) => {
				const percent = Math.round((quest.progress / quest.target) * 100);
				return `
				<div class="achievements-quest ${quest.done ? 'is-done' : ''}">
					<div class="achievements-quest-icon">${quest.done ? '✅' : quest.icon}</div>
					<div class="achievements-quest-text">
						<div class="achievements-quest-name">${esc(t(quest.name))} <span class="achievements-quest-xp">+${number(quest.xp)} XP</span></div>
						<div class="achievements-muted">${esc(t(quest.description))}</div>
						<div class="achievements-progress" title="${number(quest.progress)} / ${number(quest.target)}"><span style="width:${percent}%"></span></div>
						<div class="achievements-badge-date">${number(quest.progress)} / ${number(quest.target)}</div>
					</div>
				</div>`;
			})
			.join('');
		return `
			<div class="achievements-badges-head">
				<h3 class="achievements-title" lang="en">Daily quests</h3>
				<span class="achievements-muted">${number(done)} / ${quests.length} · ${esc(t('New quests in'))} ${untilMidnight()}</span>
			</div>
			<div class="achievements-quests">${cards}</div>
			<p class="achievements-muted achievements-note">${bonus && bonus.done ? '🌟 ' + esc(t('All done for today, bonus collected!')) : '🌟 ' + esc(t('Finish all three for a bonus of')) + ' ' + number(bonus ? bonus.xp : 0) + ' XP.'} ${esc(t('Quests completed so far'))}: ${number(total)}.</p>`;
	}

	function signInName(signIn) {
		signIn = signIn || {};
		if (signIn.method === 'oidc') {
			return 'OpenID Connect' + (signIn.provider ? ' (' + signIn.provider + ')' : '');
		}
		return t('Standard') + (signIn.twoFactor ? ' + ' + t('two-factor') : '');
	}

	function tile(icon, label, value) {
		return `<div class="achievements-tile"><div class="achievements-tile-icon">${icon}</div><div><div class="achievements-tile-value">${value}</div><div class="achievements-muted">${esc(label)}</div></div></div>`;
	}

	// One series, so one colour and no legend; the exact number is in the tooltip
	function barChart(title, values, label, tooltip) {
		const max = Math.max(1, ...values);
		const bars = values
			.map((value, i) => {
				const height = value ? Math.max(4, Math.round((value / max) * 100)) : 0;
				return `<div class="achievements-bar" title="${esc(tooltip(i))}: ${number(value)}"><span style="height:${height}%"></span><small>${esc(label(i))}</small></div>`;
			})
			.join('');
		return `<div class="achievements-chart"><h4>${esc(title)}</h4><div class="achievements-bars achievements-bars-${values.length}">${bars}</div></div>`;
	}

	function topTabs(tabs) {
		if (!tabs || !tabs.length) {
			return `<div class="achievements-chart"><h4 lang="en">Most opened tabs</h4><p class="achievements-muted" lang="en">Open a few tabs and they show up here.</p></div>`;
		}
		const max = Math.max(1, ...tabs.map((tab) => tab.count));
		const rows = tabs
			.map(
				(tab) => `
				<div class="achievements-toptab" title="${esc(tab.name)}: ${number(tab.count)}">
					<span class="achievements-toptab-name">${esc(tab.name)}</span>
					<span class="achievements-toptab-bar"><span style="width:${Math.max(3, Math.round((tab.count / max) * 100))}%"></span></span>
					<span class="achievements-toptab-count">${number(tab.count)}</span>
				</div>`
			)
			.join('');
		return `<div class="achievements-chart"><h4 lang="en">Most opened tabs</h4>${rows}</div>`;
	}

	function renderBadges(badges) {
		const categories = [];
		badges.forEach((badge) => {
			if (!categories.includes(badge.category)) {
				categories.push(badge.category);
			}
		});
		return categories
			.map((category) => {
				const items = badges
					.filter((badge) => badge.category === category)
					.map((badge) => {
						const percent = Math.round((badge.progress / badge.target) * 100);
						const footer = badge.unlocked
							? `<div class="achievements-badge-date">${esc(t('Unlocked'))} ${formatDate(badge.unlocked)}</div>`
							: badge.target > 1
								? `<div class="achievements-progress" title="${number(badge.progress)} / ${number(badge.target)}"><span style="width:${percent}%"></span></div><div class="achievements-badge-date">${number(badge.progress)} / ${number(badge.target)}</div>`
								: '';
						const xp = xpOn() && badge.xp && !(badge.secret && !badge.unlocked) ? `<span class="achievements-badge-xp">+${number(badge.xp)} XP</span>` : '';
						return `
						<div class="achievements-badge ${badge.unlocked ? 'is-unlocked' : 'is-locked'}">
							<div class="achievements-badge-icon">${badge.icon}</div>
							<div class="achievements-badge-text">
								<div class="achievements-badge-name">${esc(t(badge.name))} ${xp}</div>
								<div class="achievements-muted">${esc(t(badge.description))}</div>
								${footer}
							</div>
						</div>`;
					})
					.join('');
				return `<h4 class="achievements-category">${esc(t(category))}</h4><div class="achievements-badges">${items}</div>`;
			})
			.join('');
	}

	function renderLeaderboard(board, total) {
		const me = (activeInfo.user || {}).username;
		const rows = board
			.map(
				(entry, i) => `
				<tr class="${entry.username === me ? 'achievements-me' : ''}">
					<td>${['🥇', '🥈', '🥉'][i] || i + 1}</td>
					<td><img class="achievements-board-avatar" src="${esc(entry.image)}" alt=""> ${esc(entry.username)}</td>
					${xpOn() ? `<td>${number(entry.level)} <span class="achievements-muted">(${number(entry.xp)} XP)</span></td>` : ''}
					<td>${number(entry.badges)} / ${number(total)}</td>
					<td>${number(entry.days)}</td>
				</tr>`
			)
			.join('');
		return `
			<h3 class="achievements-title" lang="en">Leaderboard</h3>
			<div class="table-responsive"><table class="table achievements-board">
				<thead><tr><th>#</th><th lang="en">User</th>${xpOn() ? '<th lang="en">Level</th>' : ''}<th lang="en">Badges</th><th lang="en">Active days</th></tr></thead>
				<tbody>${rows}</tbody>
			</table></div>`;
	}
})();
