<?php

/**
 * Settings > Customize > Website: the announcement bar, search engine and link preview tags, robots.txt and sitemap.xml,
 * and installing Londerland as an app (web app manifest and a service worker with an offline page).
 * Everything here is written into index.php by the server, so a site without an announcement or with SEO and the app
 * switched off sends no extra bytes and makes no extra requests.
 */
trait SiteFunctions
{
	private const ANNOUNCEMENT_STYLES = [
		'info' => ['#1f6fb2', '#ffffff'],
		'success' => ['#2e7d4f', '#ffffff'],
		'warning' => ['#f0ad4e', '#1f1f1f'],
		'danger' => ['#c0392b', '#ffffff'],
		'dark' => ['#2b2f36', '#ffffff'],
	];

	public function getCustomizeSite()
	{
		$groupOptions = [];
		foreach ($this->groupSelect() as $group) {
			$groupOptions[] = ['name' => $group['name'], 'value' => (string)$group['value']];
		}
		$styleOptions = [
			['name' => 'Information (blue)', 'value' => 'info'],
			['name' => 'Good news (green)', 'value' => 'success'],
			['name' => 'Warning (orange)', 'value' => 'warning'],
			['name' => 'Important (red)', 'value' => 'danger'],
			['name' => 'Dark', 'value' => 'dark'],
			['name' => 'Accent colour (Appearance > Colors & Themes)', 'value' => 'accent'],
		];
		$timeZone = $this->siteTimeZone()->getName();
		return [
			'Announcement' => [
				$this->settingsOption('switch', 'announcementEnabled', ['label' => 'Show the announcement', 'help' => 'A bar under the top bar with your message. When it is off, or outside the times below, nothing extra is sent to the browser.']),
				$this->settingsOption('switch', 'announcementDismissible', ['label' => 'Visitors can close it', 'help' => 'A closed announcement stays closed in that browser until you change its text.']),
				$this->siteOption('textbox', 'announcementText', ['label' => 'Message', 'override' => 12, 'placeholder' => 'For example: Maintenance tonight from 22:00. More in [the news](https://example.com/news).', 'help' => 'Plain text. **bold** makes text bold, [text](https://address) makes a link, and addresses that start with https:// become links by themselves.']),
				$this->settingsOption('select', 'announcementStyle', ['label' => 'Colour', 'options' => $styleOptions]),
				$this->settingsOption('select2', 'announcementGroups', ['label' => 'Shown to', 'class' => 'select2-multiple', 'id' => 'announcementGroups-select', 'options' => $groupOptions, 'help' => 'Leave empty to show it to everyone, guests included.']),
				$this->settingsOption('datetime', 'announcementStart', ['label' => 'Show from', 'help' => 'Optional. Time zone: ' . $timeZone . ' (the TZ setting of the container).']),
				$this->settingsOption('datetime', 'announcementEnd', ['label' => 'Show until', 'help' => 'Optional. Time zone: ' . $timeZone . ' (the TZ setting of the container).']),
			],
			'SEO & Sharing' => [
				$this->siteOption('input', 'seoSiteUrl', ['label' => 'Public address of the site', 'placeholder' => 'https://example.com/', 'help' => 'The address visitors use. Needed for the canonical link, link previews (og:url) and the sitemap. Leave empty if Londerland is not a public website.']),
				$this->settingsOption('select', 'seoRobots', ['label' => 'Search engines', 'options' => [['name' => 'May show the site in search results', 'value' => 'index'], ['name' => 'Keep the site out of search results', 'value' => 'noindex']], 'help' => 'Sets the robots meta tag, the X-Robots-Tag header and the generated robots.txt.']),
				$this->siteOption('input', 'seoAuthor', ['label' => 'Author / owner', 'placeholder' => 'Londerland']),
				$this->siteOption('input', 'seoImage', ['label' => 'Link preview image', 'placeholder' => 'https://example.com/preview.png', 'help' => 'Shown when the site is shared in chat apps and social media. 1200 x 630 pixels works everywhere. A relative address (data/userTabs/...) needs the public address above.']),
				$this->settingsOption('select', 'seoTwitterCard', ['label' => 'Link preview size', 'options' => [['name' => 'Large image', 'value' => 'summary_large_image'], ['name' => 'Small image', 'value' => 'summary']]]),
				$this->siteOption('input', 'seoTwitterSite', ['label' => 'X / Twitter account', 'placeholder' => '@account']),
				$this->siteOption('input', 'seoGoogleVerification', ['label' => 'Google Search Console code', 'help' => 'Only the code from the content="..." part of the meta tag Google gives you.']),
				$this->siteOption('input', 'seoBingVerification', ['label' => 'Bing Webmaster Tools code', 'help' => 'Only the code from the content="..." part of the meta tag Bing gives you.']),
				$this->settingsOption('switch', 'seoStructuredData', ['label' => 'Structured data (JSON-LD)', 'help' => 'Tells search engines the name and address of the site.']),
				$this->settingsOption('switch', 'seoSitemap', ['label' => 'sitemap.xml', 'help' => 'Served at /sitemap.xml and listed in robots.txt. Needs the public address above.']),
				$this->siteOption('textbox', 'seoRobotsTxt', ['label' => 'Own robots.txt', 'override' => 12, 'attr' => 'rows="6" spellcheck="false"', 'placeholder' => "User-agent: *\nDisallow: /api/", 'help' => 'Leave empty for a generated one that follows the Search engines setting. Served at /robots.txt.']),
			],
			'Install as App' => [
				$this->settingsOption('switch', 'pwaEnabled', ['label' => 'Can be installed as an app', 'help' => 'Browsers offer to install Londerland (Install app in the user menu, the install icon in the address bar, or Add to Home Screen on phones). It then opens in its own window without the browser bars. Browsers only allow this over HTTPS (or on localhost).']),
				$this->settingsOption('switch', 'pwaOffline', ['label' => 'Offline page', 'help' => 'Shows a page with a retry button instead of the browser error when the server cannot be reached. Nothing else is stored: pages and data always come from the server.']),
				$this->siteOption('input', 'pwaName', ['label' => 'App name', 'placeholder' => htmlspecialchars((string)$this->config['title'], ENT_QUOTES), 'help' => 'Empty = the Londerland Title.']),
				$this->siteOption('input', 'pwaShortName', ['label' => 'Short name', 'placeholder' => htmlspecialchars((string)$this->config['title'], ENT_QUOTES), 'help' => 'Shown under the icon on phones; 12 characters or less fits.']),
				$this->settingsOption('select', 'pwaDisplay', ['label' => 'Window', 'options' => [['name' => 'Own window (standalone)', 'value' => 'standalone'], ['name' => 'Full screen', 'value' => 'fullscreen'], ['name' => 'Own window with back and reload buttons', 'value' => 'minimal-ui']]]),
				$this->settingsOption('select', 'pwaOrientation', ['label' => 'Orientation on phones', 'options' => [['name' => 'Any', 'value' => 'any'], ['name' => 'Portrait', 'value' => 'portrait'], ['name' => 'Landscape', 'value' => 'landscape']]]),
				$this->settingsOption('color', 'pwaThemeColor', ['label' => 'Title bar colour', 'help' => 'Empty = the Nav Bar Color, else dark grey.']),
				$this->settingsOption('color', 'pwaBackgroundColor', ['label' => 'Start screen colour']),
				$this->siteOption('input', 'pwaIcon192', ['label' => 'Icon 192 x 192 (PNG)', 'placeholder' => 'plugins/images/favicon/android-chrome-192x192.png', 'help' => 'Empty = the Londerland icon. Upload your own with the Image Manager (data/userTabs/...).']),
				$this->siteOption('input', 'pwaIcon512', ['label' => 'Icon 512 x 512 (PNG)', 'placeholder' => 'plugins/images/favicon/android-chrome-512x512.png']),
			],
		];
	}

