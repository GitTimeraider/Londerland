<?php
// PLUGIN INFORMATION
$GLOBALS['plugins']['Bookmarks'] = array( // Plugin Name
	'name' => 'Bookmarks', // Plugin Name
	'author' => 'Londerland', // Who wrote the plugin
	'category' => 'Utilities', // One to Two Word Description
	'link' => '', // Link to plugin info
	'license' => 'personal,business', // License Type use , for multiple
	'idPrefix' => 'BOOKMARK', // html element id prefix
	'configPrefix' => 'BOOKMARK', // config file prefix for array items without the hyphen
	'dbPrefix' => 'BOOKMARK', // db prefix
	'version' => '2.0.0', // SemVer of plugin
	'image' => 'api/plugins/bookmark/logo.png', // 1:1 non transparent image for plugin
	'settings' => true, // does plugin need a settings modal?
	'bind' => true, // use default bind to make settings page - true or false
	'api' => 'api/v2/plugins/bookmark/settings', // api route for settings page
	'homepage' => false // Is plugin for use on homepage? true or false
);

/**
 * A page of bookmarks (links) in categories, shown on a Londerland tab that points to api/v2/plugins/bookmark/page.
 * Everyone who can open that tab sees the bookmarks of their group; admins add, change and sort them on the page itself.
 * Uses the tables of the first Bookmark plugin (BOOKMARK-categories, BOOKMARK-tabs), so existing bookmarks stay.
 */
class Bookmark extends Londerland
{
	public const PAGE_URL = 'api/v2/plugins/bookmark/page';
	private const MAX_NAME = 100;
	private const MAX_URL = 2000;
	// Links that would run code in the page are refused; everything else (http, https, ssh, smb, rdp, ...) is allowed
	private const BLOCKED_SCHEMES = ['javascript', 'data', 'vbscript'];
	private const DEFAULT_BACKGROUND = '#2f323e';
	private const DEFAULT_TEXT = '#ffffff';

	/* ===================== access & setup ===================== */

	/**
	 * The plugin is enabled and has its tables. Reading needs nothing more: the tab decides who opens the page,
	 * and every bookmark carries its own minimum group.
	 */
	public function _bookmarkReady($request)
	{
		if (!$this->checkRoute($request)) {
			return false;
		}
		if (!$this->config['BOOKMARK-enabled'] || !$this->hasDB()) {
			$this->setAPIResponse('error', 'Bookmarks are not enabled', 404);
			return false;
		}
		$this->_bookmarkEnsureTables();
		return true;
	}

	public function _bookmarkAdminAccess($request)
	{
		return $this->_bookmarkReady($request) && $this->qualifyRequest(1, true);
	}

	public function _bookmarkSettingsAccess($request)
	{
		return $this->checkRoute($request) && $this->qualifyRequest(1, true);
	}

	private function _bookmarkTableExists($table)
	{
		if ($this->config['driver'] == 'sqlite3') {
			$query = ["SELECT `name` FROM `sqlite_master` WHERE `type` = 'table' AND `name` = %s", $table];
		} else {
			$query = ['SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s', (string)$this->config['dbName'], $table];
		}
		return (bool)$this->processQueries([['function' => 'fetchSingle', 'query' => $query]]);
	}

