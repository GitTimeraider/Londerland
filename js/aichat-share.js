/*
 * Public page of a shared AI chat (api/v2/plugins/aichat/shared/<token>). The server writes every message as escaped
 * text; this turns the answers' Markdown into HTML, cleaned by DOMPurify down to text formatting, code, tables and
 * links (no pictures, forms, frames or styles). Without JavaScript the page shows the plain text.
 */
(function () {
	'use strict';

	if (!window.marked || !window.DOMPurify) {
		return;
	}
	const ALLOWED_TAGS = ['p', 'br', 'strong', 'b', 'em', 'i', 'del', 's', 'code', 'pre', 'blockquote', 'ul', 'ol', 'li', 'a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'hr', 'sup', 'sub', 'span', 'div'];
	const ALLOWED_ATTR = ['href', 'title', 'colspan', 'rowspan', 'align', 'start', 'class'];

	document.querySelectorAll('.shared-content[data-markdown="1"]').forEach(function (element) {
		const html = window.marked.parse(element.textContent || '', { gfm: true, breaks: true });
		const clean = window.DOMPurify.sanitize(html, {
			ALLOWED_TAGS: ALLOWED_TAGS,
			ALLOWED_ATTR: ALLOWED_ATTR,
			ALLOWED_URI_REGEXP: /^(?:https?:|mailto:|#)/i,
		});
		element.innerHTML = clean;
		element.classList.add('is-markdown');
		element.querySelectorAll('a[href]').forEach(function (link) {
			link.setAttribute('target', '_blank');
			link.setAttribute('rel', 'noopener noreferrer nofollow ugc');
		});
	});
	document.querySelectorAll('time[datetime]').forEach(function (time) {
		const date = new Date(time.getAttribute('datetime'));
		if (!isNaN(date.getTime())) {
			time.textContent = date.toLocaleDateString(undefined, { dateStyle: 'long' });
		}
	});
})();