	// A settings item whose value is escaped: the settings form writes values into value="" and <textarea> as they are
	private function siteOption($type, $name, $extras = [])
	{
		$option = $this->settingsOption($type, $name, $extras);
		$option['value'] = htmlspecialchars((string)($this->config[$name] ?? ''), ENT_QUOTES, 'UTF-8');
		return $option;
	}

	/* ===================== announcement ===================== */

	// The container's time zone (TZ), which PHP itself does not use: Londerland works in UTC
	public function siteTimeZone()
	{
		$name = getenv('TZ') ?: 'UTC';
		try {
			return new DateTimeZone($name);
		} catch (Exception $e) {
			return new DateTimeZone('UTC');
		}
	}

	private function siteTime($value)
	{
		$value = trim((string)$value);
		if ($value === '') {
			return null;
		}
		try {
			return (new DateTime(str_replace('T', ' ', $value), $this->siteTimeZone()))->getTimestamp();
		} catch (Exception $e) {
			return null;
		}
	}

	/**
	 * The announcement the current visitor should see, or null.
	 */
	public function currentAnnouncement()
	{
		if (!$this->hasDB() || empty($this->config['announcementEnabled'])) {
			return null;
		}
		$text = trim((string)($this->config['announcementText'] ?? ''));
		if ($text === '') {
			return null;
		}
		$now = time();
		$start = $this->siteTime($this->config['announcementStart'] ?? '');
		$end = $this->siteTime($this->config['announcementEnd'] ?? '');
		if (($start !== null && $now < $start) || ($end !== null && $now >= $end)) {
			return null;
		}
		$groups = array_values(array_filter(array_map('trim', explode(',', (string)($this->config['announcementGroups'] ?? ''))), 'strlen'));
		if ($groups) {
			$groupId = (string)(int)($this->user['groupID'] ?? 999);
			if (!in_array($groupId, $groups, true)) {
				return null;
			}
		}
		$style = (string)($this->config['announcementStyle'] ?? 'info');
		return [
			'id' => substr(hash('sha256', $text . '|' . $style), 0, 12),
			'text' => $text,
			'style' => $style,
			'dismissible' => !empty($this->config['announcementDismissible']),
		];
	}

