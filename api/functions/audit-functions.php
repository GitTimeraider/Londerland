<?php

/**
 * Audit log: who did what and when (sign-ins, and changes to settings, tabs, categories, users, groups, plugins and
 * backups). Kept in the database table audit_log, created on first use, and shown under Settings > System Settings >
 * Audit Log. Setting values are never stored, only their names: values can hold passwords and API keys.
 */
trait AuditFunctions
{
	// Raise when the table changes; stored as auditLogDbVersion once the table exists
	private static $auditDbVersion = '1';
	private static $auditCategories = ['auth', 'settings', 'tab', 'category', 'user', 'group', 'plugin', 'backup', 'image', 'audit', 'other'];

	private function auditTableReady()
	{
		if (($this->config['auditLogDbVersion'] ?? '') === self::$auditDbVersion) {
			return true;
		}
		if ($this->config['driver'] == 'sqlite3') {
			$query = ["SELECT `name` FROM `sqlite_master` WHERE `type` = 'table' AND `name` = %s", 'audit_log'];
		} else {
			$query = ['SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s', (string)$this->config['dbName'], 'audit_log'];
		}
		if (!$this->processQueries([['function' => 'fetchSingle', 'query' => $query]])) {
			$created = $this->processQueries([[
				'function' => 'query',
				'query' => 'CREATE TABLE `audit_log` (
					`id`	INTEGER PRIMARY KEY AUTOINCREMENT UNIQUE,
					`created`	DATETIME,
					`user_id`	INTEGER,
					`username`	TEXT,
					`ip`	TEXT,
					`category`	TEXT,
					`action`	TEXT,
					`target`	TEXT,
					`details`	TEXT
				);'
			]]);
			if ($created === false) {
				return false;
			}
		}
		$this->updateConfig(['auditLogDbVersion' => self::$auditDbVersion]);
		$this->config['auditLogDbVersion'] = self::$auditDbVersion;
		return true;
	}

	/**
	 * Writes one entry. $action is "category.verb" (tab.update, auth.login, ...); $target is what it was done to
	 * (a tab name, a username); $details is a small array shown with the entry. $actor ['id' => , 'username' => ]
	 * is for sign-ins, where $this->user is not the new user yet. $evenIfOff writes it while the log is switched off
	 * (switching it off is the last entry). Never fails the request that called it.
	 */
	public function audit($action, $target = '', $details = [], $actor = null, $evenIfOff = false)
	{
		try {
			if (!$this->hasDB() || (empty($this->config['auditLogEnabled']) && !$evenIfOff) || !$this->db || !$this->auditTableReady()) {
				return false;
			}
			if ($actor === null) {
				$actor = $this->auditActor();
			}
			$category = explode('.', (string)$action, 2)[0];
			$details = array_filter((array)$details, function ($value) {
				return $value !== null && $value !== [] && $value !== '';
			});
			$this->processQueries([[
				'function' => 'query',
				'query' => ['INSERT INTO [audit_log]', [
					'created' => gmdate('Y-m-d H:i:s'),
					'user_id' => isset($actor['id']) ? (int)$actor['id'] : null,
					'username' => mb_substr((string)($actor['username'] ?? ''), 0, 100),
					'ip' => mb_substr((string)$this->userIP(), 0, 100),
					'category' => in_array($category, self::$auditCategories, true) ? $category : 'other',
					'action' => mb_substr((string)$action, 0, 60),
					'target' => mb_substr((string)$target, 0, 200),
					'details' => $details ? mb_substr(json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR), 0, 4000) : null,
				]],
			]]);
			// Old entries are removed now and then, and every time the log is opened
			if (mt_rand(1, 100) === 1) {
				$this->auditPurge();
			}
		} catch (Throwable $e) {
			return false;
		}
		return true;
	}

	private function auditActor()
	{
		// Scheduled jobs (cron.php) run without a user
		if (PHP_SAPI === 'cli') {
			return ['id' => null, 'username' => 'Scheduled task'];
		}
		if (!empty($this->user['loggedin']) && !empty($this->user['userID'])) {
			return ['id' => $this->user['userID'], 'username' => $this->user['username']];
		}
		// The API key (Settings > System Settings > Main) acts as an admin without an account
		$token = $this->getallheadersi()['token'] ?? ($_GET['apikey'] ?? '');
		if ($token !== '' && $token === ($this->config['londerlandAPI'] ?? null)) {
			return ['id' => null, 'username' => 'API key'];
		}
		return ['id' => null, 'username' => $this->user['username'] ?? 'Guest'];
	}

	private function auditPurge()
	{
		$days = (int)($this->config['auditLogDays'] ?? 365);
		if ($days <= 0) {
			return;
		}
		$this->processQueries([[
			'function' => 'query',
			'query' => ['DELETE FROM `audit_log` WHERE `created` < ?', gmdate('Y-m-d H:i:s', time() - $days * 86400)],
		]]);
	}

	/**
	 * Names of the settings that really changed. Plugin settings keep their plugin prefix (AICHAT-baseUrl).
	 */
	public function auditChangedKeys($old, $new)
	{
		$normal = function ($value) {
			if (is_bool($value)) {
				return $value ? '1' : '0';
			}
			return is_scalar($value) || $value === null ? (string)$value : json_encode($value);
		};
		$changed = [];
		foreach ((array)$new as $key => $value) {
			// Values from the database and from the browser differ in type (1 and "1"), so they are compared as text
			if (!array_key_exists($key, (array)$old) || $normal($old[$key]) !== $normal($value)) {
				$changed[] = (string)$key;
			}
		}
		return $changed;
	}

	// The plugin's name for its config prefix (ACHIEVEMENTS -> Achievements)
	public function auditPluginName($prefix)
	{
		foreach ((array)($GLOBALS['plugins'] ?? []) as $name => $plugin) {
			if (($plugin['configPrefix'] ?? '') === $prefix) {
				return (string)($plugin['name'] ?? $name);
			}
		}
		return $prefix;
	}

	/* ===================== viewer ===================== */

	private function auditFilters($params)
	{
		$where = [];
		$args = [];
		$category = (string)($params['category'] ?? '');
		if ($category !== '' && in_array($category, self::$auditCategories, true)) {
			$where[] = '`category` = ?';
			$args[] = $category;
		}
		$search = trim((string)($params['search'] ?? ''));
		if ($search !== '') {
			$like = '%' . mb_substr($search, 0, 100) . '%';
			$where[] = '(`username` LIKE ? OR `action` LIKE ? OR `target` LIKE ? OR `ip` LIKE ? OR `details` LIKE ?)';
			array_push($args, $like, $like, $like, $like, $like);
		}
		return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $args];
	}

	private function auditFormat($row)
	{
		$created = $row['created'];
		if (is_object($created) && method_exists($created, 'format')) {
			$created = $created->format('Y-m-d H:i:s');
		}
		$details = $row['details'] ? json_decode((string)$row['details'], true) : null;
		return [
			'id' => (int)$row['id'],
			'created' => $created ? str_replace(' ', 'T', (string)$created) . 'Z' : null,
			'userId' => $row['user_id'] !== null ? (int)$row['user_id'] : null,
			'username' => (string)$row['username'],
			'ip' => (string)$row['ip'],
			'category' => (string)$row['category'],
			'action' => (string)$row['action'],
			'target' => (string)$row['target'],
			'details' => is_array($details) ? $details : null,
		];
	}

	public function getAuditLog($params)
	{
		if (!$this->auditTableReady()) {
			$this->setAPIResponse('error', 'The audit log table could not be created', 500);
			return false;
		}
		$this->auditPurge();
		$pageSize = max(10, min(200, (int)($params['pageSize'] ?? 50)));
		$page = max(1, (int)($params['page'] ?? 1));
		[$where, $args] = $this->auditFilters($params);
		$total = (int)$this->processQueries([[
			'function' => 'fetchSingle',
			'query' => array_merge(['SELECT COUNT(*) FROM `audit_log`' . $where], $args),
		]]);
		$rows = $this->processQueries([[
			'function' => 'fetchAll',
			'query' => array_merge(['SELECT * FROM `audit_log`' . $where . ' ORDER BY `id` DESC LIMIT ' . $pageSize . ' OFFSET ' . (($page - 1) * $pageSize)], $args),
		]]) ?: [];
		return [
			'entries' => array_map([$this, 'auditFormat'], $rows),
			'total' => $total,
			'page' => $page,
			'pageSize' => $pageSize,
			'enabled' => !empty($this->config['auditLogEnabled']),
			'days' => (int)($this->config['auditLogDays'] ?? 365),
		];
	}

	/**
	 * The (filtered) log as CSV, newest first, at most 50,000 lines.
	 */
	public function auditLogCsv($params)
	{
		if (!$this->auditTableReady()) {
			return '';
		}
		[$where, $args] = $this->auditFilters($params);
		$rows = $this->processQueries([[
			'function' => 'fetchAll',
			'query' => array_merge(['SELECT * FROM `audit_log`' . $where . ' ORDER BY `id` DESC LIMIT 50000'], $args),
		]]) ?: [];
		$out = fopen('php://temp', 'w+');
		fputcsv($out, ['time (UTC)', 'user', 'ip', 'category', 'action', 'target', 'details'], ',', '"', '');
		foreach ($rows as $row) {
			$entry = $this->auditFormat($row);
			$line = [$entry['created'], $entry['username'], $entry['ip'], $entry['category'], $entry['action'], $entry['target'], $entry['details'] ? json_encode($entry['details'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : ''];
			// Spreadsheet programs run cells that start with = + - @ as formulas
			$line = array_map(function ($cell) {
				return preg_match('/^[=+\-@\t\r]/', (string)$cell) ? "'" . $cell : $cell;
			}, $line);
			fputcsv($out, $line, ',', '"', '');
		}
		rewind($out);
		$csv = stream_get_contents($out);
		fclose($out);
		return $csv;
	}

	public function clearAuditLog()
	{
		if (!$this->auditTableReady()) {
			return false;
		}
		$count = (int)$this->processQueries([['function' => 'fetchSingle', 'query' => 'SELECT COUNT(*) FROM `audit_log`']]);
		$this->processQueries([['function' => 'query', 'query' => 'DELETE FROM `audit_log`']]);
		// The clearing itself is the first entry of the new log
		$this->audit('audit.clear', '', ['removed' => $count]);
		$this->setAPIResponse('success', 'Audit log cleared', 200);
		return true;
	}
}
