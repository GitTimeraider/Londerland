/*
 * Small helpers that replace former jQuery plugins (serializeToJSON, blockUI).
 */

// Form to nested object: "a.b" names become {a: {b: ...}}, "a[key]" becomes {a: {key: ...}},
// checkboxes become booleans, "true"/"false" strings are parsed and unchecked radios are null.
$.fn.serializeToJSON = function () {
	const result = {};
	const form = this.first();
	const fields = form.is('form') ? form.find('input, select, textarea') : form.find('input, select, textarea').addBack('input, select, textarea');
	const seenRadios = new Set();
	fields.each(function () {
		const field = $(this);
		const name = this.name;
		if (!name || this.disabled || /^(?:submit|button|image|reset|file)$/i.test(this.type)) {
			return;
		}
		let value;
		if (this.type === 'radio') {
			if (seenRadios.has(name)) {
				return;
			}
			seenRadios.add(name);
			value = form.find('input[type="radio"][name="' + name + '"]:checked').val() ?? null;
		} else if (this.type === 'checkbox') {
			if (!this.checked) {
				return;
			}
			value = true;
		} else if (field.is('select[multiple]')) {
			value = field.val();
			value = value && value.length ? value : null;
		} else {
			value = field.val();
			if (typeof value === 'string') {
				value = value.replace(/\r?\n/g, '\r\n');
			}
		}
		if (typeof value === 'string' && /^(true|false)$/i.test(value)) {
			value = value.toLowerCase() === 'true';
		}
		const path = name.split('.');
		let target = result;
		path.forEach(function (part, index) {
			const keyed = /^(.+)\[(\w+)\]$/.exec(part);
			if (keyed) {
				target[keyed[1]] = target[keyed[1]] || {};
				target = target[keyed[1]];
				part = keyed[2];
			}
			if (index === path.length - 1) {
				target[part] = value;
			} else {
				target[part] = target[part] || {};
				target = target[part];
			}
		});
	});
	return result;
};

// Overlay a loading spinner on an element (replaces jQuery blockUI)
$.fn.block = function (options = {}) {
	return this.each(function () {
		const element = $(this);
		if (element.children('.org-block-overlay').length) {
			return;
		}
		if (element.css('position') === 'static') {
			element.addClass('org-block-relative');
		}
		const message = options.message !== undefined ? options.message : '<i class="fa fa-spinner fa-spin"></i>';
		$('<div class="org-block-overlay"></div>').append($('<div class="org-block-message"></div>').html(message)).appendTo(element);
	});
};

$.fn.unblock = function () {
	return this.each(function () {
		$(this).removeClass('org-block-relative').children('.org-block-overlay').remove();
	});
};

$.blockUI = function (options = {}) {
	$('body').block(options);
	$('body > .org-block-overlay').addClass('org-block-page');
};

$.unblockUI = function () {
	$('body').unblock();
};

// Copy text to the clipboard (replaces clipboard.js)
function copyToClipboard(text) {
	if (navigator.clipboard && window.isSecureContext) {
		return navigator.clipboard.writeText(text);
	}
	// Fallback for plain-http installs where the async clipboard API is unavailable
	const area = $('<textarea class="visually-hidden"></textarea>').val(text).appendTo('body');
	area[0].select();
	document.execCommand('copy');
	area.remove();
	return Promise.resolve();
}

// Render .js-switch checkboxes as Bootstrap switches; data-color / data-secondary-color set the on/off colours
function initSwitches(root = document) {
	$(root).find('.js-switch').each(function () {
		if (this.dataset.switchReady) {
			return;
		}
		this.dataset.switchReady = 'true';
		const wrapper = $('<span class="form-check form-switch org-switch"></span>');
		if (this.dataset.color) {
			wrapper[0].style.setProperty('--org-switch-on', this.dataset.color);
		}
		if (this.dataset.secondaryColor) {
			wrapper[0].style.setProperty('--org-switch-off', this.dataset.secondaryColor);
		}
		if (this.dataset.size === 'small') {
			wrapper.addClass('org-switch-sm');
		}
		$(this).addClass('form-check-input').attr('role', 'switch').wrap(wrapper);
	});
}

// Overlay scrollbars for scrollable areas; elements that already have them are left alone
function customScrollbars(selector, autoHide = 'leave') {
	const { OverlayScrollbars } = OverlayScrollbarsGlobal;
	$(selector).each(function () {
		if (!OverlayScrollbars(this)) {
			OverlayScrollbars(this, { scrollbars: { autoHide: autoHide } });
		}
	});
}