	/**
	 * Escaped text with **bold**, [text](https://...) links, bare https:// links and line breaks.
	 */
	public function announcementMarkup($text)
	{
		$html = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
		$links = [];
		$keep = function ($anchor) use (&$links) {
			$links[] = $anchor;
			return "\x01" . (count($links) - 1) . "\x02";
		};
		$html = preg_replace_callback('#\[([^\]\n]+)\]\((https?://[^\s)]+|/[^\s)]*)\)#', function ($match) use ($keep) {
			return $keep('<a href="' . $match[2] . '" target="_blank" rel="noopener noreferrer">' . $match[1] . '</a>');
		}, $html);
		$html = preg_replace_callback('#\bhttps?://[^\s<\x01]+#', function ($match) use ($keep) {
			$url = rtrim($match[0], '.,;:!?');
			$rest = substr($match[0], strlen($url));
			return $keep('<a href="' . $url . '" target="_blank" rel="noopener noreferrer">' . $url . '</a>') . $rest;
		}, $html);
		$html = preg_replace('#\*\*(.+?)\*\*#s', '<strong>$1</strong>', $html);
		$html = preg_replace_callback("#\x01(\\d+)\x02#", function ($match) use ($links) {
			return $links[(int)$match[1]];
		}, $html);
		return nl2br($html, false);
	}

