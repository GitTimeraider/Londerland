/*
 * Londerland is English only. Many places still pass their text through window.lang.translate(),
 * which returns it unchanged.
 */
window.lang = {
	currentLang: 'en',
	translate(text) {
		return text;
	},
};