	// Same structure as the first Bookmark plugin
	private function _bookmarkEnsureTables()
	{
		$tables = [
			'BOOKMARK-categories' => 'CREATE TABLE `BOOKMARK-categories` (
				`id`	INTEGER PRIMARY KEY AUTOINCREMENT UNIQUE,
				`order`	INTEGER,
				`category`	TEXT UNIQUE,
				`category_id`	INTEGER,
				`default` INTEGER
			);',
			'BOOKMARK-tabs' => 'CREATE TABLE `BOOKMARK-tabs` (
				`id`	INTEGER PRIMARY KEY AUTOINCREMENT UNIQUE,
				`order`	INTEGER,
				`category_id`	INTEGER,
				`name`	TEXT,
				`url`	TEXT,
				`enabled`	INTEGER,
				`group_id`	INTEGER,
				`image`	TEXT,
				`background_color` TEXT,
				`text_color` TEXT
			);',
		];
		foreach ($tables as $name => $create) {
			if (!$this->_bookmarkTableExists($name)) {
				$this->processQueries([['function' => 'query', 'query' => $create]]);
			}
		}
	}

	/* ===================== reading ===================== */

	private function _bookmarkCategories()
	{
		return $this->processQueries([[
			'function' => 'fetchAll',
			'query' => 'SELECT * FROM `BOOKMARK-categories` ORDER BY `order` ASC, `id` ASC'
		]]) ?: [];
	}

	private function _bookmarkFormatCategory($category)
	{
		return [
			'id' => (int)$category['id'],
			'categoryId' => (int)$category['category_id'],
			'name' => (string)$category['category'],
			'order' => (int)$category['order'],
			'default' => (bool)$category['default'],
		];
	}

	private function _bookmarkFormat($bookmark)
	{
		return [
			'id' => (int)$bookmark['id'],
			'categoryId' => (int)$bookmark['category_id'],
			'name' => (string)$bookmark['name'],
			'url' => (string)$bookmark['url'],
			'image' => (string)($bookmark['image'] ?? ''),
			'background' => $this->_bookmarkColor($bookmark['background_color'] ?? '') ?: self::DEFAULT_BACKGROUND,
			'text' => $this->_bookmarkColor($bookmark['text_color'] ?? '') ?: self::DEFAULT_TEXT,
			'groupId' => (int)$bookmark['group_id'],
			'enabled' => (bool)$bookmark['enabled'],
			'order' => (int)$bookmark['order'],
		];
	}

	/**
	 * Categories and the bookmarks this user may see (switched on, and their group or a higher one).
	 * Admins asking for everything also get switched off bookmarks and the list of groups, for editing.
	 */
	public function _bookmarkList($everything = false)
	{
		$everything = $everything && $this->qualifyRequest(1);
		if ($everything) {
			$query = 'SELECT * FROM `BOOKMARK-tabs` ORDER BY `order` ASC, `id` ASC';
		} else {
			$query = ['SELECT * FROM `BOOKMARK-tabs` WHERE `enabled` = 1 AND `group_id` >= ? ORDER BY `order` ASC, `id` ASC', (int)$this->getUserLevel()];
		}
		$bookmarks = $this->processQueries([['function' => 'fetchAll', 'query' => $query]]) ?: [];
		$data = [
			'categories' => array_map([$this, '_bookmarkFormatCategory'], $this->_bookmarkCategories()),
			'bookmarks' => array_map([$this, '_bookmarkFormat'], $bookmarks),
			'canEdit' => $this->qualifyRequest(1),
		];
		if ($everything) {
			$data['groups'] = array_map(function ($group) {
				return ['id' => (int)$group['value'], 'name' => $group['name']];
			}, $this->groupSelect());
			$data['defaultGroup'] = (int)$this->getDefaultGroupId();
		}
		return $data;
	}

	/* ===================== validation ===================== */

	// #rgb, #rrggbb or #rrggbbaa; anything else is no colour
	private function _bookmarkColor($value)
	{
		$value = trim((string)$value);
		return preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $value) ? strtolower($value) : '';
	}

	private function _bookmarkUrl($value)
	{
		$url = trim((string)$value);
		if ($url === '' || mb_strlen($url) > self::MAX_URL || preg_match('/[\x00-\x1f]/', $url)) {
			return false;
		}
		// browsers ignore spaces and control characters in a scheme, so check it without them
		if (preg_match('/^([a-z][a-z0-9+.\-]*):/i', preg_replace('/\s+/', '', $url), $match) && in_array(strtolower($match[1]), self::BLOCKED_SCHEMES, true)) {
			return false;
		}
		return $url;
	}

	// An icon from Londerland's icon list (prefix::name), an image path or address; nothing that could leave the attribute
	private function _bookmarkImage($value)
	{
		$image = trim((string)$value);
		if ($image === '' || mb_strlen($image) > 500 || preg_match('/["<>\x00-\x1f]/', $image)) {
			return '';
		}
		if (preg_match('/^(materialize|fontawesome|fontawesome-brands|themify|simpleline|weathericon|alphanumeric)::[\w\- ]{1,60}$/', $image)) {
			return $image;
		}
		if (strpos($image, 'url::') === 0) {
			$url = $this->_bookmarkUrl(substr($image, 5));
			return $url ? 'url::' . $url : '';
		}
		return $this->_bookmarkUrl($image) ?: '';
	}

	private function _bookmarkGroupId($value)
	{
		$groups = array_map(function ($group) {
			return (int)$group['value'];
		}, $this->groupSelect());
		$value = (int)$value;
		return in_array($value, $groups, true) ? $value : null;
	}

	private function _bookmarkCategoryById($categoryId)
	{
		return $this->processQueries([[
			'function' => 'fetch',
			'query' => ['SELECT * FROM `BOOKMARK-categories` WHERE `category_id` = ?', (int)$categoryId]
		]]);
	}

	private function _bookmarkDefaultCategoryId()
	{
		$categoryId = $this->processQueries([[
			'function' => 'fetchSingle',
			'query' => 'SELECT `category_id` FROM `BOOKMARK-categories` ORDER BY `default` DESC, `order` ASC LIMIT 1'
		]]);
		return $categoryId !== false && $categoryId !== null ? (int)$categoryId : $this->_bookmarkCreateDefaultCategory();
	}

	private function _bookmarkCreateDefaultCategory()
	{
		$categoryId = $this->_bookmarkNext('BOOKMARK-categories', 'category_id');
		$this->processQueries([['function' => 'query', 'query' => ['INSERT INTO [BOOKMARK-categories]', [
			'order' => 1,
			'category' => 'Bookmarks',
			'category_id' => $categoryId,
			'default' => 1,
		]]]]);
		return $categoryId;
	}

	private function _bookmarkNext($table, $column)
	{
		return (int)$this->processQueries([[
			'function' => 'fetchSingle',
			'query' => ['SELECT MAX(%n) FROM %n', $column, $table]
		]]) + 1;
	}

	private function _bookmarkCategoryNameTaken($name, $exceptId = null)
	{
		foreach ($this->_bookmarkCategories() as $category) {
			if (mb_strtolower($category['category']) === mb_strtolower($name) && (int)$category['id'] !== (int)$exceptId) {
				return true;
			}
		}
		return false;
	}

	/* ===================== bookmarks ===================== */

	/**
	 * Checks and cleans a bookmark sent by the editor; for a change, missing fields keep their stored value.
	 */
	private function _bookmarkClean($data, $current = null)
	{
		$value = function ($key, $fallback) use ($data) {
			return array_key_exists($key, $data) ? $data[$key] : $fallback;
		};
		$name = trim((string)$value('name', $current['name'] ?? ''));
		if ($name === '' || mb_strlen($name) > self::MAX_NAME) {
			$this->setAPIResponse('error', 'Give the bookmark a name of at most ' . self::MAX_NAME . ' characters', 422);
			return false;
		}
		$url = $this->_bookmarkUrl($value('url', $current['url'] ?? ''));
		if ($url === false) {
			$this->setAPIResponse('error', 'Enter a valid address (javascript: and data: links are not allowed)', 422);
			return false;
		}
		$categoryId = (int)$value('categoryId', $current['category_id'] ?? 0);
		if (!$this->_bookmarkCategoryById($categoryId)) {
			$categoryId = $this->_bookmarkDefaultCategoryId();
		}
		$groupId = $this->_bookmarkGroupId($value('groupId', $current['group_id'] ?? $this->getDefaultGroupId()));
		if ($groupId === null) {
			$this->setAPIResponse('error', 'Choose an existing group', 422);
			return false;
		}
		$image = $this->_bookmarkImage($value('image', $current['image'] ?? ''));
		return [
			'name' => $name,
			'url' => $url,
			'category_id' => $categoryId,
			'group_id' => $groupId,
			'image' => $image,
			'background_color' => $this->_bookmarkColor($value('background', $current['background_color'] ?? '')) ?: self::DEFAULT_BACKGROUND,
			'text_color' => $this->_bookmarkColor($value('text', $current['text_color'] ?? '')) ?: self::DEFAULT_TEXT,
			'enabled' => filter_var($value('enabled', $current['enabled'] ?? true), FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
		];
	}

	private function _bookmarkFind($id)
	{
		$bookmark = $this->processQueries([[
			'function' => 'fetch',
			'query' => ['SELECT * FROM `BOOKMARK-tabs` WHERE `id` = ?', (int)$id]
		]]);
		if (!$bookmark) {
			$this->setAPIResponse('error', 'Bookmark not found', 404);
		}
		return $bookmark;
	}

	public function _bookmarkAdd($data)
	{
		$row = $this->_bookmarkClean($data);
		if (!$row) {
			return false;
		}
		$row['order'] = $this->_bookmarkNext('BOOKMARK-tabs', 'order');
		$this->processQueries([['function' => 'query', 'query' => ['INSERT INTO [BOOKMARK-tabs]', $row]]]);
		$this->setAPIResponse('success', 'Bookmark added', 200);
		return true;
	}

	public function _bookmarkUpdate($id, $data)
	{
		$current = $this->_bookmarkFind($id);
		if (!$current) {
			return false;
		}
		$row = $this->_bookmarkClean($data, $current);
		if (!$row) {
			return false;
		}
		$this->processQueries([['function' => 'query', 'query' => ['UPDATE [BOOKMARK-tabs] SET', $row, 'WHERE `id` = ?', (int)$id]]]);
		$this->setAPIResponse('success', 'Bookmark saved', 200);
		return true;
	}

	public function _bookmarkDelete($id)
	{
		if (!$this->_bookmarkFind($id)) {
			return false;
		}
		$this->processQueries([['function' => 'query', 'query' => ['DELETE FROM `BOOKMARK-tabs` WHERE `id` = ?', (int)$id]]]);
		$this->setAPIResponse('success', 'Bookmark deleted', 200);
		return true;
	}

	/**
	 * Order after dragging: [{id, categoryId}, ...] in the new order, for all bookmarks that moved or not.
	 */
	public function _bookmarkSort($data)
	{
		$items = isset($data['items']) && is_array($data['items']) ? $data['items'] : [];
		$queries = [];
		$order = 1;
		foreach ($items as $item) {
			$id = (int)($item['id'] ?? 0);
			$categoryId = (int)($item['categoryId'] ?? 0);
			if ($id <= 0 || !$this->_bookmarkCategoryById($categoryId)) {
				continue;
			}
			$queries[] = ['function' => 'query', 'query' => ['UPDATE [BOOKMARK-tabs] SET', ['order' => $order++, 'category_id' => $categoryId], 'WHERE `id` = ?', $id]];
		}
		if ($queries) {
			$this->processQueries($queries);
		}
		$this->setAPIResponse('success', 'Order saved', 200);
		return true;
	}

	/* ===================== categories ===================== */

	private function _bookmarkFindCategory($id)
	{
		$category = $this->processQueries([[
			'function' => 'fetch',
			'query' => ['SELECT * FROM `BOOKMARK-categories` WHERE `id` = ?', (int)$id]
		]]);
		if (!$category) {
			$this->setAPIResponse('error', 'Category not found', 404);
		}
		return $category;
	}

	private function _bookmarkCategoryName($data, $exceptId = null)
	{
		$name = trim((string)($data['name'] ?? ''));
		if ($name === '' || mb_strlen($name) > self::MAX_NAME) {
			$this->setAPIResponse('error', 'Give the category a name of at most ' . self::MAX_NAME . ' characters', 422);
			return false;
		}
		if ($this->_bookmarkCategoryNameTaken($name, $exceptId)) {
			$this->setAPIResponse('error', 'There is already a category with that name', 409);
			return false;
		}
		return $name;
	}

	public function _bookmarkAddCategory($data)
	{
		$name = $this->_bookmarkCategoryName($data);
		if ($name === false) {
			return false;
		}
		$first = !$this->_bookmarkCategories();
		$this->processQueries([['function' => 'query', 'query' => ['INSERT INTO [BOOKMARK-categories]', [
			'order' => $this->_bookmarkNext('BOOKMARK-categories', 'order'),
			'category' => $name,
			'category_id' => $this->_bookmarkNext('BOOKMARK-categories', 'category_id'),
			'default' => $first ? 1 : 0,
		]]]]);
		$this->setAPIResponse('success', 'Category added', 200);
		return true;
	}

	public function _bookmarkUpdateCategory($id, $data)
	{
		$category = $this->_bookmarkFindCategory($id);
		if (!$category) {
			return false;
		}
		$name = array_key_exists('name', $data) ? $this->_bookmarkCategoryName($data, $id) : $category['category'];
		if ($name === false) {
			return false;
		}
		$queries = [];
		// one default category: new bookmarks without a category, and bookmarks of a deleted category, go there
		if (!empty($data['default']) && !$category['default']) {
			$queries[] = ['function' => 'query', 'query' => ['UPDATE [BOOKMARK-categories] SET', ['default' => 0]]];
		}
		$queries[] = ['function' => 'query', 'query' => ['UPDATE [BOOKMARK-categories] SET', [
			'category' => $name,
			'default' => (!empty($data['default']) || $category['default']) ? 1 : 0,
		], 'WHERE `id` = ?', (int)$id]];
		$this->processQueries($queries);
		$this->setAPIResponse('success', 'Category saved', 200);
		return true;
	}

	public function _bookmarkDeleteCategory($id)
	{
		$category = $this->_bookmarkFindCategory($id);
		if (!$category) {
			return false;
		}
		if ($category['default']) {
			$this->setAPIResponse('error', 'The default category cannot be deleted. Make another category the default first.', 409);
			return false;
		}
		$defaultCategoryId = $this->_bookmarkDefaultCategoryId();
		$this->processQueries([
			['function' => 'query', 'query' => ['UPDATE [BOOKMARK-tabs] SET', ['category_id' => $defaultCategoryId], 'WHERE `category_id` = ?', (int)$category['category_id']]],
			['function' => 'query', 'query' => ['DELETE FROM `BOOKMARK-categories` WHERE `id` = ?', (int)$id]],
		]);
		$this->setAPIResponse('success', 'Category deleted; its bookmarks moved to the default category', 200);
		return true;
	}

	// Category ids in the new order
	public function _bookmarkSortCategories($data)
	{
		$ids = isset($data['ids']) && is_array($data['ids']) ? $data['ids'] : [];
		$queries = [];
		foreach (array_values($ids) as $index => $id) {
			$queries[] = ['function' => 'query', 'query' => ['UPDATE [BOOKMARK-categories] SET', ['order' => $index + 1], 'WHERE `id` = ?', (int)$id]];
		}
		if ($queries) {
			$this->processQueries($queries);
		}
		$this->setAPIResponse('success', 'Order saved', 200);
		return true;
	}

	/* ===================== tab & settings ===================== */

	private function _bookmarkTab()
	{
		return $this->processQueries([[
			'function' => 'fetch',
			'query' => ['SELECT * FROM `tabs` WHERE `url` = ?', self::PAGE_URL]
		]]);
	}

	/**
	 * Creates the Bookmarks tab (a Londerland page) and a first category when they are missing.
	 */
	public function _bookmarkSetup()
	{
		$this->_bookmarkEnsureTables();
		$done = [];
		if (!$this->_bookmarkTab()) {
			$this->processQueries([['function' => 'query', 'query' => ['INSERT INTO [tabs]', [
				'order' => $this->getNextTabOrder() + 1,
				'category_id' => $this->getDefaultCategoryId(),
				'name' => 'Bookmarks',
				'url' => self::PAGE_URL,
				'default' => 0,
				'enabled' => 1,
				'group_id' => $this->getDefaultGroupId(),
				'image' => 'fontawesome::bookmark',
				'type' => 0,
			]]]]);
			$done[] = 'created the Bookmarks tab';
		}
		if (!$this->_bookmarkCategories()) {
			$this->_bookmarkCreateDefaultCategory();
			$done[] = 'created a first category';
		}
		$message = $done ? 'Setup ' . implode(' and ', $done) . '. Reload Londerland to see the tab.' : 'Everything was already set up';
		$this->setAPIResponse('success', $message, 200);
		return true;
	}

	/* ===================== import & export ===================== */

	/**
	 * The bookmarks as a browser bookmarks file (the Netscape format every browser imports): one folder per category.
	 * Admins get every bookmark, everyone else the ones of their group.
	 */
	public function _bookmarkExportHtml()
	{
		$list = $this->_bookmarkList($this->qualifyRequest(1));
		$e = function ($value) {
			return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
		};
		$now = time();
		$html = "<!DOCTYPE NETSCAPE-Bookmark-file-1>\n<!-- This is an automatically generated file.\n     It will be read and overwritten.\n     DO NOT EDIT! -->\n"
			. "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=UTF-8\">\n<TITLE>Bookmarks</TITLE>\n<H1>" . $e($this->config['title'] ?? 'Bookmarks') . "</H1>\n<DL><p>\n";
		foreach ($list['categories'] as $category) {
			$bookmarks = array_filter($list['bookmarks'], function ($bookmark) use ($category) {
				return $bookmark['categoryId'] === $category['categoryId'];
			});
			if (!$bookmarks) {
				continue;
			}
			$html .= "\t<DT><H3 ADD_DATE=\"" . $now . "\">" . $e($category['name']) . "</H3>\n\t<DL><p>\n";
			foreach ($bookmarks as $bookmark) {
				$html .= "\t\t<DT><A HREF=\"" . $e($bookmark['url']) . "\" ADD_DATE=\"" . $now . "\">" . $e($bookmark['name']) . "</A>\n";
			}
			$html .= "\t</DL><p>\n";
		}
		return $html . "</DL><p>\n";
	}

	/**
	 * Bookmarks from a browser bookmarks file. Each folder becomes a category (an existing category with the same name
	 * is used), or everything goes into one category; links already in Londerland are skipped. Icons in the file are
	 * pictures inside the file (data: addresses), which bookmarks do not take, so imported bookmarks show their letter.
	 */
	public function _bookmarkImportHtml($data)
	{
		$html = (string)($data['html'] ?? '');
		if ($html === '' || strlen($html) > 10 * 1024 * 1024) {
			$this->setAPIResponse('error', 'Choose a bookmarks file (.html) of at most 10 MB', 422);
			return false;
		}
		$groupId = $this->_bookmarkGroupId($data['groupId'] ?? $this->getDefaultGroupId());
		if ($groupId === null) {
			$this->setAPIResponse('error', 'Choose an existing group', 422);
			return false;
		}
		$oneCategory = trim((string)($data['category'] ?? ''));
		if (mb_strlen($oneCategory) > self::MAX_NAME) {
			$this->setAPIResponse('error', 'Give the category a name of at most ' . self::MAX_NAME . ' characters', 422);
			return false;
		}
		$skipDuplicates = !array_key_exists('skipDuplicates', $data) || filter_var($data['skipDuplicates'], FILTER_VALIDATE_BOOLEAN);
		$links = $this->_bookmarkParseHtml($html);
		if (!$links) {
			$this->setAPIResponse('error', 'No bookmarks found in this file', 422);
			return false;
		}
		$existing = [];
		foreach ($this->processQueries([['function' => 'fetchAll', 'query' => 'SELECT `url` FROM `BOOKMARK-tabs`']]) ?: [] as $row) {
			$existing[mb_strtolower(trim((string)$row['url']))] = true;
		}
		$categories = [];
		foreach ($this->_bookmarkCategories() as $category) {
			$categories[mb_strtolower($category['category'])] = (int)$category['category_id'];
		}
		$categoryFor = function ($name) use (&$categories) {
			$name = mb_substr(trim($name), 0, self::MAX_NAME) ?: 'Imported';
			$key = mb_strtolower($name);
			if (!isset($categories[$key])) {
				$categoryId = $this->_bookmarkNext('BOOKMARK-categories', 'category_id');
				$this->processQueries([['function' => 'query', 'query' => ['INSERT INTO [BOOKMARK-categories]', [
					'order' => $this->_bookmarkNext('BOOKMARK-categories', 'order'),
					'category' => $name,
					'category_id' => $categoryId,
					'default' => $categories ? 0 : 1,
				]]]]);
				$categories[$key] = $categoryId;
			}
			return $categories[$key];
		};
		$order = $this->_bookmarkNext('BOOKMARK-tabs', 'order');
		$added = 0;
		$skipped = 0;
		$queries = [];
		foreach ($links as $link) {
			$url = $this->_bookmarkUrl($link['url']);
			$key = $url === false ? '' : mb_strtolower($url);
			if ($url === false || ($skipDuplicates && isset($existing[$key]))) {
				$skipped++;
				continue;
			}
			$existing[$key] = true;
			$name = mb_substr(trim($link['name']) ?: (parse_url($url, PHP_URL_HOST) ?: $url), 0, self::MAX_NAME);
			$queries[] = ['function' => 'query', 'query' => ['INSERT INTO [BOOKMARK-tabs]', [
				'order' => $order++,
				'category_id' => $categoryFor($oneCategory !== '' ? $oneCategory : $link['folder']),
				'name' => $name,
				'url' => $url,
				'enabled' => 1,
				'group_id' => $groupId,
				'image' => '',
				'background_color' => self::DEFAULT_BACKGROUND,
				'text_color' => self::DEFAULT_TEXT,
			]]];
			$added++;
		}
		// In batches, so a big file does not become one huge request to the database
		foreach (array_chunk($queries, 200) as $batch) {
			$this->processQueries($batch);
		}
		$this->audit('plugin.import', 'Bookmarks', ['added' => $added, 'skipped' => $skipped]);
		$this->setAPIResponse('success', 'Imported ' . $added . ' bookmarks' . ($skipped ? ', skipped ' . $skipped . ' (already there or not allowed)' : ''), 200, ['added' => $added, 'skipped' => $skipped]);
		return true;
	}

	/**
	 * [['name', 'url', 'folder'], ...] from a bookmarks file. The folder is the innermost folder (H3) the link is in;
	 * links that are only in a browser's own top folder (Bookmarks bar, Other bookmarks, ...) get folder ''.
	 */
	private function _bookmarkParseHtml($html)
	{
		$document = new DOMDocument();
		$previous = libxml_use_internal_errors(true);
		// The file is UTF-8 (its META says so); without this line libxml would read it as Latin-1
		$document->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NONET | LIBXML_COMPACT);
		libxml_clear_errors();
		libxml_use_internal_errors($previous);
		$links = [];
		foreach ($document->getElementsByTagName('a') as $anchor) {
			$url = trim((string)$anchor->getAttribute('href'));
			if ($url === '') {
				continue;
			}
			$links[] = ['name' => trim($anchor->textContent), 'url' => $url, 'folder' => $this->_bookmarkFolderOf($anchor)];
			if (count($links) >= 5000) {
				break;
			}
		}
		return $links;
	}

	private function _bookmarkFolderOf(DOMNode $node)
	{
		for ($parent = $node->parentNode; $parent; $parent = $parent->parentNode) {
			if (strtolower($parent->nodeName) !== 'dl') {
				continue;
			}
			// A folder is an H3 followed by its list (DL). The files leave <DT> open, so libxml nests every following
			// item inside the one before it: the folder's H3 is then the last H3 in the element just before the list.
			for ($sibling = $parent->previousSibling; $sibling; $sibling = $sibling->previousSibling) {
				if (!($sibling instanceof DOMElement) || strtolower($sibling->nodeName) === 'p') {
					continue;
				}
				if (strtolower($sibling->nodeName) === 'h3') {
					return $this->_bookmarkFolderName($sibling);
				}
				$headings = $sibling->getElementsByTagName('h3');
				if ($headings->length) {
					return $this->_bookmarkFolderName($headings->item($headings->length - 1));
				}
				break;
			}
		}
		return '';
	}

	private function _bookmarkFolderName(DOMElement $heading)
	{
		// Chrome, Edge, Firefox and Safari mark their own top folders; their links go to the "Imported" category
		if ($heading->hasAttribute('personal_toolbar_folder') || $heading->hasAttribute('unfiled_bookmarks_folder')) {
			return '';
		}
		return trim($heading->textContent);
	}

	public function _bookmarkGetSettings()
	{
		$tab = $this->hasDB() ? $this->_bookmarkTab() : false;
		$status = $tab
			? '<li><i class="fa fa-check text-success"></i> <span lang="en">The Bookmarks tab exists</span> (' . htmlspecialchars((string)$tab['name'], ENT_QUOTES) . ').</li>'
			: '<li><i class="fa fa-exclamation-triangle text-warning"></i> <span lang="en">There is no Bookmarks tab yet: click Set up below.</span></li>';
		return array(
			'custom' => '
				<div class="row">
					<div class="col-xl-12">
						<div class="card card-info">
							<div class="card-header"><span lang="en">How it works</span></div>
							<div class="card-wrapper collapse show" aria-expanded="true">
								<div class="card-body">
									<ul class="list-icons">
										<li><i class="fa fa-chevron-right text-info"></i> <span lang="en">Bookmarks are shown on a Londerland tab that points to api/v2/plugins/bookmark/page. Set up creates it; change its name, icon and groups in the Tab Editor like any other tab.</span></li>
										<li><i class="fa fa-chevron-right text-info"></i> <span lang="en">Admins add, change, delete and sort bookmarks and categories on the Bookmarks page itself, with the Edit button at the top.</span></li>
										<li><i class="fa fa-chevron-right text-info"></i> <span lang="en">Every bookmark has a group: users of that group and higher groups see it.</span></li>
										' . $status . '
									</ul>
								</div>
							</div>
						</div>
					</div>
				</div>',
			'Options' => array(
				array(
					'type' => 'switch',
					'name' => 'BOOKMARK-newWindow-include',
					'label' => 'Open bookmarks in a new browser tab',
					'help' => 'Off: a bookmark opens in the same window, which leaves Londerland.',
					'value' => $this->config['BOOKMARK-newWindow-include']
				),
				array(
					'type' => 'switch',
					'name' => 'BOOKMARK-search-include',
					'label' => 'Search box',
					'help' => 'Filters the bookmarks while typing; Enter opens the first one. Press / to jump to it.',
					'value' => $this->config['BOOKMARK-search-include']
				),
				array(
					'type' => 'select',
					'name' => 'BOOKMARK-layout-include',
					'label' => 'Layout',
					'value' => $this->config['BOOKMARK-layout-include'],
					'options' => array(
						['name' => 'Tiles (icon and name)', 'value' => 'tiles'],
						['name' => 'List (with the address)', 'value' => 'list'],
						['name' => 'Compact (small tiles)', 'value' => 'compact'],
					)
				),
				array(
					'type' => 'button',
					'label' => 'Set up',
					'class' => ' bookmarkSetup',
					'icon' => 'fa fa-magic',
					'text' => 'Create the tab and a first category'
				),
			)
		);
	}
}