	/**
	 * The bar with its styles and a few lines of script, or '' when there is nothing to show. The script runs while the
	 * page is read, before the first paint: a bar this browser closed earlier is removed before it is ever drawn.
	 */
	public function announcementHTML()
	{
		$announcement = $this->currentAnnouncement();
		if (!$announcement) {
			return '';
		}
		if ($announcement['style'] === 'accent') {
			$background = $this->config['accentColor'] ?: '#2cabe3';
			$color = $this->config['accentTextColor'] ?: '#ffffff';
		} else {
			[$background, $color] = self::ANNOUNCEMENT_STYLES[$announcement['style']] ?? self::ANNOUNCEMENT_STYLES['info'];
		}
		$close = $announcement['dismissible'] ? '<button type="button" class="site-announcement-close" aria-label="Close" title="Close">&times;</button>' : '';
		return '
<style>
.site-announcement{position:fixed;top:40px;left:0;right:0;z-index:999;display:flex;align-items:center;gap:12px;padding:8px 16px 8px 76px;background:' . htmlspecialchars($background, ENT_QUOTES) . ';color:' . htmlspecialchars($color, ENT_QUOTES) . ';font-size:14px;line-height:1.4;box-shadow:0 2px 6px rgba(0,0,0,.25)}
.site-announcement a{color:inherit;text-decoration:underline}
.site-announcement-text{flex:1;min-width:0;overflow-wrap:anywhere}
.site-announcement-close{flex:none;background:none;border:0;color:inherit;font-size:22px;line-height:1;padding:0 4px;cursor:pointer;opacity:.8}
.site-announcement-close:hover{opacity:1}
@media (max-width:767px){.site-announcement{padding-left:16px}}
html.has-announcement #page-wrapper{margin-top:var(--announcement-h,0px)!important;height:calc(100% - 40px - var(--announcement-h,0px))!important}
html.has-announcement .split-pane{top:calc(40px + var(--announcement-h,0px))}
</style>
<div class="site-announcement" id="site-announcement" role="status" data-id="' . $announcement['id'] . '"><div class="site-announcement-text">' . $this->announcementMarkup($announcement['text']) . '</div>' . $close . '</div>
<script>
(function () {
	var bar = document.getElementById("site-announcement"), root = document.documentElement, key = "londerlandAnnouncementClosed";
	try {
		if (bar.querySelector(".site-announcement-close") && localStorage.getItem(key) === bar.getAttribute("data-id")) {
			bar.parentNode.removeChild(bar);
			return;
		}
	} catch (e) {}
	function size() {
		root.style.setProperty("--announcement-h", bar.offsetHeight + "px");
	}
	root.classList.add("has-announcement");
	size();
	window.addEventListener("resize", size);
	var close = bar.querySelector(".site-announcement-close");
	if (close) {
		close.addEventListener("click", function () {
			try {
				localStorage.setItem(key, bar.getAttribute("data-id"));
			} catch (e) {}
			window.removeEventListener("resize", size);
			bar.parentNode.removeChild(bar);
			root.classList.remove("has-announcement");
			root.style.removeProperty("--announcement-h");
			window.dispatchEvent(new Event("resize"));
		});
	}
})();
</script>
';
	}

	/* ===================== SEO ===================== */

	// The public address with a trailing slash, or '' when the admin did not set one (the Host header is not trusted for this)
	public function seoSiteUrl()
	{
		$url = trim((string)($this->config['seoSiteUrl'] ?? ''));
		if ($url === '' || !preg_match('#^https?://[^\s/]+#i', $url)) {
			return '';
		}
		return rtrim($url, '/') . '/';
	}

	private function seoAbsoluteUrl($url)
	{
		$url = trim((string)$url);
		if ($url === '' || preg_match('#^https?://#i', $url)) {
			return $url;
		}
		$base = $this->seoSiteUrl();
		return $base !== '' ? $base . ltrim($url, '/') : '';
	}

	public function seoNoIndex()
	{
		return ($this->config['seoRobots'] ?? 'index') === 'noindex';
	}

	// Response headers for index.php, sent before any of the page is
	public function seoHeaders()
	{
		if ($this->hasDB() && $this->seoNoIndex() && !headers_sent()) {
			header('X-Robots-Tag: noindex, nofollow');
		}
	}

