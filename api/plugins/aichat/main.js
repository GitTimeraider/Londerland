/*
 * AI Chat: chat button (bottom left) and chat window for logged in users.
 * Talks only to api/v2/plugins/aichat/*; answers are streamed as server-sent events.
 */
(function () {
	'use strict';

	const API = 'api/v2/plugins/aichat/';
	const VENDOR = 'assets/vendor/';
	const state = {
		chats: [],
		chat: null, // { id, title, model, pinned, messages: [] }
		models: [],
		defaultModel: null,
		prefs: { default_model: null, system_prompt: '', send_on_enter: true },
		pending: [], // attachments waiting to be sent: { id, name, mime, kind, uploading, localUrl }
		controller: null, // AbortController of the running answer
		search: '',
		librariesLoaded: null,
		modelSelect: null,
		initialized: false,
		searchOn: false, // globe toggle: search the web before answering
		imageOn: false, // image toggle: the message is an image prompt
	};
	let $panel;

	$('body').arrive('#activeInfo', { onceOnly: true }, function () {
		aiChatLaunch();
	});

	function aiChatLaunch() {
		const plugins = activeInfo.plugins || {};
		const includes = plugins.includes || {};
		const user = activeInfo.user || {};
		const group = parseInt(user.groupID, 10);
		if (plugins['AICHAT-enabled'] !== true || !user.loggedin || isNaN(group) || group >= 999) {
			return;
		}
		if (group > parseInt(includes['AICHAT-Auth-include'], 10)) {
			return;
		}
		buildDom();
	}

	/* ===================== helpers ===================== */

	function t(text) {
		return window.lang && typeof window.lang.translate === 'function' ? window.lang.translate(text) : text;
	}

	function trim(value) {
		return String(value == null ? '' : value).trim();
	}

	function escapeHtml(text) {
		return $('<div>').text(text == null ? '' : String(text)).html();
	}

	function headers(json = true) {
		const list = { Token: activeInfo.token, formKey: local('g', 'formKey') };
		if (json) {
			list['Content-Type'] = 'application/json';
		}
		return list;
	}

	async function api(method, path, body) {
		const response = await fetch(API + path, {
			method: method,
			headers: headers(),
			body: body === undefined ? undefined : JSON.stringify(body),
			credentials: 'same-origin',
		});
		let json = null;
		try {
			json = await response.json();
		} catch (e) {
			// not JSON
		}
		if (!response.ok || !json || json.response.result !== 'success') {
			throw new Error((json && json.response && json.response.message) || t('Request failed') + ' (' + response.status + ')');
		}
		return json.response;
	}

	function notify(text, type = 'error') {
		if (typeof message === 'function' && activeInfo.settings) {
			message(t('AI Chat'), text, activeInfo.settings.notifications.position, '#FFF', type, '6000');
		} else {
			console.warn(text);
		}
	}

	function loadScript(src) {
		return new Promise(function (resolve, reject) {
			if (document.querySelector('script[src="' + src + '"]')) {
				resolve();
				return;
			}
			const script = document.createElement('script');
			script.src = src;
			script.onload = resolve;
			script.onerror = function () {
				reject(new Error('Could not load ' + src));
			};
			document.head.appendChild(script);
		});
	}

	// Markdown renderer, sanitizer and code highlighter are only loaded when the chat is opened
	function loadLibraries() {
		if (!state.librariesLoaded) {
			if (!document.querySelector('link[href="' + VENDOR + 'highlightjs/github-dark.min.css"]')) {
				$('<link rel="stylesheet">').attr('href', VENDOR + 'highlightjs/github-dark.min.css').appendTo('head');
			}
			state.librariesLoaded = Promise.all([
				window.marked ? null : loadScript(VENDOR + 'marked/marked.umd.js'),
				window.DOMPurify ? null : loadScript(VENDOR + 'dompurify/purify.min.js'),
				window.hljs ? null : loadScript(VENDOR + 'highlightjs/highlight.min.js'),
			]);
		}
		return state.librariesLoaded;
	}

	function renderMarkdown(text) {
		if (!window.marked || !window.DOMPurify) {
			return '<p>' + escapeHtml(text).replace(/\n/g, '<br>') + '</p>';
		}
		const html = window.marked.parse(text || '', { gfm: true, breaks: true });
		const clean = window.DOMPurify.sanitize(html, { ADD_ATTR: ['target'] });
		const $html = $('<div>').html(clean);
		$html.find('a').attr({ target: '_blank', rel: 'noopener noreferrer' });
		$html.find('pre > code').each(function () {
			const code = this;
			const language = (code.className.match(/language-([\w+#.-]+)/) || [])[1] || '';
			if (window.hljs) {
				try {
					if (language && window.hljs.getLanguage(language)) {
						code.innerHTML = window.hljs.highlight(code.textContent, { language: language }).value;
					} else {
						code.innerHTML = window.hljs.highlightAuto(code.textContent).value;
					}
					code.classList.add('hljs');
				} catch (e) {
					// keep plain text
				}
			}
			const $pre = $(code).parent();
			const $wrap = $('<div class="aichat-code"></div>');
			$wrap.append(
				$('<div class="aichat-code-head"></div>')
					.append($('<span></span>').text(language || 'code'))
					.append('<button type="button" class="aichat-icon-btn aichat-copy-code" title="' + escapeHtml(t('Copy code')) + '"><i class="fa fa-copy"></i></button>')
			);
			$pre.replaceWith($wrap);
			$wrap.append($pre);
		});
		return $html.html();
	}

	function formatTime(iso) {
		if (!iso) {
			return '';
		}
		const date = new Date(iso);
		return isNaN(date) ? '' : date.toLocaleString([], { dateStyle: 'short', timeStyle: 'short' });
	}

	function formatSize(bytes) {
		if (bytes < 1024) {
			return bytes + ' B';
		}
		if (bytes < 1024 * 1024) {
			return (bytes / 1024).toFixed(0) + ' KB';
		}
		return (bytes / 1024 / 1024).toFixed(1) + ' MB';
	}

	function fileUrl(id) {
		return API + 'files/' + id;
	}

	function copyText(text) {
		const done = function () {
			notify(t('Copied to clipboard'), 'success');
		};
		if (typeof copyToClipboard === 'function') {
			copyToClipboard(text).then(done);
		} else if (navigator.clipboard) {
			navigator.clipboard.writeText(text).then(done);
		}
	}

	function download(name, content, type) {
		const url = URL.createObjectURL(new Blob([content], { type: type }));
		const link = $('<a>').attr({ href: url, download: name }).appendTo('body');
		link[0].click();
		link.remove();
		setTimeout(function () {
			URL.revokeObjectURL(url);
		}, 1000);
	}

	function confirmDialog(title, text, confirmText) {
		if (window.Swal) {
			return Swal.fire({
				title: title,
				text: text,
				icon: 'warning',
				showCancelButton: true,
				confirmButtonText: confirmText,
				cancelButtonText: t('Cancel'),
				confirmButtonColor: '#ff7676',
				customClass: { popup: 'bg-org' },
			}).then(function (result) {
				return result.isConfirmed;
			});
		}
		return Promise.resolve(window.confirm(title + '\n' + text));
	}

	/* ===================== DOM ===================== */

	function searchAvailable() {
		const provider = activeInfo.plugins.includes['AICHAT-searchProvider-include'];
		return !!provider && provider !== 'none';
	}

	function imagesAvailable() {
		return activeInfo.plugins.includes['AICHAT-images-include'] === true;
	}

	function buildDom() {
		const uploads = activeInfo.plugins.includes['AICHAT-uploads-include'] !== false;
		// Big chat bubble fixed in the bottom right corner of every page
		$('body')
			.addClass('aichat-enabled')
			.append(`<button type="button" class="aichat-launcher" title="${escapeHtml(t('AI Chat'))}" aria-label="${escapeHtml(t('Open AI Chat'))}" aria-expanded="false"><i class="fa fa-comment-dots"></i><span class="aichat-launcher-label">${escapeHtml(t('AI'))}</span></button>`);
		$('body').append(`
			<section class="aichat-panel" role="dialog" aria-label="${escapeHtml(t('AI Chat'))}">
				<aside class="aichat-sidebar">
					<div class="aichat-sidebar-head">
						<button type="button" class="btn btn-info btn-sm aichat-new"><i class="fa fa-plus me-1"></i> <span>${escapeHtml(t('New chat'))}</span></button>
					</div>
					<input type="search" class="form-control form-control-sm aichat-search" placeholder="${escapeHtml(t('Search chats'))}">
					<ul class="aichat-chat-list" role="list"></ul>
					<div class="aichat-sidebar-foot">
						<button type="button" class="aichat-icon-btn aichat-open-settings" title="${escapeHtml(t('Chat settings'))}"><i class="fa fa-sliders"></i>&nbsp;<span>${escapeHtml(t('Settings'))}</span></button>
						<button type="button" class="aichat-icon-btn aichat-delete-all" title="${escapeHtml(t('Delete all chats'))}"><i class="fa fa-trash"></i></button>
					</div>
				</aside>
				<div class="aichat-main">
					<header class="aichat-header">
						<button type="button" class="aichat-icon-btn aichat-toggle-sidebar" title="${escapeHtml(t('Chat history'))}"><i class="fa fa-bars"></i></button>
						<div class="aichat-title" title="${escapeHtml(t('Click to rename'))}"></div>
						<div class="aichat-model-wrap">
							<select class="aichat-model" aria-label="${escapeHtml(t('Model'))}"></select>
							<button type="button" class="aichat-icon-btn aichat-set-default" title="${escapeHtml(t('Make this my default model'))}"><i class="fa fa-star"></i></button>
						</div>
						<div class="dropdown">
							<button type="button" class="aichat-icon-btn" data-bs-toggle="dropdown" aria-expanded="false" title="${escapeHtml(t('More'))}"><i class="fa fa-ellipsis-v"></i></button>
							<ul class="dropdown-menu dropdown-menu-end">
								<li><a class="dropdown-item aichat-rename" href="#"><i class="fa fa-pencil fa-fw me-2"></i>${escapeHtml(t('Rename'))}</a></li>
								<li><a class="dropdown-item aichat-pin" href="#"><i class="fa fa-thumb-tack fa-fw me-2"></i><span>${escapeHtml(t('Pin'))}</span></a></li>
								<li><a class="dropdown-item aichat-export-md" href="#"><i class="fa fa-file-text-o fa-fw me-2"></i>${escapeHtml(t('Export as Markdown'))}</a></li>
								<li><a class="dropdown-item aichat-export-json" href="#"><i class="fa fa-file-code-o fa-fw me-2"></i>${escapeHtml(t('Export as JSON'))}</a></li>
								<li><hr class="dropdown-divider"></li>
								<li><a class="dropdown-item text-danger aichat-delete" href="#"><i class="fa fa-trash fa-fw me-2"></i>${escapeHtml(t('Delete chat'))}</a></li>
							</ul>
						</div>
						<button type="button" class="aichat-icon-btn aichat-expand" title="${escapeHtml(t('Larger window'))}"><i class="fa fa-expand"></i></button>
						<button type="button" class="aichat-icon-btn aichat-close" title="${escapeHtml(t('Close'))}"><i class="fa fa-times"></i></button>
					</header>
					<div class="aichat-messages" aria-live="polite"></div>
					<div class="aichat-composer">
						<div class="aichat-composer-box">
							<div class="aichat-attachments aichat-pending"></div>
							<textarea class="aichat-input" rows="1" placeholder="${escapeHtml(t('Send a message'))}"></textarea>
							<div class="aichat-composer-row">
								<div>
									${uploads ? `<button type="button" class="aichat-icon-btn aichat-attach" title="${escapeHtml(t('Add images or files'))}"><i class="fa fa-paperclip"></i></button>
									<input type="file" class="aichat-file" multiple hidden>` : ''}
									${searchAvailable() ? `<button type="button" class="aichat-tool-toggle aichat-toggle-search" aria-pressed="false" title="${escapeHtml(t('Search the web before answering'))}"><i class="fa fa-globe"></i><span>${escapeHtml(t('Search'))}</span></button>` : ''}
									${imagesAvailable() ? `<button type="button" class="aichat-tool-toggle aichat-toggle-image" aria-pressed="false" title="${escapeHtml(t('Create an image from your message'))}"><i class="fa fa-image"></i><span>${escapeHtml(t('Image'))}</span></button>` : ''}
									<span class="aichat-hint"></span>
								</div>
								<button type="button" class="aichat-send" title="${escapeHtml(t('Send'))}" disabled><i class="fa fa-arrow-up"></i></button>
							</div>
						</div>
					</div>
					<div class="aichat-drop"><i class="fa fa-upload me-2"></i>${escapeHtml(t('Drop files to add them'))}</div>
					<div class="aichat-overlay aichat-settings-overlay">
						<div class="aichat-dialog">
							<h4>${escapeHtml(t('Chat settings'))}</h4>
							<div class="form-group">
								<label for="aichat-pref-model">${escapeHtml(t('My default model'))}</label>
								<select id="aichat-pref-model" class="form-select aichat-pref-model"></select>
							</div>
							<div class="form-group">
								<label for="aichat-pref-prompt">${escapeHtml(t('Custom instructions (sent with every chat)'))}</label>
								<textarea id="aichat-pref-prompt" class="form-control aichat-pref-prompt" rows="5" maxlength="8000" placeholder="${escapeHtml(t('For example: answer briefly, I work with Docker and Linux.'))}"></textarea>
							</div>
							<div class="form-check form-switch mb-3">
								<input class="form-check-input aichat-pref-enter" type="checkbox" role="switch" id="aichat-pref-enter">
								<label class="form-check-label" for="aichat-pref-enter">${escapeHtml(t('Enter sends the message (Shift+Enter for a new line)'))}</label>
							</div>
							<div class="d-flex justify-content-end gap-2">
								<button type="button" class="btn btn-secondary btn-sm aichat-settings-cancel">${escapeHtml(t('Cancel'))}</button>
								<button type="button" class="btn btn-info btn-sm aichat-settings-save">${escapeHtml(t('Save'))}</button>
							</div>
						</div>
					</div>
				</div>
			</section>
		`);
		$panel = $('.aichat-panel');
		bindEvents();
	}

	/* ===================== opening ===================== */

	async function openChat() {
		$('body').addClass('aichat-open');
		$('.aichat-launcher').attr({ 'aria-expanded': 'true', 'aria-label': t('Close AI Chat') }).find('i').attr('class', 'fa fa-times');
		if (window.innerWidth < 768) {
			$panel.addClass('aichat-sidebar-hidden');
		}
		$panel.find('.aichat-input').trigger('focus');
		if (state.initialized) {
			return;
		}
		state.initialized = true;
		renderNotice('<i class="fa fa-spinner fa-spin"></i>');
		try {
			await loadLibraries();
			const data = (await api('GET', 'models')).data;
			state.models = data.models;
			state.defaultModel = data.defaultModel;
			state.prefs = data.prefs;
			buildModelSelect();
			updateHint();
			await refreshChats();
			// start with an empty chat; the history stays one click away in the sidebar
			showWelcome();
		} catch (error) {
			state.initialized = false;
			renderNotice('<i class="fa fa-exclamation-triangle me-2"></i>' + escapeHtml(error.message));
		}
	}

	function closeChat() {
		$('body').removeClass('aichat-open');
		$('.aichat-launcher').attr({ 'aria-expanded': 'false', 'aria-label': t('Open AI Chat') }).find('i').attr('class', 'fa fa-comment-dots');
		$('.aichat-launcher').trigger('focus');
	}

	function renderNotice(html) {
		$panel.find('.aichat-messages').html('<div class="aichat-notice">' + html + '</div>');
	}

	/* ===================== models ===================== */

	function preferredModel() {
		const candidates = [state.prefs.default_model, state.defaultModel];
		for (const model of candidates) {
			if (model && state.models.includes(model)) {
				return model;
			}
		}
		return state.models[0] || '';
	}

	function buildModelSelect() {
		const $select = $panel.find('.aichat-model');
		if (state.modelSelect) {
			state.modelSelect.destroy();
		}
		$select.empty();
		state.models.forEach(function (model) {
			$select.append($('<option>').val(model).text(model));
		});
		if (!state.models.length) {
			$select.append($('<option value="">').text(t('No models available')));
		}
		$select.val(preferredModel());
		if (window.TomSelect && state.models.length) {
			state.modelSelect = new TomSelect($select[0], {
				maxOptions: null,
				allowEmptyOption: false,
				onChange: onModelChange,
			});
		} else {
			$select.addClass('form-select form-select-sm').on('change', function () {
				onModelChange(this.value);
			});
		}
		updateDefaultStar();
	}

	function currentModel() {
		return state.modelSelect ? state.modelSelect.getValue() : $panel.find('.aichat-model').val();
	}

	function setModel(model) {
		if (!model || !state.models.includes(model)) {
			model = preferredModel();
		}
		if (state.modelSelect) {
			state.modelSelect.setValue(model, true);
		} else {
			$panel.find('.aichat-model').val(model);
		}
		updateDefaultStar();
	}

	function onModelChange(model) {
		updateDefaultStar();
		if (state.chat && model) {
			state.chat.model = model;
			api('PUT', 'chats/' + state.chat.id, { model: model }).catch(function (error) {
				notify(error.message);
			});
		}
	}

	function updateDefaultStar() {
		const isDefault = currentModel() && currentModel() === state.prefs.default_model;
		$panel
			.find('.aichat-set-default')
			.toggleClass('active', !!isDefault)
			.attr('title', isDefault ? t('This is your default model') : t('Make this my default model'));
	}

	async function setDefaultModel() {
		const model = currentModel();
		if (!model) {
			return;
		}
		try {
			state.prefs = (await api('PUT', 'prefs', { default_model: model })).data;
			updateDefaultStar();
			notify(t('Default model set to') + ' ' + model, 'success');
		} catch (error) {
			notify(error.message);
		}
	}

	/* ===================== chat list ===================== */

	async function refreshChats() {
		const path = 'chats' + (state.search ? '?search=' + encodeURIComponent(state.search) : '');
		state.chats = (await api('GET', path)).data || [];
		renderChatList();
	}

	function chatGroup(chat) {
		if (chat.pinned) {
			return t('Pinned');
		}
		const updated = new Date(chat.updated);
		const today = new Date();
		today.setHours(0, 0, 0, 0);
		const days = (today - new Date(updated.getFullYear(), updated.getMonth(), updated.getDate())) / 86400000;
		if (days <= 0) {
			return t('Today');
		}
		if (days <= 1) {
			return t('Yesterday');
		}
		if (days <= 7) {
			return t('Previous 7 days');
		}
		if (days <= 30) {
			return t('Previous 30 days');
		}
		return t('Older');
	}

	function renderChatList() {
		const $list = $panel.find('.aichat-chat-list').empty();
		if (!state.chats.length) {
			$list.append($('<li class="aichat-empty-list"></li>').text(state.search ? t('No chats found') : t('No chats yet')));
			return;
		}
		let lastGroup = null;
		state.chats.forEach(function (chat) {
			const group = chatGroup(chat);
			if (group !== lastGroup) {
				$list.append($('<li class="aichat-group-label"></li>').text(group));
				lastGroup = group;
			}
			const $item = $('<li class="aichat-chat-item" tabindex="0" role="button"></li>')
				.attr('data-id', chat.id)
				.toggleClass('active', !!(state.chat && state.chat.id === chat.id));
			$item.append($('<span class="aichat-chat-title"></span>').text(chat.title).attr('title', chat.title));
			$item.append(
				`<span class="aichat-chat-actions">
					<button type="button" class="aichat-icon-btn aichat-item-pin" title="${escapeHtml(chat.pinned ? t('Unpin') : t('Pin'))}"><i class="fa fa-thumb-tack"></i></button>
					<button type="button" class="aichat-icon-btn aichat-item-delete" title="${escapeHtml(t('Delete chat'))}"><i class="fa fa-trash"></i></button>
				</span>`
			);
			$list.append($item);
		});
	}

	/* ===================== messages ===================== */

	function showWelcome() {
		state.chat = null;
		state.pending = [];
		renderPending();
		setModel(preferredModel());
		$panel.find('.aichat-title').text(t('New chat'));
		$panel.find('.aichat-pin span').text(t('Pin'));
		const name = escapeHtml(activeInfo.user.username || '');
		const suggestions = [t('Explain how Docker volumes work'), t('Write a short welcome text for my homelab'), t('Help me debug a bash script'), t('Summarize the file I upload')];
		$panel.find('.aichat-messages').html(`
			<div class="aichat-welcome">
				<h3>${escapeHtml(t('Hi'))} ${name}</h3>
				<p>${escapeHtml(t('How can I help you today?'))}</p>
				<div class="aichat-suggestions">${suggestions.map((text) => `<button type="button" class="aichat-suggestion">${escapeHtml(text)}</button>`).join('')}</div>
			</div>`);
		renderChatList();
		updateSendState();
	}

	async function loadChat(id) {
		if (state.controller) {
			return;
		}
		try {
			state.chat = (await api('GET', 'chats/' + id)).data;
		} catch (error) {
			notify(error.message);
			return;
		}
		state.pending = [];
		renderPending();
		setModel(state.chat.model);
		$panel.find('.aichat-title').text(state.chat.title);
		$panel.find('.aichat-pin span').text(state.chat.pinned ? t('Unpin') : t('Pin'));
		renderMessages();
		renderChatList();
		if (window.innerWidth < 768) {
			$panel.addClass('aichat-sidebar-hidden');
		}
		updateSendState();
	}

	function attachmentHtml(attachment, removable) {
		const name = escapeHtml(attachment.name);
		const remove = removable ? `<button type="button" class="aichat-attachment-remove" data-id="${escapeHtml(attachment.localId || attachment.id)}" title="${escapeHtml(t('Remove'))}">&times;</button>` : '';
		const uploading = attachment.uploading ? ' uploading' : '';
		if (attachment.kind === 'image') {
			const src = attachment.localUrl || fileUrl(attachment.id);
			const open = attachment.id ? ` href="${fileUrl(attachment.id)}" target="_blank" rel="noopener"` : '';
			return `<a class="aichat-attachment image${uploading}"${open} title="${name}"><img src="${escapeHtml(src)}" alt="${name}">${remove}</a>`;
		}
		const icon = attachment.kind === 'pdf' ? 'fa-file-pdf-o' : 'fa-file-text-o';
		const open = attachment.id ? ` href="${fileUrl(attachment.id)}"` : '';
		const spinner = attachment.uploading ? '<i class="fa fa-spinner fa-spin"></i>' : `<i class="fa ${icon}"></i>`;
		return `<a class="aichat-attachment${uploading}"${open} title="${name}">${spinner}<span>${name}</span>${attachment.size ? `<small class="text-muted">${formatSize(attachment.size)}</small>` : ''}${remove}</a>`;
	}

	function generatedImageHtml(image) {
		const url = fileUrl(image.id);
		return `<figure class="aichat-generated">
			<a href="${url}" target="_blank" rel="noopener"><img src="${url}" alt="${escapeHtml(image.prompt || image.name)}" loading="lazy"></a>
			<figcaption><span>${escapeHtml(image.prompt || '')}</span><a href="${url}" download="${escapeHtml(image.name)}" class="aichat-icon-btn" title="${escapeHtml(t('Download'))}"><i class="fa fa-download"></i></a></figcaption>
		</figure>`;
	}

	function hostName(url) {
		try {
			return new URL(url).hostname.replace(/^www\./, '');
		} catch (e) {
			return url;
		}
	}

	function sourcesHtml(sources, searches) {
		if (!sources || !sources.length) {
			return '';
		}
		const searched = searches && searches.length ? `<span class="aichat-searched">${escapeHtml(t('Searched'))}: ${searches.map((q) => '"' + escapeHtml(q) + '"').join(', ')}</span>` : '';
		const items = sources
			.map((source, index) => `<li><a href="${escapeHtml(source.url)}" target="_blank" rel="noopener noreferrer" title="${escapeHtml(source.snippet || '')}"><span class="aichat-source-number">${index + 1}</span><span class="aichat-source-title">${escapeHtml(source.title)}</span><span class="aichat-source-host">${escapeHtml(hostName(source.url))}</span></a></li>`)
			.join('');
		return `<details class="aichat-sources"><summary><i class="fa fa-globe me-1"></i>${escapeHtml(t('Sources'))} (${sources.length}) ${searched}</summary><ol>${items}</ol></details>`;
	}

	// Turns [1], [2] in an answer into links to the matching source
	function linkCitations(html, sources) {
		if (!sources || !sources.length) {
			return html;
		}
		const $html = $('<div>').html(html);
		const walker = document.createTreeWalker($html[0], NodeFilter.SHOW_TEXT);
		const nodes = [];
		while (walker.nextNode()) {
			if (/\[\d+\]/.test(walker.currentNode.nodeValue) && !$(walker.currentNode).closest('code, pre, a').length) {
				nodes.push(walker.currentNode);
			}
		}
		nodes.forEach(function (node) {
			const fragment = document.createDocumentFragment();
			node.nodeValue.split(/(\[\d+\])/).forEach(function (part) {
				const match = part.match(/^\[(\d+)\]$/);
				const source = match ? sources[parseInt(match[1], 10) - 1] : null;
				if (source) {
					const link = $('<a class="aichat-cite" target="_blank" rel="noopener noreferrer"></a>').attr({ href: source.url, title: source.title }).text(match[1]);
					fragment.appendChild(link[0]);
				} else {
					fragment.appendChild(document.createTextNode(part));
				}
			});
			node.parentNode.replaceChild(fragment, node);
		});
		return $html.html();
	}

	function messageHtml(msg, isLast) {
		const classes = ['aichat-message', msg.role];
		if (isLast) {
			classes.push('last');
		}
		const meta = msg.meta || {};
		let body = '';
		if (msg.role === 'user' && msg.attachments && msg.attachments.length) {
			body += `<div class="aichat-attachments">${msg.attachments.map((a) => attachmentHtml(a, false)).join('')}</div>`;
		}
		if (msg.role === 'assistant') {
			if (msg.reasoning) {
				body += `<details class="aichat-reasoning"><summary>${escapeHtml(t('Thinking'))}</summary><div class="aichat-reasoning-body">${escapeHtml(msg.reasoning)}</div></details>`;
			}
			const images = msg.attachments || [];
			if (msg.content || !images.length) {
				body += `<div class="aichat-bubble aichat-markdown">${msg.content ? linkCitations(renderMarkdown(msg.content), meta.sources) : `<em class="text-muted">${escapeHtml(t('(empty answer)'))}</em>`}</div>`;
			}
			if (images.length) {
				body += `<div class="aichat-generated-list">${images.map(generatedImageHtml).join('')}</div>`;
			}
			body += sourcesHtml(meta.sources, meta.searches);
		} else if (msg.role === 'error') {
			body += `<div class="aichat-bubble"><i class="fa fa-exclamation-triangle me-2"></i>${escapeHtml(msg.content)}</div>`;
		} else if (msg.content) {
			body += `<div class="aichat-bubble">${escapeHtml(msg.content)}</div>`;
		}
		const actions = [];
		if (msg.role !== 'error') {
			actions.push(`<button type="button" class="aichat-icon-btn aichat-copy" title="${escapeHtml(t('Copy'))}"><i class="fa fa-copy"></i></button>`);
		}
		if (msg.role === 'user') {
			actions.push(`<button type="button" class="aichat-icon-btn aichat-edit" title="${escapeHtml(t('Edit and send again'))}"><i class="fa fa-pencil"></i></button>`);
		}
		if ((msg.role === 'assistant' || msg.role === 'error') && isLast) {
			actions.push(`<button type="button" class="aichat-icon-btn aichat-regenerate" title="${escapeHtml(t('Answer again'))}"><i class="fa fa-refresh"></i></button>`);
		}
		const label = msg.role === 'assistant' && msg.model ? `<span>${escapeHtml(msg.model)}</span>&middot;` : '';
		return `<div class="${classes.join(' ')}" data-id="${msg.id || ''}">${body}<div class="aichat-meta">${label}<span>${escapeHtml(formatTime(msg.created))}</span>${actions.join('')}</div></div>`;
	}

	function renderMessages() {
		const $messages = $panel.find('.aichat-messages');
		const list = state.chat ? state.chat.messages : [];
		if (!list.length) {
			$messages.html(`<div class="aichat-notice">${escapeHtml(t('Ask anything to start this chat.'))}</div>`);
			return;
		}
		$messages.html(list.map((msg, index) => messageHtml(msg, index === list.length - 1)).join(''));
		scrollToBottom(true);
	}

	function scrollToBottom(force) {
		const el = $panel.find('.aichat-messages')[0];
		// keep following the answer unless the user scrolled up to read
		if (force || el.scrollHeight - el.scrollTop - el.clientHeight < 120) {
			el.scrollTop = el.scrollHeight;
		}
	}

	/* ===================== sending ===================== */

	function updateSendState() {
		const $send = $panel.find('.aichat-send');
		if (state.controller) {
			$send.prop('disabled', false).addClass('stop').attr('title', t('Stop')).html('<i class="fa fa-stop"></i>');
			return;
		}
		const hasText = trim($panel.find('.aichat-input').val()) !== '';
		const uploading = state.pending.some((file) => file.uploading);
		// image mode needs a description but no chat model
		const ready = state.imageOn ? hasText && !uploading : (hasText || state.pending.length) && !uploading && state.models.length;
		$send.prop('disabled', !ready).removeClass('stop').attr('title', t('Send')).html('<i class="fa fa-arrow-up"></i>');
	}

	function updateHint() {
		$panel
			.find('.aichat-hint')
			.text(state.prefs.send_on_enter ? t('Enter to send, Shift+Enter for a new line') : t('Ctrl+Enter to send'));
	}

	async function ensureChat() {
		if (state.chat) {
			return state.chat;
		}
		const chat = (await api('POST', 'chats', { model: currentModel() })).data;
		chat.messages = [];
		state.chat = chat;
		state.chats.unshift(chat);
		$panel.find('.aichat-title').text(chat.title);
		renderChatList();
		return chat;
	}

	async function send(options = {}) {
		if (state.controller) {
			return;
		}
		const $input = $panel.find('.aichat-input');
		const content = options.content !== undefined ? options.content : trim($input.val());
		const files = options.regenerate ? [] : options.files || state.pending.filter((f) => f.id).map((f) => f.id);
		if (!options.regenerate && !content && !files.length) {
			return;
		}
		let chat;
		try {
			chat = await ensureChat();
		} catch (error) {
			notify(error.message);
			return;
		}
		const body = { model: currentModel(), content: content, files: files, search: state.searchOn, image: state.imageOn };
		if (options.regenerate) {
			body.regenerate = true;
			const previous = chat.messages.slice().reverse().find((m) => m.role === 'assistant');
			const previousMeta = (previous && previous.meta) || {};
			body.image = !!previousMeta.image;
			body.search = !body.image && (state.searchOn || !!(previousMeta.searches && previousMeta.searches.length));
			// remove the old answer (and a failed attempt) from the view
			while (chat.messages.length && chat.messages[chat.messages.length - 1].role !== 'user') {
				chat.messages.pop();
			}
		}
		if (options.editFrom) {
			body.editFrom = options.editFrom;
			const index = chat.messages.findIndex((m) => m.id === options.editFrom);
			if (index >= 0) {
				chat.messages.splice(index);
			}
		}
		if (!options.regenerate && options.content === undefined) {
			$input.val('').trigger('input');
			state.pending = [];
			renderPending();
		}
		await stream(chat, body);
	}

	async function stream(chat, body) {
		const $messages = $panel.find('.aichat-messages');
		if (!chat.messages.length) {
			$messages.empty();
		} else {
			renderMessages();
		}
		$messages.find('.aichat-message').removeClass('last');
		$messages.find('.aichat-regenerate').remove();
		const $answer = $(`<div class="aichat-message assistant last"><div class="aichat-status"></div><div class="aichat-reasoning-slot"></div><div class="aichat-bubble aichat-markdown"><span class="aichat-typing"><span></span><span></span><span></span></span></div><div class="aichat-generated-list"></div><div class="aichat-sources-slot"></div></div>`);
		$messages.append($answer);
		scrollToBottom(true);
		state.controller = new AbortController();
		updateSendState();
		let answer = '';
		let reasoning = '';
		let sources = [];
		let renderQueued = false;
		const setStatus = function (text) {
			$answer.find('.aichat-status').html(text ? `<i class="fa fa-spinner fa-spin me-2"></i>${escapeHtml(t(text))}` : '');
		};
		const render = function () {
			renderQueued = false;
			if (reasoning) {
				let $details = $answer.find('.aichat-reasoning');
				if (!$details.length) {
					$details = $(`<details class="aichat-reasoning" open><summary>${escapeHtml(t('Thinking'))}</summary><div class="aichat-reasoning-body"></div></details>`);
					$answer.find('.aichat-reasoning-slot').append($details);
				}
				$details.find('.aichat-reasoning-body').text(reasoning);
			}
			if (answer) {
				$answer.find('.aichat-reasoning').prop('open', false);
				$answer.find('.aichat-bubble').html(linkCitations(renderMarkdown(answer), sources)).addClass('aichat-cursor');
				setStatus('');
			}
			scrollToBottom();
		};
		const queueRender = function () {
			if (!renderQueued) {
				renderQueued = true;
				requestAnimationFrame(render);
			}
		};
		let finished = false;
		try {
			const response = await fetch(API + 'chats/' + chat.id + '/stream', {
				method: 'POST',
				headers: headers(),
				body: JSON.stringify(body),
				credentials: 'same-origin',
				signal: state.controller.signal,
			});
			if (!response.ok || !(response.headers.get('Content-Type') || '').includes('text/event-stream')) {
				let text = t('The answer could not be started') + ' (' + response.status + ')';
				try {
					text = (await response.json()).response.message || text;
				} catch (e) {
					// keep default text
				}
				throw new Error(text);
			}
			const reader = response.body.getReader();
			const decoder = new TextDecoder();
			let buffer = '';
			for (;;) {
				const { value, done } = await reader.read();
				if (done) {
					break;
				}
				buffer += decoder.decode(value, { stream: true });
				let split;
				while ((split = buffer.indexOf('\n\n')) >= 0) {
					const raw = buffer.slice(0, split);
					buffer = buffer.slice(split + 2);
					const line = raw.split('\n').find((l) => l.startsWith('data:'));
					if (!line) {
						continue;
					}
					const event = JSON.parse(line.slice(5));
					switch (event.type) {
						case 'user':
							chat.messages.push(event.message);
							$answer.before(messageHtml(event.message, false));
							scrollToBottom(true);
							break;
						case 'status':
							setStatus(event.text);
							break;
						case 'sources':
							sources = event.sources;
							$answer.find('.aichat-sources-slot').html(sourcesHtml(sources, []));
							break;
						case 'image':
							setStatus('');
							if (!answer) {
								$answer.find('.aichat-bubble').remove();
							}
							$answer.find('.aichat-generated-list').append(generatedImageHtml(event.image));
							scrollToBottom(true);
							break;
						case 'reasoning':
							reasoning += event.content;
							queueRender();
							break;
						case 'delta':
							answer += event.content;
							queueRender();
							break;
						case 'done':
							finished = true;
							chat.messages.push(event.message);
							break;
						case 'title':
							chat.title = event.title;
							$panel.find('.aichat-title').text(event.title);
							break;
						case 'error':
							throw new Error(event.message);
					}
				}
			}
			if (!finished) {
				throw new Error(t('The connection to the AI server was interrupted'));
			}
		} catch (error) {
			if (error.name === 'AbortError') {
				// stopped by the user: the server keeps what was written so far
				if (answer || reasoning) {
					chat.messages.push({ id: null, role: 'assistant', content: answer, reasoning: reasoning || null, model: body.model, created: new Date().toISOString(), attachments: [] });
				}
			} else {
				chat.messages.push({ id: null, role: 'error', content: error.message, attachments: [] });
			}
		} finally {
			state.controller = null;
			updateSendState();
			renderMessages();
			const index = state.chats.findIndex((c) => c.id === chat.id);
			if (index >= 0) {
				state.chats[index].title = chat.title;
				state.chats[index].updated = new Date().toISOString();
				state.chats.unshift(state.chats.splice(index, 1)[0]);
				state.chats.sort((a, b) => b.pinned - a.pinned);
			}
			renderChatList();
			// a stopped answer gets its real id from the server on the next load
			if (chat.messages.some((m) => !m.id && m.role === 'assistant')) {
				setTimeout(function () {
					if (state.chat && state.chat.id === chat.id && !state.controller) {
						loadChat(chat.id);
					}
				}, 800);
			}
		}
	}

	function stop() {
		if (state.controller) {
			state.controller.abort();
		}
	}

	/* ===================== uploads ===================== */

	function renderPending() {
		$panel.find('.aichat-pending').html(state.pending.map((file) => attachmentHtml(file, true)).join(''));
		updateSendState();
	}

	function addFiles(fileList) {
		if (activeInfo.plugins.includes['AICHAT-uploads-include'] === false) {
			return;
		}
		const maxBytes = (parseInt(activeInfo.plugins.includes['AICHAT-maxUploadMB-include'], 10) || 20) * 1024 * 1024;
		Array.from(fileList).forEach(function (file) {
			if (file.size > maxBytes) {
				notify(file.name + ': ' + t('file is too large'));
				return;
			}
			const pending = {
				localId: 'local-' + Math.random().toString(36).slice(2),
				name: file.name || 'pasted-image.png',
				size: file.size,
				kind: file.type.startsWith('image/') ? 'image' : file.type === 'application/pdf' ? 'pdf' : 'text',
				uploading: true,
				localUrl: file.type.startsWith('image/') ? URL.createObjectURL(file) : null,
			};
			state.pending.push(pending);
			renderPending();
			const form = new FormData();
			form.append('file', file, pending.name);
			fetch(API + 'files', { method: 'POST', headers: headers(false), body: form, credentials: 'same-origin' })
				.then((response) => response.json().then((json) => ({ ok: response.ok, json: json })))
				.then(function (result) {
					if (!result.ok || result.json.response.result !== 'success') {
						throw new Error(result.json.response.message || t('Upload failed'));
					}
					Object.assign(pending, result.json.response.data, { uploading: false });
				})
				.catch(function (error) {
					notify(pending.name + ': ' + error.message);
					state.pending = state.pending.filter((p) => p !== pending);
				})
				.finally(renderPending);
		});
	}

	/* ===================== actions ===================== */

	async function renameChat() {
		if (!state.chat) {
			return;
		}
		let title = state.chat.title;
		if (window.Swal) {
			const result = await Swal.fire({
				title: t('Rename chat'),
				input: 'text',
				inputValue: title,
				showCancelButton: true,
				confirmButtonText: t('Save'),
				cancelButtonText: t('Cancel'),
				customClass: { popup: 'bg-org' },
			});
			if (!result.isConfirmed) {
				return;
			}
			title = result.value;
		} else {
			title = window.prompt(t('Rename chat'), title);
		}
		if (!title || !trim(title)) {
			return;
		}
		try {
			const chat = (await api('PUT', 'chats/' + state.chat.id, { title: trim(title) })).data;
			state.chat.title = chat.title;
			$panel.find('.aichat-title').text(chat.title);
			state.chats.forEach((c) => c.id === chat.id && (c.title = chat.title));
			renderChatList();
		} catch (error) {
			notify(error.message);
		}
	}

	async function togglePin(id) {
		const chat = state.chats.find((c) => c.id === id) || state.chat;
		if (!chat) {
			return;
		}
		try {
			const updated = (await api('PUT', 'chats/' + id, { pinned: !chat.pinned })).data;
			state.chats.forEach((c) => c.id === id && (c.pinned = updated.pinned));
			if (state.chat && state.chat.id === id) {
				state.chat.pinned = updated.pinned;
				$panel.find('.aichat-pin span').text(updated.pinned ? t('Unpin') : t('Pin'));
			}
			await refreshChats();
		} catch (error) {
			notify(error.message);
		}
	}

	async function deleteChat(id) {
		const chat = state.chats.find((c) => c.id === id);
		const ok = await confirmDialog(t('Delete chat?'), chat ? chat.title : '', t('Delete'));
		if (!ok) {
			return;
		}
		try {
			await api('DELETE', 'chats/' + id);
			state.chats = state.chats.filter((c) => c.id !== id);
			if (state.chat && state.chat.id === id) {
				showWelcome();
			}
			renderChatList();
		} catch (error) {
			notify(error.message);
		}
	}

	async function deleteAll() {
		const ok = await confirmDialog(t('Delete all chats?'), t('This removes your whole chat history and uploaded files.'), t('Delete all'));
		if (!ok) {
			return;
		}
		try {
			await api('DELETE', 'chats');
			state.chats = [];
			showWelcome();
		} catch (error) {
			notify(error.message);
		}
	}

	function exportChat(format) {
		if (!state.chat) {
			return;
		}
		const safe = (state.chat.title || 'chat').replace(/[^\w\- ]+/g, '').trim().replace(/\s+/g, '-') || 'chat';
		if (format === 'json') {
			download(safe + '.json', JSON.stringify(state.chat, null, 2), 'application/json');
			return;
		}
		const lines = ['# ' + state.chat.title, ''];
		state.chat.messages.forEach(function (msg) {
			if (msg.role === 'error') {
				return;
			}
			lines.push('## ' + (msg.role === 'user' ? activeInfo.user.username : msg.model || 'Assistant') + ' (' + formatTime(msg.created) + ')', '');
			(msg.attachments || []).forEach((a) => lines.push('*' + t('Attachment') + ': ' + a.name + '*'));
			lines.push(msg.content || '', '');
		});
		download(safe + '.md', lines.join('\n'), 'text/markdown');
	}

	function openSettings() {
		const $select = $panel.find('.aichat-pref-model').empty();
		$select.append($('<option value="">').text(t('Server default') + (state.defaultModel ? ' (' + state.defaultModel + ')' : '')));
		state.models.forEach((model) => $select.append($('<option>').val(model).text(model)));
		$select.val(state.prefs.default_model || '');
		$panel.find('.aichat-pref-prompt').val(state.prefs.system_prompt || '');
		$panel.find('.aichat-pref-enter').prop('checked', state.prefs.send_on_enter !== false);
		$panel.find('.aichat-settings-overlay').addClass('show');
	}

	async function saveSettings() {
		try {
			state.prefs = (
				await api('PUT', 'prefs', {
					default_model: $panel.find('.aichat-pref-model').val() || null,
					system_prompt: $panel.find('.aichat-pref-prompt').val(),
					send_on_enter: $panel.find('.aichat-pref-enter').prop('checked'),
				})
			).data;
			$panel.find('.aichat-settings-overlay').removeClass('show');
			updateHint();
			updateDefaultStar();
			if (!state.chat) {
				setModel(preferredModel());
			}
			notify(t('Settings saved'), 'success');
		} catch (error) {
			notify(error.message);
		}
	}

	function findMessage(element) {
		const id = parseInt($(element).closest('.aichat-message').attr('data-id'), 10);
		const index = $(element).closest('.aichat-message').index();
		return state.chat ? state.chat.messages.find((m) => m.id === id) || state.chat.messages[index] : null;
	}

	function editMessage(msg) {
		if (!msg || !msg.id || state.controller) {
			return;
		}
		const $message = $panel.find('.aichat-message[data-id="' + msg.id + '"]');
		const $editor = $(`
			<div class="aichat-composer-box w-100">
				<textarea class="aichat-input" rows="3"></textarea>
				<div class="aichat-composer-row">
					<span class="aichat-hint">${escapeHtml(t('Sending removes the answers after this message'))}</span>
					<div class="d-flex gap-2">
						<button type="button" class="btn btn-secondary btn-sm aichat-edit-cancel">${escapeHtml(t('Cancel'))}</button>
						<button type="button" class="btn btn-info btn-sm aichat-edit-send">${escapeHtml(t('Send'))}</button>
					</div>
				</div>
			</div>`);
		$editor.find('textarea').val(msg.content);
		$message.find('.aichat-bubble, .aichat-meta').hide();
		$message.append($editor);
		$editor.find('textarea').trigger('focus');
		$editor.on('click', '.aichat-edit-cancel', function () {
			$editor.remove();
			$message.find('.aichat-bubble, .aichat-meta').show();
		});
		$editor.on('click', '.aichat-edit-send', function () {
			const text = trim($editor.find('textarea').val());
			if (!text) {
				return;
			}
			send({ content: text, editFrom: msg.id, files: (msg.attachments || []).map((a) => a.id) });
		});
	}

	/* ===================== events ===================== */

	function bindEvents() {
		$('body').on('click', '.aichat-launcher', function () {
			if ($('body').hasClass('aichat-open')) {
				closeChat();
			} else {
				openChat();
			}
		});
		$panel.on('click', '.aichat-close', closeChat);
		$panel.on('click', '.aichat-expand', function () {
			$panel.toggleClass('aichat-expanded');
			$(this).find('i').toggleClass('fa-expand fa-compress');
		});
		$panel.on('click', '.aichat-toggle-sidebar', function () {
			$panel.toggleClass('aichat-sidebar-hidden');
		});
		$panel.on('click', '.aichat-new', function () {
			if (!state.controller) {
				showWelcome();
				$panel.find('.aichat-input').trigger('focus');
				if (window.innerWidth < 768) {
					$panel.addClass('aichat-sidebar-hidden');
				}
			}
		});
		$panel.on('click keydown', '.aichat-chat-item', function (e) {
			if (e.type === 'keydown' && e.key !== 'Enter') {
				return;
			}
			if ($(e.target).closest('.aichat-chat-actions').length) {
				return;
			}
			loadChat(parseInt($(this).attr('data-id'), 10));
		});
		$panel.on('click', '.aichat-item-pin', function () {
			togglePin(parseInt($(this).closest('.aichat-chat-item').attr('data-id'), 10));
		});
		$panel.on('click', '.aichat-item-delete', function () {
			deleteChat(parseInt($(this).closest('.aichat-chat-item').attr('data-id'), 10));
		});
		let searchTimer;
		$panel.on('input', '.aichat-search', function () {
			clearTimeout(searchTimer);
			const value = trim(this.value);
			searchTimer = setTimeout(function () {
				state.search = value;
				refreshChats().catch((error) => notify(error.message));
			}, 300);
		});
		$panel.on('click', '.aichat-title, .aichat-rename', function (e) {
			e.preventDefault();
			renameChat();
		});
		$panel.on('click', '.aichat-pin', function (e) {
			e.preventDefault();
			if (state.chat) {
				togglePin(state.chat.id);
			}
		});
		$panel.on('click', '.aichat-delete', function (e) {
			e.preventDefault();
			if (state.chat) {
				deleteChat(state.chat.id);
			}
		});
		$panel.on('click', '.aichat-export-md', function (e) {
			e.preventDefault();
			exportChat('md');
		});
		$panel.on('click', '.aichat-export-json', function (e) {
			e.preventDefault();
			exportChat('json');
		});
		$panel.on('click', '.aichat-delete-all', deleteAll);
		$panel.on('click', '.aichat-set-default', setDefaultModel);
		$panel.on('click', '.aichat-open-settings', openSettings);
		$panel.on('click', '.aichat-settings-cancel', function () {
			$panel.find('.aichat-settings-overlay').removeClass('show');
		});
		$panel.on('click', '.aichat-settings-save', saveSettings);
		$panel.on('click', '.aichat-suggestion', function () {
			$panel.find('.aichat-input').val($(this).text()).trigger('input').trigger('focus');
		});

		// composer
		$panel.on('input', '.aichat-composer .aichat-input', function () {
			this.style.height = 'auto';
			this.style.height = Math.min(this.scrollHeight, 220) + 'px';
			updateSendState();
		});
		$panel.on('keydown', '.aichat-composer .aichat-input', function (e) {
			if (e.key !== 'Enter' || e.isComposing) {
				return;
			}
			const sendKey = state.prefs.send_on_enter !== false ? !e.shiftKey : e.ctrlKey || e.metaKey;
			if (sendKey || ((e.ctrlKey || e.metaKey) && !e.shiftKey)) {
				e.preventDefault();
				if (!$panel.find('.aichat-send').prop('disabled') && !state.controller) {
					send();
				}
			}
		});
		$panel.on('click', '.aichat-send', function () {
			if (state.controller) {
				stop();
			} else {
				send();
			}
		});
		$panel.on('click', '.aichat-toggle-search, .aichat-toggle-image', function () {
			const isSearch = $(this).hasClass('aichat-toggle-search');
			if (isSearch) {
				state.searchOn = !state.searchOn;
				if (state.searchOn) {
					state.imageOn = false;
				}
			} else {
				state.imageOn = !state.imageOn;
				if (state.imageOn) {
					state.searchOn = false;
				}
			}
			$panel.find('.aichat-toggle-search').toggleClass('active', state.searchOn).attr('aria-pressed', String(state.searchOn));
			$panel.find('.aichat-toggle-image').toggleClass('active', state.imageOn).attr('aria-pressed', String(state.imageOn));
			$panel
				.find('.aichat-composer .aichat-input')
				.attr('placeholder', state.imageOn ? t('Describe the image to create') : state.searchOn ? t('Ask anything, the web is searched first') : t('Send a message'))
				.trigger('focus');
			updateSendState();
		});
		$panel.on('click', '.aichat-attach', function () {
			$panel.find('.aichat-file').trigger('click');
		});
		$panel.on('change', '.aichat-file', function () {
			addFiles(this.files);
			this.value = '';
		});
		$panel.on('click', '.aichat-attachment-remove', function (e) {
			e.preventDefault();
			e.stopPropagation();
			const id = String($(this).attr('data-id'));
			state.pending = state.pending.filter((file) => String(file.localId || file.id) !== id && String(file.id) !== id);
			renderPending();
		});
		$panel.on('paste', '.aichat-input', function (e) {
			const files = Array.from((e.originalEvent.clipboardData || {}).files || []);
			if (files.length) {
				e.preventDefault();
				addFiles(files);
			}
		});
		let dragDepth = 0;
		$panel.on('dragenter', function (e) {
			if (Array.from(e.originalEvent.dataTransfer.types || []).includes('Files')) {
				dragDepth++;
				$panel.addClass('aichat-dragging');
			}
		});
		$panel.on('dragleave', function () {
			dragDepth = Math.max(0, dragDepth - 1);
			if (!dragDepth) {
				$panel.removeClass('aichat-dragging');
			}
		});
		$panel.on('dragover', function (e) {
			e.preventDefault();
		});
		$panel.on('drop', function (e) {
			e.preventDefault();
			dragDepth = 0;
			$panel.removeClass('aichat-dragging');
			addFiles(e.originalEvent.dataTransfer.files);
		});

		// message actions
		$panel.on('click', '.aichat-copy', function () {
			const msg = findMessage(this);
			if (msg) {
				copyText(msg.content || '');
			}
		});
		$panel.on('click', '.aichat-copy-code', function () {
			copyText($(this).closest('.aichat-code').find('pre code').text());
		});
		$panel.on('click', '.aichat-edit', function () {
			editMessage(findMessage(this));
		});
		$panel.on('click', '.aichat-regenerate', function () {
			send({ regenerate: true, content: '' });
		});

		$(document).on('keydown', function (e) {
			if (e.key !== 'Escape' || !$('body').hasClass('aichat-open')) {
				return;
			}
			if ($panel.find('.aichat-settings-overlay').hasClass('show')) {
				$panel.find('.aichat-settings-overlay').removeClass('show');
			} else if (!$('.swal2-container').length) {
				closeChat();
			}
		});
	}
})();