// Colour pickers for text inputs (replaces ColorPickerSliders). The input keeps holding the hex value;
// picking a colour updates it and fires "input" and "change", typing a value updates the picker.
function initColorPickers(selector, options = {}) {
	if (!window.Pickr) {
		londerlandLoadLibrary('pickr').then(function () {
			initColorPickers(selector, options);
		});
		return;
	}
	$(selector).each(function () {
		if (this.pickr) {
			this.pickr.setColor(this.value || null, true);
			return;
		}
		const input = $(this);
		const anchor = $('<span class="org-color-picker"></span>').insertAfter(input);
		const paint = function (value) {
			input.css({ 'border-right': value ? '2.5em solid ' + value : '' });
		};
		const pickr = Pickr.create({
			el: anchor[0],
			theme: 'nano',
			container: options.inline ? anchor.parent()[0] : 'body',
			inline: !!options.inline,
			showAlways: !!options.inline,
			useAsButton: !options.inline,
			default: this.value || null,
			defaultRepresentation: 'HEXA',
			comparison: false,
			swatches: options.swatches || null,
			components: {
				preview: true,
				opacity: true,
				hue: true,
				interaction: { input: true, clear: !options.inline },
			},
		});
		this.pickr = pickr;
		if (!options.inline) {
			anchor.addClass('org-color-swatch').on('click', function () {
				pickr.show();
			});
		}
		pickr.on('change', function (color) {
			const value = color ? color.toHEXA().toString() : '';
			input.val(value);
			paint(value);
			input.trigger('input');
			if (options.onChange) {
				options.onChange(value);
			}
		});
		pickr.on('changestop', function () {
			input.trigger('change');
		});
		pickr.on('clear', function () {
			input.val('');
			paint('');
			input.trigger('input').trigger('change');
		});
		input.on('input change', function (e) {
			if (e.isTrigger) {
				// our own events, the picker already has this colour - unless .val() was set from code
				if (pickr.getColor() && pickr.getColor().toHEXA().toString() === this.value) {
					return;
				}
			}
			paint(this.value);
			if (this.value === '' || /^#?[0-9a-f]{3,8}$/i.test(this.value) || /^(rgb|hsl)a?\(/i.test(this.value)) {
				pickr.setColor(this.value || null, true);
			}
		});
		paint(this.value);
	});
}

// Wrap what a formatIcon/formatImage style function returns ({id, text} -> text or jQuery) for Tom Select
function tomSelectRenderer(format) {
	return function (data, escape) {
		const out = format ? format({ id: data.id ?? data.value, text: data.text }) : data.text;
		return typeof out === 'string' ? '<div>' + escape(out) + '</div>' : $('<div></div>').append(out)[0];
	};
}

// Searchable single select fed page by page from an Londerland API list endpoint (replaces Select2 ajax pickers)
function initRemoteChooser(selector, url, format, placeholder) {
	$(selector).each(function () {
		if (this.tomselect) {
			return;
		}
		// the old "Select or type ..." entry is now the placeholder
		$(this).find('option:not([value])').remove();
		new TomSelect(this, {
			valueField: 'id',
			labelField: 'text',
			searchField: ['text'],
			placeholder: window.lang ? window.lang.translate(placeholder) : placeholder,
			maxOptions: null,
			preload: 'focus',
			// list the first page before anything is typed, like the old Select2 pickers did
			shouldLoad: function () {
				return true;
			},
			plugins: ['virtual_scroll'],
			firstUrl: function (query) {
				return url + (url.includes('?') ? '&' : '?') + 'search=' + encodeURIComponent(query) + '&page=1';
			},
			load: function (query, callback) {
				const pageUrl = this.getUrl(query);
				const page = parseInt(new URL(pageUrl, location.href).searchParams.get('page'), 10);
				$.getJSON(pageUrl).done((data) => {
					if (page * 20 < data.response.data.total) {
						this.setNextUrl(query, pageUrl.replace(/page=\d+$/, 'page=' + (page + 1)));
					}
					callback(data.response.data.results);
				}).fail(function () {
					callback();
				});
			},
			render: {
				option: tomSelectRenderer(format),
				item: tomSelectRenderer(format),
			},
		});
	});
}