	/**
	 * Tags for the <head> of index.php: author, robots, canonical, Open Graph, X/Twitter, verification codes, JSON-LD.
	 */
	public function seoHead()
	{
		if (!$this->hasDB()) {
			return '';
		}
		$e = function ($value) {
			return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
		};
		$title = strip_tags((string)$this->config['title']);
		$description = strip_tags((string)$this->config['description']);
		$siteUrl = $this->seoSiteUrl();
		$image = $this->seoAbsoluteUrl($this->config['seoImage'] ?? '');
		$author = trim((string)($this->config['seoAuthor'] ?? '')) ?: 'Londerland';
		$tags = [];
		$tags[] = '<meta name="author" content="' . $e($author) . '">';
		$tags[] = '<meta name="robots" content="' . ($this->seoNoIndex() ? 'noindex, nofollow' : 'index, follow') . '">';
		if ($siteUrl !== '') {
			$tags[] = '<link rel="canonical" href="' . $e($siteUrl) . '">';
			$tags[] = '<meta property="og:url" content="' . $e($siteUrl) . '">';
		}
		$tags[] = '<meta property="og:type" content="website">';
		$tags[] = '<meta property="og:site_name" content="' . $e($title) . '">';
		$tags[] = '<meta property="og:title" content="' . $e($title) . '">';
		$tags[] = '<meta property="og:description" content="' . $e($description) . '">';
		$card = ($this->config['seoTwitterCard'] ?? '') === 'summary' ? 'summary' : 'summary_large_image';
		$tags[] = '<meta name="twitter:card" content="' . ($image !== '' ? $card : 'summary') . '">';
		$tags[] = '<meta name="twitter:title" content="' . $e($title) . '">';
		$tags[] = '<meta name="twitter:description" content="' . $e($description) . '">';
		if ($image !== '') {
			$tags[] = '<meta property="og:image" content="' . $e($image) . '">';
			$tags[] = '<meta name="twitter:image" content="' . $e($image) . '">';
		}
		$twitter = trim((string)($this->config['seoTwitterSite'] ?? ''));
		if ($twitter !== '') {
			$tags[] = '<meta name="twitter:site" content="' . $e('@' . ltrim($twitter, '@')) . '">';
		}
		if (trim((string)($this->config['seoGoogleVerification'] ?? '')) !== '') {
			$tags[] = '<meta name="google-site-verification" content="' . $e(trim($this->config['seoGoogleVerification'])) . '">';
		}
		if (trim((string)($this->config['seoBingVerification'] ?? '')) !== '') {
			$tags[] = '<meta name="msvalidate.01" content="' . $e(trim($this->config['seoBingVerification'])) . '">';
		}
		if (!empty($this->config['seoStructuredData'])) {
			$data = ['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => $title, 'description' => $description];
			if ($siteUrl !== '') {
				$data['url'] = $siteUrl;
			}
			if ($image !== '') {
				$data['image'] = $image;
			}
			$tags[] = '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) . '</script>';
		}
		return implode("\n\t", $tags) . "\n";
	}

	public function seoRobotsTxt()
	{
		$custom = trim(str_replace("\r\n", "\n", (string)($this->config['seoRobotsTxt'] ?? '')));
		if ($custom !== '') {
			return $custom . "\n";
		}
		$lines = ['User-agent: *'];
		if ($this->seoNoIndex()) {
			$lines[] = 'Disallow: /';
		} else {
			// The API, the data volume and the plugin folders hold nothing for search results
			$lines[] = 'Disallow: /api/';
			$lines[] = 'Disallow: /data/';
			$lines[] = 'Allow: /';
			if (!empty($this->config['seoSitemap']) && $this->seoSiteUrl() !== '') {
				$lines[] = '';
				$lines[] = 'Sitemap: ' . $this->seoSiteUrl() . 'sitemap.xml';
			}
		}
		return implode("\n", $lines) . "\n";
	}

	/**
	 * Londerland is one page (tabs are #fragments, which search engines ignore), so the sitemap lists that page.
	 */
	public function seoSitemapXml()
	{
		$siteUrl = $this->seoSiteUrl();
		if ($this->seoNoIndex() || empty($this->config['seoSitemap']) || $siteUrl === '') {
			return null;
		}
		return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
			. '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n"
			. "\t<url><loc>" . htmlspecialchars($siteUrl, ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc></url>\n"
			. "</urlset>\n";
	}

	/* ===================== install as app ===================== */

	public function pwaEnabled()
	{
		return $this->hasDB() && !empty($this->config['pwaEnabled']);
	}

	public function pwaThemeColor()
	{
		$color = trim((string)($this->config['pwaThemeColor'] ?? '')) ?: trim((string)($this->config['headerColor'] ?? ''));
		return preg_match('/^#[0-9a-f]{3,8}$/i', $color) ? $color : '#2b2f36';
	}

	/**
	 * <head> tags: the manifest, theme colour, and registering (or, once switched off, removing) the service worker.
	 */
	public function pwaHead()
	{
		// A service worker from earlier (app or offline page switched off since) would keep showing the offline page
		$unregister = '<script>if (navigator.serviceWorker && navigator.serviceWorker.controller) { navigator.serviceWorker.getRegistrations().then(function (r) { r.forEach(function (w) { w.unregister(); }); }); }</script>' . "\n";
		if (!$this->pwaEnabled()) {
			return $unregister;
		}
		$color = htmlspecialchars($this->pwaThemeColor(), ENT_QUOTES);
		// Browsers do not need a service worker to install an app any more; it is only there for the offline page
		$serviceWorker = !empty($this->config['pwaOffline'])
			? 'if ("serviceWorker" in navigator) {
			window.addEventListener("load", function () { navigator.serviceWorker.register("sw.js?v=' . rawurlencode($this->fileHash) . '").catch(function () {}); });
		}'
			: '';
		return '<link rel="manifest" href="api/v2/pwa/manifest" crossorigin="use-credentials">
	<meta name="theme-color" content="' . $color . '">
	<meta name="mobile-web-app-capable" content="yes">
	' . ($serviceWorker === '' ? $unregister : '') . '<script>
		' . $serviceWorker . '
		// Chrome and Edge offer installing through this event; the user menu shows "Install app" while it is available
		window.addEventListener("beforeinstallprompt", function (event) {
			event.preventDefault();
			window.londerlandInstallPrompt = event;
			document.documentElement.classList.add("can-install-app");
		});
		window.addEventListener("appinstalled", function () {
			window.londerlandInstallPrompt = null;
			document.documentElement.classList.remove("can-install-app");
		});
	</script>
';
	}

	/**
	 * The favicon tags without their own manifest link while the app is switched on (the first manifest link wins).
	 */
	public function siteFavIcons()
	{
		$icons = $this->favIcons();
		if ($this->pwaEnabled()) {
			$icons = preg_replace('#<link[^>]+rel=["\']manifest["\'][^>]*>\s*#i', '', $icons);
			$icons = preg_replace('#<meta[^>]+name=["\']theme-color["\'][^>]*>\s*#i', '', $icons);
		}
		return $icons;
	}

	public function pwaManifest()
	{
		$title = trim(strip_tags((string)$this->config['title'])) ?: 'Londerland';
		$name = trim((string)($this->config['pwaName'] ?? '')) ?: $title;
		$shortName = trim((string)($this->config['pwaShortName'] ?? '')) ?: $name;
		$display = in_array($this->config['pwaDisplay'] ?? '', ['standalone', 'fullscreen', 'minimal-ui'], true) ? $this->config['pwaDisplay'] : 'standalone';
		$orientation = in_array($this->config['pwaOrientation'] ?? '', ['any', 'portrait', 'landscape'], true) ? $this->config['pwaOrientation'] : 'any';
		$background = trim((string)($this->config['pwaBackgroundColor'] ?? ''));
		$background = preg_match('/^#[0-9a-f]{3,8}$/i', $background) ? $background : (($this->config['style'] ?? 'dark') === 'light' ? '#ffffff' : '#1f2329');
		// The manifest is served from api/v2/pwa/, so its addresses go two folders up to the site
		$icon = function ($configured, $default) {
			$configured = trim((string)$configured);
			if ($configured === '') {
				return '../../../' . $default;
			}
			return preg_match('#^(https?:)?//#i', $configured) || strpos($configured, '/') === 0 ? $configured : '../../../' . $configured;
		};
		return [
			'id' => '../../../',
			'name' => $name,
			'short_name' => $shortName,
			'description' => strip_tags((string)$this->config['description']),
			'start_url' => '../../../',
			'scope' => '../../../',
			'display' => $display,
			'orientation' => $orientation,
			'theme_color' => $this->pwaThemeColor(),
			'background_color' => $background,
			'icons' => [
				['src' => $icon($this->config['pwaIcon192'] ?? '', 'plugins/images/favicon/android-chrome-192x192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
				['src' => $icon($this->config['pwaIcon512'] ?? '', 'plugins/images/favicon/android-chrome-512x512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
			],
		];
	}
}
