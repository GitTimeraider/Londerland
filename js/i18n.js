/*
 * Organizr translations (replaces jquery-lang).
 *
 * Elements carrying lang="en" have their own text nodes and their title/alt/placeholder/href
 * attributes (plus the value of button-like inputs) translated into the current language.
 * Content added to the page later is translated automatically through a MutationObserver.
 * Language packs are JSON files of the form {"token": {"English text": "Translation"}}.
 */
class Lang {
	constructor() {
		this.defaultLang = 'en';
		this.currentLang = 'en';
		this.pack = {};
		this._dynamic = {};
		this._loading = {};
		this._originals = new WeakMap();
		this.attrList = ['title', 'alt', 'placeholder', 'href'];
		this.cookieName = 'organizrLanguage';
		this.cookieExpiry = 365;
		this.cookiePath = '/';
	}

	init(options = {}) {
		if (options.cookie) {
			this.cookieName = options.cookie.name || this.cookieName;
			this.cookieExpiry = options.cookie.expiry || this.cookieExpiry;
			this.cookiePath = options.cookie.path || this.cookiePath;
		}
		let lang = options.currentLang || this.defaultLang;
		if (options.allowCookieOverride && typeof Cookies !== 'undefined' && Cookies.get(this.cookieName)) {
			lang = Cookies.get(this.cookieName);
		}
		const start = () => {
			this._observe();
			this.change(lang);
		};
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', start);
		} else {
			start();
		}
	}

	// Register where the language pack for `lang` can be loaded from
	dynamic(lang, path) {
		if (lang && path) {
			this._dynamic[lang] = path;
		}
	}

	loadPack(lang) {
		if (!this._loading[lang]) {
			this._loading[lang] = fetch(this._dynamic[lang], { credentials: 'same-origin' })
				.then((response) => {
					if (!response.ok) {
						throw new Error('Language pack could not load from: ' + this._dynamic[lang]);
					}
					return response.json();
				})
				.then((pack) => {
					this.pack[lang] = pack;
					return pack;
				});
		}
		return this._loading[lang];
	}

	change(lang, selector, callback) {
		if (lang !== this.defaultLang && !this.pack[lang]) {
			if (!this._dynamic[lang]) {
				const error = 'No language pack defined for: ' + lang;
				if (callback) {
					callback(error, lang, selector);
				}
				console.warn(error);
				return;
			}
			this.loadPack(lang)
				.then(() => this.change(lang, selector, callback))
				.catch((error) => {
					console.warn(error.message);
					if (callback) {
						callback(error.message, lang, selector);
					}
				});
			return;
		}
		this.currentLang = lang;
		const root = selector ? document.querySelectorAll(selector) : [document.documentElement];
		root.forEach((element) => this._translateTree(element));
		if (typeof Cookies !== 'undefined') {
			Cookies.set(this.cookieName, lang, { expires: this.cookieExpiry, path: this.cookiePath });
		}
		if (callback) {
			callback(false, lang, selector);
		}
	}

	translate(text, lang) {
		lang = lang || this.currentLang;
		if (lang === this.defaultLang || !this.pack[lang]) {
			return text;
		}
		const translation = this.pack[lang].token ? this.pack[lang].token[text] : undefined;
		if (!translation && typeof langStrings !== 'undefined') {
			// Collect untranslated strings so they can be exported for translators
			langStrings.token[text] = text;
		}
		return translation || text;
	}

	_observe() {
		new MutationObserver((mutations) => {
			for (const mutation of mutations) {
				for (const node of mutation.addedNodes) {
					if (node.nodeType === Node.ELEMENT_NODE) {
						this._translateTree(node);
					}
				}
			}
		}).observe(document.documentElement, { childList: true, subtree: true });
	}

	_translateTree(root) {
		if (root.matches && root.matches('[lang]:not(html)')) {
			this._translateElement(root);
		}
		root.querySelectorAll && root.querySelectorAll('[lang]').forEach((element) => this._translateElement(element));
	}

	_translateElement(element) {
		const elementLang = element.getAttribute('lang');
		if (!this._originals.has(element)) {
			// Only content written in the default language can be translated
			if (elementLang !== this.defaultLang) {
				return;
			}
			this._originals.set(element, this._capture(element));
		}
		if (elementLang === this.currentLang) {
			return;
		}
		const original = this._originals.get(element);
		const lang = this.currentLang;
		for (const [attr, value] of Object.entries(original.attrs)) {
			element.setAttribute(attr, this.translate(value, lang));
		}
		if (element.getAttribute('data-lang-content') !== 'false') {
			if (original.value !== undefined) {
				element.value = this.translate(original.value, lang);
			} else {
				this._translateText(element, original.texts, lang);
			}
		}
		element.setAttribute('lang', lang);
	}

	_capture(element) {
		const original = { attrs: {}, texts: [] };
		for (const attr of this.attrList) {
			if (element.hasAttribute(attr) && element.getAttribute(attr) !== '') {
				original.attrs[attr] = element.getAttribute(attr);
			}
		}
		if (element.tagName === 'INPUT' && ['button', 'submit', 'hidden', 'reset'].includes(element.type)) {
			original.value = element.value;
		} else {
			const textNodes = [...element.childNodes].filter((node) => node.nodeType === Node.TEXT_NODE);
			const token = element.dataset.langToken;
			original.texts = textNodes.map((node) => ({ node, text: node.data, token: textNodes.length === 1 ? token : undefined }));
		}
		return original;
	}

	_translateText(element, texts, lang) {
		for (const entry of texts) {
			const source = (entry.token || entry.text).trim();
			if (lang === this.defaultLang || !source) {
				entry.node.data = entry.text;
				continue;
			}
			const translation = this.translate(source, lang);
			if (/<[^>]+>/.test(translation)) {
				element.innerHTML = translation;
				return;
			}
			entry.node.data = entry.text.split(entry.text.trim()).join(translation);
		}
	}
}
