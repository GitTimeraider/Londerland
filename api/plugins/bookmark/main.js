/*
 * Bookmarks: fills the Bookmarks tab (api/v2/plugins/bookmark/page) with the bookmarks of the user's group, in
 * categories, with search. Admins edit, add, delete and drag bookmarks and categories on the page itself.
 * Talks only to api/v2/plugins/bookmark/*.
 */
(function () {
	'use strict';

	const API = 'api/v2/plugins/bookmark/';
	const COLLAPSED_KEY = 'londerland-bookmarks-collapsed';
	const ICONS = {
		materialize: 'mdi mdi-',
		fontawesome: 'fa fa-',
		'fontawesome-brands': 'fa-brands fa-',
		themify: 'ti-',
		simpleline: 'icon-',
		weathericon: 'wi wi-',
	};
	const state = {
		data: null, // { categories, bookmarks, canEdit, groups?, defaultGroup? }
		editing: false,
		query: '',
	};
	let $app = $();
	let sortables = [];

	$('body').arrive('#activeInfo', { onceOnly: true }, function () {
		if ((activeInfo.plugins || {})['BOOKMARK-enabled'] !== true) {
			return;
		}
		// the tab's page is (re)loaded into the page each time the tab opens or reloads
		$(document).arrive('.bookmarks-app', { existing: true }, function () {
			$app = $(this);
			load();
		});
		// "/" jumps to the search box while the Bookmarks tab is showing
		$(document).on('keydown.bookmarks', function (event) {
			if (event.key === '/' && !$(event.target).is('input, textarea, select, [contenteditable="true"]') && $app.is(':visible')) {
				const $search = $app.find('.bookmarks-search');
				if ($search.length) {
					event.preventDefault();
					$search.trigger('focus');
				}
			}
		});
	});

	/* ===================== helpers ===================== */

	function t(text) {
		return window.lang && typeof window.lang.translate === 'function' ? window.lang.translate(text) : text;
	}

	function esc(value) {
		return $('<div>').text(value == null ? '' : String(value)).html();
	}

	function setting(name) {
		const value = ((activeInfo.plugins || {}).includes || {})['BOOKMARK-' + name + '-include'];
		return value === true || value === 'true';
	}

	function layout() {
		const value = ((activeInfo.plugins || {}).includes || {})['BOOKMARK-layout-include'];
		return ['tiles', 'list', 'compact'].includes(value) ? value : 'tiles';
	}

	function storage(action, value) {
		try {
			if (action === 'get') {
				return JSON.parse(window.localStorage.getItem(COLLAPSED_KEY) || '[]');
			}
			window.localStorage.setItem(COLLAPSED_KEY, JSON.stringify(value));
		} catch (e) {
			return [];
		}
		return [];
	}

	function host(url) {
		try {
			return new URL(url, location.href).host || url;
		} catch (e) {
			return url;
		}
	}

	function apiError(xhr) {
		LonderlandApiError(xhr, 'Bookmarks');
	}

	/**
	 * Icon of a bookmark: one of Londerland's icon sets (prefix::name), a letter (alphanumeric::A), an image
	 * (url::address or a path/address) or, without one, the first letter of the name.
	 */
	function iconHtml(image, name) {
		const parts = String(image || '').split('::');
		if (parts.length === 2 && ICONS[parts[0]] && /^[\w\- ]+$/.test(parts[1])) {
			return '<i class="' + ICONS[parts[0]] + esc(parts[1]) + '"></i>';
		}
		if (parts.length === 2 && parts[0] === 'alphanumeric') {
			return '<span class="bookmarks-letter">' + esc(parts[1]) + '</span>';
		}
		const src = parts.length === 2 && parts[0] === 'url' ? parts[1] : image;
		if (src) {
			return '<img src="' + esc(src) + '" alt="" loading="lazy">';
		}
		return '<span class="bookmarks-letter">' + esc(String(name || '?').trim().charAt(0).toUpperCase()) + '</span>';
	}

	/* ===================== loading & drawing ===================== */

	function load() {
		const everything = state.editing ? '?all=1' : '';
		londerlandAPI2('GET', API + 'list' + everything)
			.done(function (data) {
				state.data = data.response.data;
				if (state.editing && !state.data.canEdit) {
					state.editing = false;
				}
				render();
			})
			.fail(function (xhr) {
				$app.html('<p class="bookmarks-empty">' + esc(t('Bookmarks could not be loaded.')) + '</p>');
				apiError(xhr);
			});
	}

	function render() {
		const data = state.data;
		if (!data || !$app.length) {
			return;
		}
		const collapsed = storage('get');
		const header = `
			<div class="bookmarks-header">
				${setting('search') ? `<div class="bookmarks-search-wrap"><i class="ti-search"></i><input type="search" class="form-control bookmarks-search" placeholder="${esc(t('Search bookmarks'))}  ( / )" value="${esc(state.query)}" autocomplete="off"></div>` : '<div></div>'}
				<div class="bookmarks-actions">
					${state.editing ? `<button type="button" class="btn btn-sm btn-success bookmarks-add-category"><i class="fa fa-plus"></i> <span lang="en">Category</span></button>` : ''}
					${state.editing ? `<button type="button" class="btn btn-sm btn-secondary bookmarks-import" title="${esc(t('Import a bookmarks file from your browser'))}"><i class="fa fa-upload"></i> <span lang="en">Import</span></button>` : ''}
					${data.bookmarks.length ? `<a class="btn btn-sm btn-secondary bookmarks-export" href="${API}export" download title="${esc(t('Download as a bookmarks file for your browser'))}"><i class="fa fa-download"></i> <span lang="en">Export</span></a>` : ''}
					${data.canEdit ? `<button type="button" class="btn btn-sm ${state.editing ? 'btn-info' : 'btn-secondary'} bookmarks-edit-toggle"><i class="fa ${state.editing ? 'fa-check' : 'fa-pencil'}"></i> <span lang="en">${state.editing ? 'Done' : 'Edit'}</span></button>` : ''}
				</div>
			</div>`;
		const categories = data.categories
			.map((category) => {
				const items = data.bookmarks.filter((bookmark) => bookmark.categoryId === category.categoryId);
				if (!items.length && !state.editing) {
					return '';
				}
				const isCollapsed = !state.editing && collapsed.includes(category.id);
				return `
				<section class="bookmarks-category${isCollapsed ? ' is-collapsed' : ''}" data-id="${category.id}" data-category-id="${category.categoryId}" data-name="${esc(category.name.toLowerCase())}">
					<div class="bookmarks-category-head">
						${state.editing ? '<span class="bookmarks-handle bookmarks-category-handle" title="' + esc(t('Drag to sort')) + '"><i class="fa fa-arrows"></i></span>' : ''}
						<button type="button" class="bookmarks-category-toggle" ${state.editing ? 'disabled' : ''}>
							<i class="fa fa-chevron-down"></i>
							<span class="bookmarks-category-name">${esc(category.name)}</span>
							<span class="bookmarks-count">${items.length}</span>
							${category.default && state.editing ? '<span class="badge text-bg-secondary" lang="en">default</span>' : ''}
						</button>
						${
							state.editing
								? `<span class="bookmarks-category-tools">
									<button type="button" class="btn btn-sm btn-success bookmarks-add" title="${esc(t('Add bookmark'))}"><i class="fa fa-plus"></i></button>
									<button type="button" class="btn btn-sm btn-secondary bookmarks-edit-category" title="${esc(t('Edit category'))}"><i class="fa fa-pencil"></i></button>
									${category.default ? '' : `<button type="button" class="btn btn-sm btn-danger bookmarks-delete-category" title="${esc(t('Delete category'))}"><i class="fa fa-trash"></i></button>`}
								</span>`
								: ''
						}
					</div>
					<div class="bookmarks-items bookmarks-layout-${layout()}" data-category-id="${category.categoryId}">
						${items.map(item).join('')}
						${state.editing && !items.length ? `<p class="bookmarks-empty-category" lang="en">Drag bookmarks here or click + to add one.</p>` : ''}
					</div>
				</section>`;
			})
			.join('');
		const empty = !data.bookmarks.length
			? `<p class="bookmarks-empty">${esc(t(data.canEdit ? 'No bookmarks yet. Click Edit, then + next to a category to add one.' : 'There are no bookmarks for you yet.'))}</p>`
			: '';
		$app.html(header + '<div class="bookmarks-categories">' + categories + '</div>' + empty + '<p class="bookmarks-empty bookmarks-no-results hidden" lang="en">Nothing matches your search.</p>');
		$app.toggleClass('is-editing', state.editing);
		applySearch();
		setupSorting();
	}

	function item(bookmark) {
		const newWindow = setting('newWindow') && !state.editing;
		const group = state.editing && state.data.groups ? (state.data.groups.find((g) => g.id === bookmark.groupId) || {}).name : '';
		return `
			<a class="bookmarks-item${bookmark.enabled ? '' : ' is-disabled'}" href="${esc(bookmark.url)}" ${newWindow ? 'target="_blank" rel="noopener noreferrer"' : 'rel="noreferrer"'}
				data-id="${bookmark.id}" data-search="${esc((bookmark.name + ' ' + bookmark.url).toLowerCase())}"
				style="background-color:${esc(bookmark.background)};color:${esc(bookmark.text)}" title="${esc(bookmark.name + '\n' + bookmark.url)}">
				<span class="bookmarks-icon">${iconHtml(bookmark.image, bookmark.name)}</span>
				<span class="bookmarks-text">
					<span class="bookmarks-name">${esc(bookmark.name)}</span>
					<span class="bookmarks-host">${esc(host(bookmark.url))}</span>
				</span>
				${state.editing ? `<span class="bookmarks-meta">${bookmark.enabled ? '' : '<i class="fa fa-eye-slash" title="' + esc(t('Switched off')) + '"></i> '}${esc(group || '')}</span>` : ''}
			</a>`;
	}

	/* ===================== search & collapsing ===================== */

	function applySearch() {
		const query = state.query.trim().toLowerCase();
		let shown = 0;
		$app.find('.bookmarks-category').each(function () {
			const $category = $(this);
			const categoryMatches = query !== '' && String($category.attr('data-name')).includes(query);
			let visible = 0;
			$category.find('.bookmarks-item').each(function () {
				const match = query === '' || categoryMatches || String($(this).attr('data-search')).includes(query);
				$(this).toggleClass('hidden', !match);
				visible += match ? 1 : 0;
			});
			$category.toggleClass('hidden', query !== '' && !visible);
			// while searching, every category with results is open
			$category.toggleClass('is-searching', query !== '');
			shown += visible;
		});
		$app.find('.bookmarks-no-results').toggleClass('hidden', query === '' || shown > 0);
	}

	$(document).on('input', '.bookmarks-search', function () {
		state.query = $(this).val();
		applySearch();
	});
	$(document).on('keydown', '.bookmarks-search', function (event) {
		if (event.key === 'Enter') {
			const first = $app.find('.bookmarks-item:not(.hidden)')[0];
			if (first) {
				event.preventDefault();
				first.click();
			}
		} else if (event.key === 'Escape') {
			state.query = '';
			$(this).val('');
			applySearch();
		}
	});
	$(document).on('click', '.bookmarks-category-toggle', function () {
		const $category = $(this).closest('.bookmarks-category').toggleClass('is-collapsed');
		const id = $category.data('id');
		const collapsed = storage('get').filter((value) => value !== id);
		if ($category.hasClass('is-collapsed')) {
			collapsed.push(id);
		}
		storage('set', collapsed);
	});

	// Opening a bookmark (also counted by the Achievements plugin); while editing, a click edits it
	$(document).on('click', '.bookmarks-item', function (event) {
		if (state.editing) {
			event.preventDefault();
			editBookmark(state.data.bookmarks.find((bookmark) => bookmark.id === $(this).data('id')));
			return;
		}
		$(document).trigger('londerland:bookmark-open');
	});

	/* ===================== editing ===================== */

	$(document).on('click', '.bookmarks-edit-toggle', function () {
		state.editing = !state.editing;
		state.query = '';
		load();
	});

	function setupSorting() {
		sortables.forEach((sortable) => sortable.destroy());
		sortables = [];
		if (!state.editing) {
			return;
		}
		londerlandLoadLibrary('sortable').then(function () {
			$app.find('.bookmarks-items').each(function () {
				sortables.push(
					Sortable.create(this, {
						group: 'bookmarks',
						animation: 150,
						draggable: '.bookmarks-item',
						onEnd: saveBookmarkOrder,
					})
				);
			});
			const categories = $app.find('.bookmarks-categories')[0];
			if (categories) {
				sortables.push(
					Sortable.create(categories, {
						animation: 150,
						handle: '.bookmarks-category-handle',
						draggable: '.bookmarks-category',
						onEnd: saveCategoryOrder,
					})
				);
			}
		});
	}

	function saveBookmarkOrder() {
		const items = [];
		$app.find('.bookmarks-items').each(function () {
			const categoryId = $(this).data('category-id');
			$(this)
				.find('.bookmarks-item')
				.each(function () {
					items.push({ id: $(this).data('id'), categoryId: categoryId });
				});
		});
		londerlandAPI2('PUT', API + 'bookmarks/order', { items: items }).done(load).fail(apiError);
	}

	function saveCategoryOrder() {
		const ids = $app
			.find('.bookmarks-category')
			.map(function () {
				return $(this).data('id');
			})
			.get();
		londerlandAPI2('PUT', API + 'categories/order', { ids: ids }).done(load).fail(apiError);
	}

	function modal(title, body, onSave) {
		$('#bookmarks-modal').remove();
		const $modal = $(`
			<div class="modal fade" id="bookmarks-modal" tabindex="-1" aria-hidden="true">
				<div class="modal-dialog modal-dialog-centered">
					<form class="modal-content bookmarks-form" autocomplete="off">
						<div class="modal-header">
							<h4 class="modal-title">${esc(title)}</h4>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="${esc(t('Close'))}"></button>
						</div>
						<div class="modal-body">${body}</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal" lang="en">Cancel</button>
							<button type="submit" class="btn btn-info" lang="en">Save</button>
						</div>
					</form>
				</div>
			</div>`).appendTo('body');
		const instance = new bootstrap.Modal($modal[0]);
		$modal.on('hidden.bs.modal', function () {
			$modal.remove();
		});
		$modal.find('form').on('submit', function (event) {
			event.preventDefault();
			const $save = $modal.find('[type="submit"]').prop('disabled', true);
			onSave($modal)
				.done(function () {
					instance.hide();
					load();
				})
				.fail(apiError)
				.always(function () {
					$save.prop('disabled', false);
				});
		});
		instance.show();
		return $modal;
	}

	function editBookmark(bookmark, categoryId) {
		const data = state.data;
		const isNew = !bookmark;
		bookmark = bookmark || {
			name: '',
			url: '',
			image: '',
			background: '#2f323e',
			text: '#ffffff',
			groupId: data.defaultGroup,
			enabled: true,
			categoryId: categoryId,
		};
		const options = (list, selected, value, label) => list.map((entry) => `<option value="${esc(entry[value])}" ${entry[value] === selected ? 'selected' : ''}>${esc(entry[label])}</option>`).join('');
		const $modal = modal(
			t(isNew ? 'Add bookmark' : 'Edit bookmark'),
			`
			<div class="row g-3">
				<div class="col-12"><label class="form-label" lang="en">Name</label><input class="form-control" name="name" maxlength="100" required value="${esc(bookmark.name)}"></div>
				<div class="col-12"><label class="form-label" lang="en">Address</label><input class="form-control" name="url" required placeholder="https://..." value="${esc(bookmark.url)}"></div>
				<div class="col-sm-6"><label class="form-label" lang="en">Category</label><select class="form-select" name="categoryId">${options(data.categories, bookmark.categoryId, 'categoryId', 'name')}</select></div>
				<div class="col-sm-6"><label class="form-label" lang="en">Visible for (and higher groups)</label><select class="form-select" name="groupId">${options(data.groups || [], bookmark.groupId, 'id', 'name')}</select></div>
				<div class="col-12">
					<label class="form-label" lang="en">Icon</label>
					<div class="bookmarks-icon-field">
						<span class="bookmarks-icon bookmarks-icon-preview"></span>
						<input class="form-control" name="image" placeholder="${esc(t('Empty = first letter of the name'))}" value="${esc(bookmark.image)}">
					</div>
					<div class="row g-2 mt-1">
						<div class="col-sm-6"><select class="bookmarks-choose-icon"></select></div>
						<div class="col-sm-6"><select class="bookmarks-choose-image"></select></div>
					</div>
				</div>
				<div class="col-sm-6"><label class="form-label" lang="en">Background colour</label><input class="form-control bookmarks-color" name="background" value="${esc(bookmark.background)}"></div>
				<div class="col-sm-6"><label class="form-label" lang="en">Text colour</label><input class="form-control bookmarks-color" name="text" value="${esc(bookmark.text)}"></div>
				<div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="enabled" id="bookmarks-enabled" ${bookmark.enabled ? 'checked' : ''}><label class="form-check-label" for="bookmarks-enabled" lang="en">Switched on (off = only admins see it, while editing)</label></div></div>
				${isNew ? '' : '<div class="col-12"><button type="button" class="btn btn-sm btn-outline-danger bookmarks-delete"><i class="fa fa-trash"></i> <span lang="en">Delete this bookmark</span></button></div>'}
			</div>`,
			function ($form) {
				const body = {
					name: $form.find('[name="name"]').val(),
					url: $form.find('[name="url"]').val(),
					categoryId: parseInt($form.find('[name="categoryId"]').val(), 10),
					groupId: parseInt($form.find('[name="groupId"]').val(), 10),
					image: $form.find('[name="image"]').val(),
					background: $form.find('[name="background"]').val(),
					text: $form.find('[name="text"]').val(),
					enabled: $form.find('[name="enabled"]').is(':checked'),
				};
				return isNew ? londerlandAPI2('POST', API + 'bookmarks', body) : londerlandAPI2('PUT', API + 'bookmarks/' + bookmark.id, body);
			}
		);
		const preview = function () {
			$modal.find('.bookmarks-icon-preview').html(iconHtml($modal.find('[name="image"]').val(), $modal.find('[name="name"]').val()));
		};
		$modal.find('[name="image"], [name="name"]').on('input', preview);
		preview();
		// Londerland's own icon and image lists; picking one fills the icon field
		const chooser = function (selector, url, format, placeholder) {
			const $select = $modal.find(selector);
			initRemoteChooser($select, url, format, placeholder).then(function () {
				if ($select[0] && $select[0].tomselect) {
					$select[0].tomselect.on('change', function (value) {
						if (value) {
							$modal.find('[name="image"]').val(value);
							preview();
						}
					});
				}
			});
		};
		chooser('.bookmarks-choose-icon', 'api/v2/icon', formatIcon, 'Search for an icon');
		chooser('.bookmarks-choose-image', 'api/v2/image/select', formatImage, 'Search for an image');
		initColorPickers($modal.find('.bookmarks-color'), { swatches: ['#2f323e', '#1e88e5', '#00897b', '#43a047', '#f4b400', '#fb8c00', '#e53935', '#8e24aa', '#ffffff', '#000000'] });
		$modal.find('.bookmarks-delete').on('click', function () {
			if (confirm(t('Delete this bookmark?'))) {
				londerlandAPI2('DELETE', API + 'bookmarks/' + bookmark.id)
					.done(function () {
						bootstrap.Modal.getInstance($modal[0]).hide();
						load();
					})
					.fail(apiError);
			}
		});
	}

	function editCategory(category) {
		const isNew = !category;
		modal(
			t(isNew ? 'Add category' : 'Edit category'),
			`
			<div class="row g-3">
				<div class="col-12"><label class="form-label" lang="en">Name</label><input class="form-control" name="name" maxlength="100" required value="${esc(category ? category.name : '')}"></div>
				${
					isNew || category.default
						? ''
						: '<div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="default" id="bookmarks-default"><label class="form-check-label" for="bookmarks-default" lang="en">Default category (bookmarks of a deleted category move here)</label></div></div>'
				}
			</div>`,
			function ($form) {
				const body = { name: $form.find('[name="name"]').val(), default: $form.find('[name="default"]').is(':checked') };
				return isNew ? londerlandAPI2('POST', API + 'categories', body) : londerlandAPI2('PUT', API + 'categories/' + category.id, body);
			}
		);
	}

	// A bookmarks file exported by a browser (Chrome, Edge, Firefox, Safari: "Export bookmarks" gives an .html file)
	function importBookmarks() {
		const data = state.data;
		const options = (data.groups || []).map((group) => `<option value="${group.id}" ${group.id === data.defaultGroup ? 'selected' : ''}>${esc(group.name)}</option>`).join('');
		const $modal = modal(
			t('Import bookmarks'),
			`
			<div class="row g-3">
				<div class="col-12">
					<label class="form-label" lang="en">Bookmarks file (.html)</label>
					<input class="form-control" type="file" name="file" accept=".html,.htm,text/html" required>
					<div class="form-text" lang="en">In your browser: Bookmarks manager > Export bookmarks. Chrome, Edge, Firefox and Safari all make this file.</div>
				</div>
				<div class="col-sm-6"><label class="form-label" lang="en">Visible for (and higher groups)</label><select class="form-select" name="groupId">${options}</select></div>
				<div class="col-sm-6">
					<label class="form-label" lang="en">Categories</label>
					<select class="form-select" name="mode">
						<option value="folders" lang="en">One per folder</option>
						<option value="one" lang="en">All in one category</option>
					</select>
				</div>
				<div class="col-12 bookmarks-import-category hidden"><label class="form-label" lang="en">Category name</label><input class="form-control" name="category" maxlength="100" value="${esc(t('Imported'))}"></div>
				<div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="skipDuplicates" id="bookmarks-skip-duplicates" checked><label class="form-check-label" for="bookmarks-skip-duplicates" lang="en">Skip links that are already a bookmark</label></div></div>
			</div>`,
			function ($form) {
				const deferred = $.Deferred();
				const file = $form.find('[name="file"]')[0].files[0];
				if (!file) {
					return deferred.reject({ responseJSON: { response: { message: t('Choose a bookmarks file') } } }).promise();
				}
				const reader = new FileReader();
				reader.onload = function () {
					londerlandAPI2('POST', API + 'import', {
						html: String(reader.result || ''),
						groupId: parseInt($form.find('[name="groupId"]').val(), 10),
						category: $form.find('[name="mode"]').val() === 'one' ? $form.find('[name="category"]').val() : '',
						skipDuplicates: $form.find('[name="skipDuplicates"]').is(':checked'),
					})
						.done(function (result) {
							messageSingle(t('Import bookmarks'), result.response.message, activeInfo.settings.notifications.position, '#FFF', 'success', '8000');
							deferred.resolve(result);
						})
						.fail(function (xhr) {
							deferred.reject(xhr);
						});
				};
				reader.onerror = function () {
					deferred.reject({ responseJSON: { response: { message: t('The file could not be read') } } });
				};
				reader.readAsText(file, 'UTF-8');
				return deferred.promise();
			}
		);
		$modal.find('[type="submit"]').text(t('Import'));
		$modal.find('[name="mode"]').on('change', function () {
			$modal.find('.bookmarks-import-category').toggleClass('hidden', $(this).val() !== 'one');
		});
	}

	function categoryOf(element) {
		const id = $(element).closest('.bookmarks-category').data('id');
		return state.data.categories.find((category) => category.id === id);
	}

	$(document).on('click', '.bookmarks-add-category', function () {
		editCategory(null);
	});
	$(document).on('click', '.bookmarks-import', function () {
		importBookmarks();
	});
	$(document).on('click', '.bookmarks-edit-category', function () {
		editCategory(categoryOf(this));
	});
	$(document).on('click', '.bookmarks-add', function () {
		editBookmark(null, categoryOf(this).categoryId);
	});
	$(document).on('click', '.bookmarks-delete-category', function () {
		const category = categoryOf(this);
		if (confirm(t('Delete this category? Its bookmarks move to the default category.'))) {
			londerlandAPI2('DELETE', API + 'categories/' + category.id).done(load).fail(apiError);
		}
	});
})();
