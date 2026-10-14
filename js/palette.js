/*
 * Command palette (Ctrl+K / Cmd+K, or the search button in the top bar): type to find a tab, a bookmark, a settings
 * page or an action, and press Enter. Shift+Enter opens a tab or bookmark in a new browser tab.
 * Not part of the page load: custom.js fetches this file the first time the palette is opened (londerlandLoadLibrary).
 */
(function () {
	'use strict';

	const RECENT_KEY = 'londerland-palette-recent';
	const MAX_RESULTS = 50;
	let $root = null;
	let items = [];
	let results = [];
	let selected = 0;
	let bookmarksLoaded = false;
	let previousFocus = null;

	/* ===================== helpers ===================== */

	function t(text) {
		return window.lang && typeof window.lang.translate === 'function' ? window.lang.translate(text) : text;
	}

	function esc(value) {
		return $('<div>').text(value == null ? '' : String(value)).html();
	}

	function storage(value) {
		try {
			if (value === undefined) {
				const list = JSON.parse(window.localStorage.getItem(RECENT_KEY) || '[]');
				return Array.isArray(list) ? list : [];
			}
			window.localStorage.setItem(RECENT_KEY, JSON.stringify(value));
		} catch (e) {
			return [];
		}
		return value;
	}

	function user() {
		return (window.activeInfo && activeInfo.user) || {};
	}

	function groupId() {
		const id = parseInt(user().groupID, 10);
		return isNaN(id) ? 999 : id;
	}

	function pluginOn(prefix) {
		return ((window.activeInfo && activeInfo.plugins) || {})[prefix + '-enabled'] === true;
	}

	// Waits for an element that a page builds after it loads (the settings page and its sub-tabs)
	function whenPresent(selector, timeout) {
		return new Promise(function (resolve, reject) {
			const started = Date.now();
			(function check() {
				const element = document.querySelector(selector);
				if (element) {
					resolve(element);
				} else if (Date.now() - started > (timeout || 8000)) {
					reject(new Error('Not found: ' + selector));
				} else {
					setTimeout(check, 100);
				}
			})();
		});
	}

	/* ===================== items ===================== */

	function tabIcon(tab) {
		try {
			return iconPrefix(tab.image);
		} catch (e) {
			return '<i class="fa fa-folder fa-fw"></i>';
		}
	}

	function tabItems() {
		const categories = {};
		((window.activeInfo && activeInfo.categories) || []).forEach(function (category) {
			categories[category.category_id] = category.category;
		});
		return ((window.activeInfo && activeInfo.tabs) || [])
			.filter(function (tab) {
				return tab && tab.name && tab.enabled !== false && tab.enabled !== 0 && tab.enabled !== '0' && parseInt(tab.id, 10) > 0;
			})
			.map(function (tab) {
				const category = categories[tab.category_id];
				return {
					key: 'tab:' + tab.id,
					kind: t('Tab'),
					title: tab.name,
					subtitle: category && category !== 'Unsorted' ? category : '',
					icon: tabIcon(tab),
					run: function (event) {
						tabActions(event && event.shiftKey ? { ctrlKey: true, which: 1 } : { which: 1 }, tab.id);
					},
				};
			});
	}

	function command(key, title, icon, run, keywords, available) {
		return { key: 'cmd:' + key, kind: t('Action'), title: t(title), subtitle: '', icon: '<i class="' + icon + ' fa-fw"></i>', keywords: keywords || '', run: run, available: available };
	}

	function settingsItem(key, main, sub, title, keywords, level) {
		return {
			key: 'settings:' + key,
			kind: t('Settings'),
			title: t(title),
			subtitle: '',
			icon: '<i class="ti-settings fa-fw"></i>',
			keywords: keywords || '',
			available: function () {
				return groupId() <= (level === undefined ? 1 : level);
			},
			run: function () {
				goToSettings(main, sub);
			},
		};
	}

	function goToSettings(main, sub) {
		openSettings();
		whenPresent('#settings-main-' + main + '-anchor a')
			.then(function (link) {
				link.click();
				return sub ? whenPresent('#' + sub) : null;
			})
			.then(function (link) {
				if (link) {
					link.click();
				}
			})
			.catch(function () {});
	}

	function commandItems() {
		const loggedIn = user().loggedin === true;
		return [
			command('reload', 'Reload the open tab', 'ti-reload', function () { reloadCurrentTab(); }, 'refresh'),
			command('close', 'Close the open tab', 'ti-close', function (event) { closeCurrentTab(event || {}); }),
			command('newtab', 'Open the open tab in a new browser tab', 'ti-new-window', function () { openInNewBrowserTab(); }, 'pop out window'),
			command('fullscreen', 'Full screen', 'ti-fullscreen', function () { toggleFullScreen(); }, 'kiosk'),
			command('account', 'Account settings', 'ti-user', function () { $('.inline-popups[href="#account-area"]').first().trigger('click'); }, 'profile password two-factor 2fa', function () { return loggedIn && $('.inline-popups[href="#account-area"]').length > 0; }),
			command('stats', 'My Stats', 'fa fa-trophy', function () { $('.achievements-open').first().trigger('click'); }, 'achievements badges level xp quests', function () { return $('.achievements-open').length > 0; }),
			command('install', 'Install app', 'fa fa-download', function () { installApp(); }, 'pwa home screen', function () { return !!window.londerlandInstallPrompt; }),
			command('lock', 'Lock screen', 'ti-lock', function () { lock(); }, '', function () { return loggedIn; }),
			command('logout', 'Log out', 'fa fa-sign-out', function () { logout(); }, 'sign out', function () { return loggedIn; }),
			command('login', 'Log in', 'fa fa-sign-in', function () { $('.show-login').first().trigger('click'); }, 'sign in register', function () { return !loggedIn; }),
			settingsItem('tabs', 'tab-editor', 'settings-tab-editor-tabs-anchor', 'Tab Editor: Tabs', 'add tab edit'),
			settingsItem('categories', 'tab-editor', 'settings-tab-editor-categories-anchor', 'Tab Editor: Categories'),
			settingsItem('appearance', 'customize', 'settings-customize-appearance-anchor', 'Customize: Appearance', 'theme colours colors logo title css javascript login favicon'),
			settingsItem('website', 'customize', 'settings-customize-site-anchor', 'Customize: Website', 'announcement banner seo robots sitemap pwa app install'),
			settingsItem('users', 'user-management', 'settings-user-manage-users-anchor', 'User Management: Users', 'accounts'),
			settingsItem('groups', 'user-management', 'settings-user-manage-groups-anchor', 'User Management: Groups'),
			settingsItem('images', 'image-manager', null, 'Image Manager', 'upload pictures icons'),
			settingsItem('plugins', 'plugins', 'settings-plugins-enabled-anchor', 'Plugins: Active'),
			settingsItem('plugins-inactive', 'plugins', 'settings-plugins-disabled-anchor', 'Plugins: Inactive', 'enable install'),
			settingsItem('main', 'system-settings', 'settings-settings-main-anchor', 'System Settings: Main', 'authentication login api key security'),
			settingsItem('sso', 'system-settings', 'settings-settings-sso-anchor', 'System Settings: SSO', 'single sign-on oidc'),
			settingsItem('logs', 'system-settings', 'settings-settings-logs-anchor', 'System Settings: Logs'),
			settingsItem('backup', 'system-settings', 'settings-settings-backup-anchor', 'System Settings: Backup'),
			settingsItem('audit', 'system-settings', 'settings-settings-audit-anchor', 'System Settings: Audit Log', 'who changed history security', 0),
			settingsItem('about', 'system-settings', 'settings-settings-about-anchor', 'System Settings: About', 'version'),
		];
	}

	// Bookmarks the user's group may see, fetched once, the first time the palette opens
	function loadBookmarks() {
		if (bookmarksLoaded || !pluginOn('BOOKMARK')) {
			return;
		}
		bookmarksLoaded = true;
		londerlandAPI2('GET', 'api/v2/plugins/bookmark/list').done(function (data) {
			const list = (data.response && data.response.data) || {};
			const categories = {};
			(list.categories || []).forEach(function (category) {
				categories[category.categoryId] = category.name;
			});
			(list.bookmarks || []).forEach(function (bookmark) {
				if (!bookmark.url) {
					return;
				}
				items.push({
					key: 'bookmark:' + bookmark.id,
					kind: t('Bookmark'),
					title: bookmark.name,
					subtitle: categories[bookmark.categoryId] || hostOf(bookmark.url),
					keywords: bookmark.url,
					icon: '<i class="fa fa-bookmark fa-fw"></i>',
					run: function () {
						window.open(bookmark.url, '_blank', 'noopener');
						$(document).trigger('londerland:bookmark-open');
					},
				});
			});
			if ($root && $root.is(':visible')) {
				search();
			}
		});
	}

	function hostOf(url) {
		try {
			return new URL(url, location.href).host;
		} catch (e) {
			return '';
		}
	}

	function buildItems() {
		items = tabItems().concat(commandItems());
		bookmarksLoaded = false;
		loadBookmarks();
	}

	/* ===================== matching ===================== */

	// Higher is better; 0 = no match. Whole text, start of a word, anywhere, then the letters in order.
	function score(item, query) {
		const title = String(item.title).toLowerCase();
		const rest = (String(item.subtitle || '') + ' ' + String(item.keywords || '') + ' ' + String(item.kind || '')).toLowerCase();
		if (title === query) {
			return 1000;
		}
		if (title.startsWith(query)) {
			return 800 - title.length;
		}
		const wordStart = title.split(/[\s:\-_/.]+/).some(function (word) {
			return word.startsWith(query);
		});
		if (wordStart) {
			return 600 - title.length;
		}
		if (title.includes(query)) {
			return 400 - title.indexOf(query);
		}
		if (rest.includes(query)) {
			return 200;
		}
		let at = 0;
		for (const letter of query) {
			at = title.indexOf(letter, at);
			if (at === -1) {
				return 0;
			}
			at++;
		}
		return 100 - title.length;
	}

	function available(item) {
		return typeof item.available !== 'function' || item.available();
	}

	function search() {
		const query = String($root.find('.palette-input').val() || '').trim().toLowerCase();
		const usable = items.filter(available);
		if (query === '') {
			const recent = storage()
				.map(function (key) {
					return usable.find(function (item) {
						return item.key === key;
					});
				})
				.filter(Boolean);
			const recentKeys = recent.map(function (item) {
				return item.key;
			});
			results = recent
				.map(function (item) {
					return Object.assign({}, item, { recent: true });
				})
				.concat(
					usable.filter(function (item) {
						return recentKeys.indexOf(item.key) === -1;
					})
				)
				.slice(0, MAX_RESULTS);
		} else {
			// Each word has to match; the item's score is that of its weakest word
			const words = query.split(/\s+/);
			results = usable
				.map(function (item) {
					const scores = words.map(function (word) {
						return score(item, word);
					});
					return { item: item, score: Math.min.apply(null, scores) + (words.length > 1 ? score(item, query) / 10 : 0) };
				})
				.filter(function (entry) {
					return entry.score > 0;
				})
				.sort(function (a, b) {
					return b.score - a.score;
				})
				.slice(0, MAX_RESULTS)
				.map(function (entry) {
					return entry.item;
				});
		}
		selected = 0;
		render();
	}

	/* ===================== drawing ===================== */

	function render() {
		const $list = $root.find('.palette-list');
		if (!results.length) {
			$list.html('<li class="palette-empty">' + esc(t('Nothing found')) + '</li>');
			return;
		}
		$list.html(
			results
				.map(function (item, index) {
					return (
						'<li class="palette-item' + (index === selected ? ' is-selected' : '') + '" role="option" id="palette-item-' + index + '" data-index="' + index + '" aria-selected="' + (index === selected) + '">' +
						'<span class="palette-icon">' + item.icon + '</span>' +
						'<span class="palette-text"><span class="palette-title">' + esc(item.title) + '</span>' +
						(item.subtitle ? '<span class="palette-subtitle">' + esc(item.subtitle) + '</span>' : '') +
						'</span>' +
						'<span class="palette-kind">' + esc(item.recent ? t('Recent') : item.kind) + '</span>' +
						'</li>'
					);
				})
				.join('')
		);
		$root.find('.palette-input').attr('aria-activedescendant', 'palette-item-' + selected);
	}

	function move(by) {
		if (!results.length) {
			return;
		}
		selected = (selected + by + results.length) % results.length;
		const $items = $root.find('.palette-item');
		$items.removeClass('is-selected').attr('aria-selected', 'false');
		const $current = $items.eq(selected).addClass('is-selected').attr('aria-selected', 'true');
		$root.find('.palette-input').attr('aria-activedescendant', 'palette-item-' + selected);
		const element = $current.get(0);
		if (element) {
			element.scrollIntoView({ block: 'nearest' });
		}
	}

	function choose(index, event) {
		const item = results[index];
		if (!item) {
			return;
		}
		storage(
			[item.key]
				.concat(
					storage().filter(function (key) {
						return key !== item.key;
					})
				)
				.slice(0, 8)
		);
		close(false);
		try {
			item.run(event);
		} catch (e) {
			console.error(e);
		}
	}

	const STYLE = `
.palette-backdrop{position:fixed;inset:0;z-index:2000;display:flex;align-items:flex-start;justify-content:center;padding:12vh 16px 16px;background:rgba(0,0,0,.45)}
.palette-backdrop.hidden{display:none}
.palette{width:100%;max-width:620px;max-height:70vh;display:flex;flex-direction:column;overflow:hidden;border-radius:12px;background:var(--bs-body-bg,#2b2f36);color:var(--bs-body-color,#e8eaed);box-shadow:0 18px 50px rgba(0,0,0,.45);border:1px solid rgba(127,127,127,.25)}
.palette-search{display:flex;align-items:center;gap:10px;padding:12px 16px;border-bottom:1px solid rgba(127,127,127,.25)}
.palette-search i{opacity:.6}
.palette-input{flex:1;min-width:0;border:0;outline:0;background:transparent;color:inherit;font-size:17px}
.palette-list{list-style:none;margin:0;padding:6px;overflow-y:auto}
.palette-item{display:flex;align-items:center;gap:12px;padding:8px 10px;border-radius:8px;cursor:pointer}
.palette-item.is-selected{background:rgba(127,127,127,.22)}
.palette-icon{flex:none;width:28px;text-align:center;font-size:16px}
.palette-icon img{width:20px;height:20px;object-fit:contain}
.palette-text{flex:1;min-width:0;display:flex;flex-direction:column}
.palette-title,.palette-subtitle{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.palette-subtitle{font-size:12px;opacity:.65}
.palette-kind{flex:none;font-size:11px;opacity:.6;text-transform:uppercase;letter-spacing:.04em}
.palette-empty{padding:16px;text-align:center;opacity:.7}
.palette-help{display:flex;flex-wrap:wrap;gap:16px;padding:8px 16px;border-top:1px solid rgba(127,127,127,.25);font-size:12px;opacity:.7}
.palette kbd{display:inline-block;margin:0 2px;padding:1px 5px;border-radius:4px;font-size:11px;line-height:1.4;background:rgba(127,127,127,.25);color:inherit;box-shadow:none}
@media (max-width:767px){.palette-backdrop{padding-top:56px}.palette-help{display:none}}
`;

	function build() {
		$('<style id="palette-style">').text(STYLE).appendTo('head');
		$root = $(
			'<div class="palette-backdrop hidden" role="presentation">' +
				'<div class="palette" role="dialog" aria-modal="true" aria-label="' + esc(t('Search')) + '">' +
				'<div class="palette-search"><i class="ti-search"></i>' +
				'<input class="palette-input" type="text" role="combobox" aria-expanded="true" aria-controls="palette-list" aria-autocomplete="list" autocomplete="off" spellcheck="false" placeholder="' + esc(t('Search tabs, bookmarks, settings and actions')) + '">' +
				'<kbd class="palette-esc">Esc</kbd></div>' +
				'<ul class="palette-list" id="palette-list" role="listbox"></ul>' +
				'<div class="palette-help"><span><kbd>↑</kbd><kbd>↓</kbd> ' + esc(t('choose')) + '</span><span><kbd>Enter</kbd> ' + esc(t('open')) + '</span><span><kbd>Shift</kbd>+<kbd>Enter</kbd> ' + esc(t('new browser tab')) + '</span></div>' +
				'</div></div>'
		).appendTo('body');
		$root.on('mousedown', function (event) {
			if (event.target === $root.get(0)) {
				close(true);
			}
		});
		$root.on('input', '.palette-input', search);
		$root.on('keydown', '.palette-input', function (event) {
			switch (event.key) {
				case 'ArrowDown':
					event.preventDefault();
					move(1);
					break;
				case 'ArrowUp':
					event.preventDefault();
					move(-1);
					break;
				case 'PageDown':
					event.preventDefault();
					move(Math.min(8, results.length - 1 - selected) || 0);
					break;
				case 'PageUp':
					event.preventDefault();
					move(-Math.min(8, selected) || 0);
					break;
				case 'Enter':
					event.preventDefault();
					choose(selected, event);
					break;
				case 'Escape':
					event.preventDefault();
					close(true);
					break;
				case 'Tab':
					// focus stays in the palette
					event.preventDefault();
					move(event.shiftKey ? -1 : 1);
					break;
			}
		});
		$root.on('mousemove', '.palette-item', function () {
			const index = parseInt($(this).attr('data-index'), 10);
			if (index !== selected) {
				selected = index;
				$root.find('.palette-item').removeClass('is-selected').attr('aria-selected', 'false');
				$(this).addClass('is-selected').attr('aria-selected', 'true');
			}
		});
		$root.on('click', '.palette-item', function (event) {
			choose(parseInt($(this).attr('data-index'), 10), event);
		});
	}

	function open() {
		if (!$root) {
			build();
		}
		if (!$root.hasClass('hidden')) {
			close(true);
			return;
		}
		previousFocus = document.activeElement;
		buildItems();
		$root.removeClass('hidden');
		$root.find('.palette-input').val('').trigger('focus');
		search();
	}

	function close(restoreFocus) {
		if (!$root) {
			return;
		}
		$root.addClass('hidden');
		if (restoreFocus && previousFocus && typeof previousFocus.focus === 'function') {
			previousFocus.focus();
		}
	}

	window.londerlandPalette = { open: open, close: close };
})();
