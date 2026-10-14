/*
 * Londerland's service worker, registered only while Settings > Customize > Website > Install as App is on.
 * It stores nothing but the offline page: every page and every request still goes to the server, so nothing a
 * signed-in user sees is kept on the device. When the server cannot be reached, a page load shows the offline page.
 */
const CACHE = 'londerland-offline-v1';
const OFFLINE_URL = 'offline.html';

self.addEventListener('install', (event) => {
	event.waitUntil(
		caches
			.open(CACHE)
			.then((cache) => cache.add(new Request(OFFLINE_URL, { cache: 'reload' })))
			.then(() => self.skipWaiting())
	);
});

self.addEventListener('activate', (event) => {
	event.waitUntil(
		caches
			.keys()
			.then((keys) => Promise.all(keys.filter((key) => key.startsWith('londerland-') && key !== CACHE).map((key) => caches.delete(key))))
			.then(() => self.clients.claim())
	);
});

self.addEventListener('fetch', (event) => {
	// Only whole page loads of this site (the main page, and pages of this site opened in a tab); everything else
	// is left to the browser as if there were no service worker
	if (event.request.mode !== 'navigate' || new URL(event.request.url).origin !== self.location.origin) {
		return;
	}
	event.respondWith(fetch(event.request).catch(() => caches.match(OFFLINE_URL).then((page) => page || Response.error())));
});