// Multi select with optional free-typed entries (replaces Select2 for "select2" settings fields)
function initMultiSelect(selector, settings = {}) {
	$(selector).each(function () {
		if (this.tomselect) {
			return;
		}
		const plugins = ['remove_button'];
		if (settings.allowClear) {
			plugins.push('clear_button');
		}
		const select = new TomSelect(this, {
			plugins: plugins,
			create: !!settings.tags,
			createOnBlur: !!(settings.tags && settings.selectOnClose),
			closeAfterSelect: settings.closeOnSelect !== false,
			maxOptions: null,
			hidePlaceholder: true,
		});
		if (settings.selectionCssClass) {
			$(select.wrapper).addClass(settings.selectionCssClass);
		}
	});
}

// Reset Tom Select controls (or plain selects) to nothing selected
function clearSelect(selector) {
	$(selector).each(function () {
		if (this.tomselect) {
			this.tomselect.clear(true);
		} else {
			$(this).val(null);
		}
	});
}

// Browser details from Bowser 2 in the shape Londerland used with Bowser 1
const browserInfo = (function () {
	const parsed = bowser.parse(navigator.userAgent);
	return {
		name: parsed.browser.name,
		version: parsed.browser.version,
		osname: parsed.os.name,
		osversion: parsed.os.version,
		mobile: parsed.platform.type === 'mobile',
		tablet: parsed.platform.type === 'tablet',
	};
})();

// Ace loads its modes and themes on demand from the bundled copy
if (window.ace) {
	ace.config.set('basePath', 'assets/vendor/ace');
}


// Londerland's theme styles active tabs as li.active (Bootstrap 3); Bootstrap 5 marks the link instead
$(document).on('shown.bs.tab', function (e) {
	$(e.target).closest('li').addClass('active').siblings('li').removeClass('active');
});
// ...and the menus are built with li.active, which Bootstrap 5 does not see: it would not hide the pane that was
// shown first, which then stays above every other pane. Hide the other active panes of the group ourselves.
$(document).on('show.bs.tab', function (e) {
	const selector = e.target.getAttribute('data-bs-target') || e.target.getAttribute('href') || '';
	const pane = selector.charAt(0) === '#' ? document.getElementById(selector.slice(1)) : null;
	if (pane) {
		$(pane).siblings('.tab-pane.active').removeClass('active show');
	}
});

// Big libraries that only a few pages use (code editor, e-mail editor, user table) are not part of the
// page load; they are fetched the first time a page asks for them. Returns a Promise; later calls reuse it.
const londerlandLibraries = {
	ace: { js: ['assets/vendor/ace/ace.js'], css: [] },
	tinymce: { js: ['assets/vendor/tinymce/tinymce.min.js'], css: [] },
	tabulator: { js: ['assets/vendor/tabulator/tabulator.min.js'], css: ['assets/vendor/tabulator/tabulator_bootstrap5.min.css'] },
	pickr: { js: ['assets/vendor/pickr/pickr.min.js'], css: ['assets/vendor/pickr/nano.min.css'] },
	dropzone: { js: ['assets/vendor/dropzone/dropzone-min.js'], css: ['assets/vendor/dropzone/dropzone.css'] },
	datatables: { js: ['assets/vendor/datatables/dataTables.min.js', 'assets/vendor/datatables/dataTables.bootstrap5.min.js'], css: ['assets/vendor/datatables/dataTables.bootstrap5.min.css'] },
	sortable: { js: ['assets/vendor/sortablejs/Sortable.min.js'], css: [] },
	pusher: { js: ['assets/vendor/pusher-js/pusher.min.js'], css: [] },
};
const londerlandLibraryLoads = {};
function londerlandLoadLibrary(name) {
	if (!londerlandLibraryLoads[name]) {
		const library = londerlandLibraries[name];
		// Stylesheets go before the theme stylesheet, where they used to be, so the theme still overrides them
		const themeStyle = document.getElementById('style');
		library.css.forEach(function (href) {
			if (!document.querySelector('link[href="' + href + '"]')) {
				const link = document.createElement('link');
				link.rel = 'stylesheet';
				link.href = href;
				document.head.insertBefore(link, themeStyle);
			}
		});
		// Scripts one after the other: later files of a library expect the earlier ones
		londerlandLibraryLoads[name] = library.js.reduce(function (previous, src) {
			return previous.then(function () {
				return new Promise(function (resolve, reject) {
					const script = document.createElement('script');
					script.src = src;
					script.onload = resolve;
					script.onerror = function () {
						reject(new Error('Could not load ' + src));
					};
					document.head.appendChild(script);
				});
			});
		}, Promise.resolve());
		londerlandLibraryLoads[name].catch(function (error) {
			// Allow a new try (for example after a network hiccup)
			delete londerlandLibraryLoads[name];
			console.error(error);
		});
	}
	return londerlandLibraryLoads[name];
}

