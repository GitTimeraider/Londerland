<?php
// PLUGIN INFORMATION
$GLOBALS['plugins']['Achievements'] = array( // Plugin Name
	'name' => 'Achievements', // Plugin Name
	'author' => 'Londerland', // Who wrote the plugin
	'category' => 'Fun', // One to Two Word Description
	'link' => '', // Link to plugin info
	'license' => 'personal,business', // License Type use , for multiple
	'idPrefix' => 'ACHIEVEMENTS', // html element id prefix
	'configPrefix' => 'ACHIEVEMENTS', // config file prefix for array items without the hyphen
	'dbPrefix' => 'ACHIEVEMENTS', // db prefix
	'version' => '1.0.0', // SemVer of plugin
	'image' => 'api/plugins/achievements/logo.png', // 1:1 non transparent image for plugin
	'settings' => true, // does plugin need a settings modal?
	'bind' => true, // use default bind to make settings page - true or false
	'api' => 'api/v2/plugins/achievements/settings', // api route for settings page
	'homepage' => false // Is plugin for use on homepage? true or false
);

/**
 * Badges for logged in users plus a My Stats page with statistics about their own account.
 * The browser reports small events (page visit, tab opened, ...); the server keeps the counters
 * per user in one row and decides which badges are unlocked.
 */
class Achievements extends Londerland
{
	private const TABLE = 'ACHIEVEMENTS-users';
	// Stored as ACHIEVEMENTS-dbVersion once the table exists; raise it when the table changes
	private const DB_VERSION = '1';
	// Caps so a user cannot grow their row without limit
	private const MAX_TABS_TRACKED = 500;
	// ai: AI Chat created a chat or sent a message; only makes the server look at the AI Chat tables again
	// palette: the command palette (Ctrl+K) was opened
	private const EVENTS = ['visit', 'tab', 'fullscreen', 'lock', 'konami', 'logo', 'ai', 'split', 'popout', 'reload', 'marathon', 'bookmark', 'palette'];
	// XP: a badge gives 50 (secret ones 100, unless the badge says otherwise), the first visit of a day 10 plus 2 per
	// day of the streak (up to 20 more), and finishing all three daily quests 50 on top of the quests' own XP
	private const XP_BADGE = 50;
	private const XP_SECRET_BADGE = 100;
	private const XP_DAILY_VISIT = 10;
	private const XP_STREAK_STEP = 2;
	private const XP_STREAK_MAX = 10;
	private const XP_ALL_QUESTS = 50;
	private const QUESTS_PER_DAY = 3;

	/* ===================== achievements ===================== */

	/**
	 * Every badge is "stat >= target". Stats are counters kept per user, or computed when read (see _achComputedStats).
	 * secret: name and description stay hidden until unlocked. requires: only offered while that plugin is enabled.
	 * $all = true also returns the switched off badges and the ones waiting for another plugin (for the settings).
	 */
	public function _achDefinitions($all = false)
	{
		$definitions = [
			// Getting started
			['key' => 'hello_world', 'name' => 'Hello World', 'description' => 'Visit Londerland for the first time', 'icon' => '👋', 'category' => 'Getting started', 'stat' => 'visits', 'target' => 1],
			['key' => 'sudoer', 'name' => 'Sudoer', 'description' => 'Be a member of the Admin or Co-Admin group', 'icon' => '🔑', 'category' => 'Getting started', 'stat' => 'admin', 'target' => 1],
			['key' => 'year_one', 'name' => 'Year One', 'description' => 'Have an account for a year', 'icon' => '🎂', 'category' => 'Getting started', 'stat' => 'account_days', 'target' => 365, 'xp' => 250],
			// Loyalty
			['key' => 'regular', 'name' => 'Regular', 'description' => 'Use Londerland on 7 different days', 'icon' => '📅', 'category' => 'Loyalty', 'stat' => 'days', 'target' => 7],
			['key' => 'uptime', 'name' => 'Uptime 99.9%', 'description' => 'Use Londerland on 30 different days', 'icon' => '📈', 'category' => 'Loyalty', 'stat' => 'days', 'target' => 30],
			['key' => 'lts', 'name' => 'Long-Term Support', 'description' => 'Use Londerland on 100 different days', 'icon' => '🏛️', 'category' => 'Loyalty', 'stat' => 'days', 'target' => 100, 'xp' => 250],
			['key' => 'warm_cache', 'name' => 'Warm Cache', 'description' => 'Visit 3 days in a row', 'icon' => '🔥', 'category' => 'Loyalty', 'stat' => 'best_streak', 'target' => 3],
			['key' => 'always_on', 'name' => 'Always On', 'description' => 'Visit 7 days in a row', 'icon' => '⚡', 'category' => 'Loyalty', 'stat' => 'best_streak', 'target' => 7],
			['key' => 'no_downtime', 'name' => 'No Downtime', 'description' => 'Visit 30 days in a row', 'icon' => '🛡️', 'category' => 'Loyalty', 'stat' => 'best_streak', 'target' => 30, 'xp' => 250],
			// Tabs
			['key' => 'tab_hopper', 'name' => 'Tab Hopper', 'description' => 'Open 10 tabs', 'icon' => '🗂️', 'category' => 'Tabs', 'stat' => 'tabs', 'target' => 10],
			['key' => 'ctrl_tab', 'name' => 'Ctrl+Tab Master', 'description' => 'Open 100 tabs', 'icon' => '⌨️', 'category' => 'Tabs', 'stat' => 'tabs', 'target' => 100],
			['key' => 'context_switcher', 'name' => 'Context Switcher', 'description' => 'Open 1000 tabs', 'icon' => '🌀', 'category' => 'Tabs', 'stat' => 'tabs', 'target' => 1000],
			['key' => 'port_scanner', 'name' => 'Port Scanner', 'description' => 'Open 5 different tabs', 'icon' => '🔍', 'category' => 'Tabs', 'stat' => 'tabs_unique', 'target' => 5],
			['key' => 'network_mapper', 'name' => 'Network Mapper', 'description' => 'Open 15 different tabs', 'icon' => '🗺️', 'category' => 'Tabs', 'stat' => 'tabs_unique', 'target' => 15],
			['key' => 'kiosk_mode', 'name' => 'Kiosk Mode', 'description' => 'Switch to full screen', 'icon' => '🖥️', 'category' => 'Tabs', 'stat' => 'fullscreen', 'target' => 1],
			['key' => 'split_brain', 'name' => 'Split Brain', 'description' => 'Open two tabs side by side (Ctrl+Alt+click a tab)', 'icon' => '🧠', 'category' => 'Tabs', 'stat' => 'split', 'target' => 1],
			['key' => 'pop_out', 'name' => 'Pop-out', 'description' => 'Open a tab in a new browser window', 'icon' => '🪟', 'category' => 'Tabs', 'stat' => 'popout', 'target' => 1],
			['key' => 'refresh_rate', 'name' => 'Refresh Rate', 'description' => 'Reload tabs 25 times', 'icon' => '🔄', 'category' => 'Tabs', 'stat' => 'reload', 'target' => 25],
			['key' => 'screen_locker', 'name' => 'Screen Locker', 'description' => 'Lock your screen', 'icon' => '🔒', 'category' => 'Tabs', 'stat' => 'lock', 'target' => 1],
			['key' => 'power_user', 'name' => 'Power User', 'description' => 'Open the command palette (Ctrl+K) 10 times', 'icon' => '⌘', 'category' => 'Tabs', 'stat' => 'palette', 'target' => 10],
			// Time of day
			['key' => 'night_shift', 'name' => 'Night Shift', 'description' => 'Visit between midnight and 5 in the morning', 'icon' => '🦉', 'category' => 'Time', 'stat' => 'night', 'target' => 1],
			['key' => 'early_cron', 'name' => 'Early Cron Job', 'description' => 'Visit between 5 and 7 in the morning', 'icon' => '🌅', 'category' => 'Time', 'stat' => 'early', 'target' => 1],
			['key' => 'on_call', 'name' => 'On-Call Weekend', 'description' => 'Visit on 5 weekend days', 'icon' => '📟', 'category' => 'Time', 'stat' => 'weekend', 'target' => 5],
			['key' => 'marathon', 'name' => 'Marathon Session', 'description' => 'Keep Londerland open for 4 hours in a row', 'icon' => '🏃', 'category' => 'Time', 'stat' => 'marathon', 'target' => 1],
			['key' => 'friday_deploy', 'name' => 'Friday Deploy', 'description' => 'Use Londerland on a Friday after 17:00. Brave!', 'icon' => '🚀', 'category' => 'Time', 'stat' => 'friday_deploy', 'target' => 1],
			// Special days
			['key' => 'epoch_rollover', 'name' => 'Epoch Rollover', 'description' => 'Visit on New Year\'s Day', 'icon' => '🎆', 'category' => 'Special days', 'stat' => 'day_newyear', 'target' => 1],
			['key' => 'leap_day', 'name' => 'Leap Day', 'description' => 'Visit on 29 February', 'icon' => '🐸', 'category' => 'Special days', 'stat' => 'day_leap', 'target' => 1],
			['key' => 'pi_day', 'name' => 'Pi Day', 'description' => 'Visit on 14 March (3.14)', 'icon' => '🥧', 'category' => 'Special days', 'stat' => 'day_pi', 'target' => 1],
			['key' => 'backup_day', 'name' => 'World Backup Day', 'description' => 'Visit on 31 March. Did you make a backup?', 'icon' => '💽', 'category' => 'Special days', 'stat' => 'day_backup', 'target' => 1],
			['key' => 'fools_commit', 'name' => 'Fool\'s Commit', 'description' => 'Visit on April Fools\' Day', 'icon' => '🃏', 'category' => 'Special days', 'stat' => 'day_april1', 'target' => 1],
			['key' => 'may_the_fourth', 'name' => 'May the 4th', 'description' => 'Visit on 4 May', 'icon' => '⭐', 'category' => 'Special days', 'stat' => 'day_may4', 'target' => 1],
			['key' => 'thank_sysadmin', 'name' => 'Thank Your Sysadmin', 'description' => 'Visit on SysAdmin Day (last Friday of July)', 'icon' => '☕', 'category' => 'Special days', 'stat' => 'day_sysadmin', 'target' => 1],
			['key' => 'hex_100', 'name' => '0x100', 'description' => 'Visit on Programmers\' Day (day 256 of the year)', 'icon' => '💾', 'category' => 'Special days', 'stat' => 'day_programmers', 'target' => 1],
			['key' => 'spooky_scripts', 'name' => 'Spooky Scripts', 'description' => 'Visit on Halloween', 'icon' => '🎃', 'category' => 'Special days', 'stat' => 'day_halloween', 'target' => 1],
			['key' => 'merry_uptime', 'name' => 'Merry Uptime', 'description' => 'Visit during Christmas (24 to 26 December)', 'icon' => '🎄', 'category' => 'Special days', 'stat' => 'day_xmas', 'target' => 1],
			// AI Chat
			['key' => 'prompt_engineer', 'name' => 'Prompt Engineer', 'description' => 'Start your first AI chat', 'icon' => '🤖', 'category' => 'AI Chat', 'stat' => 'ai_chats', 'target' => 1, 'requires' => 'AICHAT'],
			['key' => 'token_burner', 'name' => 'Token Burner', 'description' => 'Send 100 messages to the AI', 'icon' => '🪙', 'category' => 'AI Chat', 'stat' => 'ai_messages', 'target' => 100, 'requires' => 'AICHAT'],
			// Bookmarks
			['key' => 'bookworm', 'name' => 'Bookworm', 'description' => 'Open 10 bookmarks', 'icon' => '📚', 'category' => 'Bookmarks', 'stat' => 'bookmark', 'target' => 10, 'requires' => 'BOOKMARK'],
			// Levels & quests (only while XP, levels and daily quests are switched on)
			['key' => 'first_quest', 'name' => 'Side Quest', 'description' => 'Complete a daily quest', 'icon' => '📜', 'category' => 'Levels & quests', 'stat' => 'quests_done', 'target' => 1, 'requires' => 'XP'],
			['key' => 'quest_log', 'name' => 'Quest Log', 'description' => 'Complete 25 daily quests', 'icon' => '🗒️', 'category' => 'Levels & quests', 'stat' => 'quests_done', 'target' => 25, 'requires' => 'XP'],
			['key' => 'perfect_day', 'name' => 'Perfect Day', 'description' => 'Complete all three daily quests on one day', 'icon' => '🌟', 'category' => 'Levels & quests', 'stat' => 'perfect_days', 'target' => 1, 'requires' => 'XP'],
			['key' => 'level_5', 'name' => 'Promoted', 'description' => 'Reach level 5', 'icon' => '🎖️', 'category' => 'Levels & quests', 'stat' => 'level', 'target' => 5, 'requires' => 'XP'],
			['key' => 'level_10', 'name' => 'Double Digits', 'description' => 'Reach level 10', 'icon' => '🔟', 'category' => 'Levels & quests', 'stat' => 'level', 'target' => 10, 'requires' => 'XP', 'xp' => 250],
			// Secrets
			['key' => 'konami', 'name' => '↑↑↓↓←→←→BA', 'description' => 'Enter the Konami code', 'icon' => '🎮', 'category' => 'Secrets', 'stat' => 'konami', 'target' => 1, 'secret' => true],
			['key' => 'rubber_duck', 'name' => 'Rubber Duck Debugging', 'description' => 'Click the logo 10 times in a row', 'icon' => '🦆', 'category' => 'Secrets', 'stat' => 'logo', 'target' => 1, 'secret' => true],
			// Meta
			['key' => 'completionist', 'name' => 'Achievement Hunter', 'description' => 'Unlock 15 other badges', 'icon' => '🏆', 'category' => 'Meta', 'stat' => 'unlocked', 'target' => 15, 'xp' => 250],
		];
		if ($all) {
			return $definitions;
		}
		$disabled = $this->_achSplitList($this->config['ACHIEVEMENTS-disabled'] ?? '');
		return array_values(array_filter($definitions, function ($definition) use ($disabled) {
			if (in_array($definition['key'], $disabled, true)) {
				return false;
			}
			if (($definition['requires'] ?? '') === 'XP') {
				return $this->_achXpEnabled();
			}
			return !isset($definition['requires']) || !empty($this->config[$definition['requires'] . '-enabled']);
		}));
	}

	/* ===================== XP, levels & daily quests ===================== */

	public function _achXpEnabled()
	{
		return !array_key_exists('ACHIEVEMENTS-xp-include', $this->config) || filter_var($this->config['ACHIEVEMENTS-xp-include'], FILTER_VALIDATE_BOOLEAN);
	}

	private function _achBadgeXp($definition)
	{
		return (int)($definition['xp'] ?? (!empty($definition['secret']) ? self::XP_SECRET_BADGE : self::XP_BADGE));
	}

	// Total XP needed for a level: 0, 100, 300, 600, 1000, ... (each level asks 100 more than the one before)
	public function _achXpForLevel($level)
	{
		return 50 * $level * ($level - 1);
	}

	public function _achLevel($xp)
	{
		$level = 1;
		while ($level < 999 && $xp >= $this->_achXpForLevel($level + 1)) {
			$level++;
		}
		return $level;
	}

	public function _achLevelTitle($level)
	{
		$titles = [1 => 'Guest Account', 2 => 'Intern', 3 => 'Helpdesk', 4 => 'Junior Admin', 6 => 'Sysadmin', 9 => 'Senior Sysadmin', 12 => 'DevOps Engineer', 16 => 'Site Reliability Engineer', 20 => 'Architect', 25 => 'Root', 30 => 'Kernel Hacker', 40 => 'Uptime Legend'];
		$title = $titles[1];
		foreach ($titles as $from => $name) {
			if ($level >= $from) {
				$title = $name;
			}
		}
		return $title;
	}

	private function _achLevelInfo($xp)
	{
		$level = $this->_achLevel($xp);
		$from = $this->_achXpForLevel($level);
		$to = $this->_achXpForLevel($level + 1);
		return ['level' => $level, 'title' => $this->_achLevelTitle($level), 'xp' => $xp, 'levelXp' => $xp - $from, 'levelSize' => $to - $from, 'nextLevelXp' => $to];
	}

	/**
	 * Daily quests: what counts today (counter) and how often (target). requires: only while that plugin is enabled.
	 */
	private function _achQuestPool()
	{
		$pool = [
			['key' => 'visits', 'name' => 'Check In', 'description' => 'Open Londerland 2 times today', 'icon' => '👋', 'counter' => 'visit', 'target' => 2, 'xp' => 20],
			['key' => 'tabs', 'name' => 'Busy Day', 'description' => 'Open 8 tabs today', 'icon' => '🗂️', 'counter' => 'tab', 'target' => 8, 'xp' => 30],
			['key' => 'tabs_unique', 'name' => 'Explorer', 'description' => 'Open 3 different tabs today', 'icon' => '🧭', 'counter' => 'tab_unique', 'target' => 3, 'xp' => 30],
			['key' => 'reload', 'name' => 'Turn It Off and On Again', 'description' => 'Reload a tab 3 times today', 'icon' => '🔄', 'counter' => 'reload', 'target' => 3, 'xp' => 20],
			['key' => 'split', 'name' => 'Multitasker', 'description' => 'Open two tabs side by side (Ctrl+Alt+click a tab)', 'icon' => '🧠', 'counter' => 'split', 'target' => 1, 'xp' => 40],
			['key' => 'popout', 'name' => 'Pop-out', 'description' => 'Open a tab in a new browser window', 'icon' => '🪟', 'counter' => 'popout', 'target' => 1, 'xp' => 30],
			['key' => 'palette', 'name' => 'Keyboard Warrior', 'description' => 'Open the command palette (Ctrl+K) 2 times', 'icon' => '⌨️', 'counter' => 'palette', 'target' => 2, 'xp' => 30],
			['key' => 'fullscreen', 'name' => 'Big Screen', 'description' => 'Switch to full screen', 'icon' => '🖥️', 'counter' => 'fullscreen', 'target' => 1, 'xp' => 30],
			['key' => 'mystats', 'name' => 'Status Report', 'description' => 'Look at My Stats', 'icon' => '📊', 'counter' => 'mystats', 'target' => 1, 'xp' => 20],
			['key' => 'bookmarks', 'name' => 'Bookworm', 'description' => 'Open 2 bookmarks today', 'icon' => '📚', 'counter' => 'bookmark', 'target' => 2, 'xp' => 30, 'requires' => 'BOOKMARK'],
			['key' => 'ai', 'name' => 'Ask the Oracle', 'description' => 'Send 3 messages to the AI today', 'icon' => '🤖', 'counter' => 'ai', 'target' => 3, 'xp' => 40, 'requires' => 'AICHAT'],
		];
		return array_values(array_filter($pool, function ($quest) {
			return !isset($quest['requires']) || !empty($this->config[$quest['requires'] . '-enabled']);
		}));
	}

	/**
	 * Today's quests are picked from the pool by the user and the date, so they stay the same all day and differ
	 * per user and per day; a new local day starts new quests.
	 */
	private function _achToday(&$stats, DateTime $day)
	{
		$date = $day->format('Y-m-d');
		$today = $stats['today'] ?? null;
		if (!is_array($today) || ($today['date'] ?? '') !== $date) {
			$pool = $this->_achQuestPool();
			$seed = $this->_achUserId() . '|' . $date;
			usort($pool, function ($a, $b) use ($seed) {
				return strcmp(hash('sha256', $seed . '|' . $a['key']), hash('sha256', $seed . '|' . $b['key']));
			});
			$today = [
				'date' => $date,
				'counts' => [],
				'tabs' => [],
				'quests' => array_slice(array_column($pool, 'key'), 0, self::QUESTS_PER_DAY),
				'done' => [],
				'bonus' => false,
			];
		}
		$stats['today'] = $today;
	}

	private function _achCountToday(&$stats, $counter, $tabId = null)
	{
		$counts = (array)($stats['today']['counts'] ?? []);
		$counts[$counter] = (int)($counts[$counter] ?? 0) + 1;
		if ($tabId) {
			$tabs = array_map('intval', (array)($stats['today']['tabs'] ?? []));
			if (!in_array($tabId, $tabs, true) && count($tabs) < 100) {
				$tabs[] = $tabId;
			}
			$stats['today']['tabs'] = $tabs;
			$counts['tab_unique'] = count($tabs);
		}
		$stats['today']['counts'] = $counts;
	}

	private function _achQuestList($stats)
	{
		$pool = [];
		foreach ($this->_achQuestPool() as $quest) {
			$pool[$quest['key']] = $quest;
		}
		$today = (array)($stats['today'] ?? []);
		$list = [];
		foreach ((array)($today['quests'] ?? []) as $key) {
			if (!isset($pool[$key])) {
				continue;
			}
			$quest = $pool[$key];
			$progress = (int)(((array)($today['counts'] ?? []))[$quest['counter']] ?? 0);
			$list[] = [
				'key' => $key,
				'name' => $quest['name'],
				'description' => $quest['description'],
				'icon' => $quest['icon'],
				'xp' => $quest['xp'],
				'target' => $quest['target'],
				'progress' => min($quest['target'], $progress),
				'done' => in_array($key, (array)($today['done'] ?? []), true),
			];
		}
		return $list;
	}

	/**
	 * Marks finished quests as done and gives their XP (and the bonus when all are done). Returns what to announce.
	 */
	private function _achCompleteQuests(&$stats)
	{
		$announce = [];
		$quests = $this->_achQuestList($stats);
		foreach ($quests as $quest) {
			if (!$quest['done'] && $quest['progress'] >= $quest['target']) {
				$stats['today']['done'][] = $quest['key'];
				$stats['xp'] = (int)($stats['xp'] ?? 0) + $quest['xp'];
				$this->_achIncrement($stats, 'quests_done');
				$announce[] = ['type' => 'quest', 'name' => $quest['name'], 'icon' => $quest['icon'], 'xp' => $quest['xp']];
			}
		}
		$allDone = $quests && count(array_intersect(array_column($quests, 'key'), (array)$stats['today']['done'])) === count($quests);
		if ($allDone && empty($stats['today']['bonus'])) {
			$stats['today']['bonus'] = true;
			$stats['xp'] = (int)($stats['xp'] ?? 0) + self::XP_ALL_QUESTS;
			$this->_achIncrement($stats, 'perfect_days');
			$announce[] = ['type' => 'bonus', 'name' => 'All daily quests done', 'icon' => '🌟', 'xp' => self::XP_ALL_QUESTS];
		}
		return $announce;
	}

	/**
	 * Accounts from before XP existed start with the XP of their badges and active days.
	 */
	private function _achMigrateXp(&$stats, $unlocked)
	{
		if (isset($stats['xp'])) {
			return;
		}
		$xp = (int)($stats['days'] ?? 0) * self::XP_DAILY_VISIT;
		foreach ($this->_achDefinitions(true) as $definition) {
			if (isset($unlocked[$definition['key']])) {
				$xp += $this->_achBadgeXp($definition);
			}
		}
		$stats['xp'] = $xp;
	}

	/* ===================== access & setup ===================== */

	/**
	 * Logged in user (never a guest) in a group that collects achievements, with the table in place.
	 */
	public function _achAccess($request)
	{
		if (!$this->checkRoute($request)) {
			return false;
		}
		if (!$this->config['ACHIEVEMENTS-enabled'] || !$this->hasDB()) {
			$this->setAPIResponse('error', 'Achievements are not enabled', 404);
			return false;
		}
		$userId = $this->user['userID'] ?? null;
		$groupId = (int)($this->user['groupID'] ?? 999);
		if (!$userId || empty($this->user['loggedin']) || $groupId >= 999) {
			$this->setAPIResponse('error', 'Please log in to collect achievements', 401);
			return false;
		}
		if (!in_array($groupId, $this->_achAllowedGroups(), true)) {
			$this->setAPIResponse('error', 'Your group does not collect achievements', 401);
			return false;
		}
		$this->_achEnsureTable();
		return true;
	}

	public function _achAdminAccess($request)
	{
		return $this->checkRoute($request) && $this->qualifyRequest(1, true);
	}

	/**
	 * Groups chosen in the settings; 'auto' = every group except guests. Guests have no account, so never collect.
	 */
	public function _achAllowedGroups()
	{
		$setting = trim((string)($this->config['ACHIEVEMENTS-groups-include'] ?? 'auto'));
		if ($setting === 'auto' || $setting === '') {
			$allowed = array_map(function ($group) {
				return (int)$group['value'];
			}, $this->groupSelect());
		} else {
			$allowed = array_map('intval', $this->_achSplitList($setting));
		}
		return array_values(array_filter($allowed, function ($group) {
			return $group < 999;
		}));
	}

	private function _achSplitList($value)
	{
		return array_values(array_filter(array_map('trim', explode(',', (string)$value)), 'strlen'));
	}

	private function _achUserId()
	{
		return (int)$this->user['userID'];
	}

	private function _achTableExists($table)
	{
		if ($this->config['driver'] == 'sqlite3') {
			$query = ["SELECT `name` FROM `sqlite_master` WHERE `type` = 'table' AND `name` = %s", $table];
		} else {
			$query = ['SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s', (string)$this->config['dbName'], $table];
		}
		return (bool)$this->processQueries([['function' => 'fetchSingle', 'query' => $query]]);
	}

	/**
	 * Creates the table on first use. Once it exists, ACHIEVEMENTS-dbVersion remembers that, so later requests skip
	 * the check; $force checks anyway (a read failed, for example after restoring an older database).
	 */
	private function _achEnsureTable($force = false)
	{
		if (!$force && ($this->config['ACHIEVEMENTS-dbVersion'] ?? '') === self::DB_VERSION) {
			return;
		}
		if (!$this->_achTableExists(self::TABLE)) {
			$this->processQueries([[
				'function' => 'query',
				'query' => 'CREATE TABLE `ACHIEVEMENTS-users` (
					`user_id`	INTEGER PRIMARY KEY,
					`stats`	LONGTEXT,
					`unlocked`	LONGTEXT,
					`updated`	DATETIME
				);'
			]]);
		}
		if (($this->config['ACHIEVEMENTS-dbVersion'] ?? '') !== self::DB_VERSION) {
			$this->updateConfig(['ACHIEVEMENTS-dbVersion' => self::DB_VERSION]);
		}
	}

	/* ===================== storage ===================== */

	private function _achLoad($userId)
	{
		$query = [[
			'function' => 'fetch',
			'query' => ['SELECT * FROM `ACHIEVEMENTS-users` WHERE `user_id` = ?', (int)$userId]
		]];
		$row = $this->processQueries($query);
		// false = the query failed (no row is null): the table is gone, so create it again
		if ($row === false) {
			$this->_achEnsureTable(true);
			$row = $this->processQueries($query);
		}
		$stats = $row ? json_decode((string)$row['stats'], true) : null;
		$unlocked = $row ? json_decode((string)$row['unlocked'], true) : null;
		return [is_array($stats) ? $stats : [], is_array($unlocked) ? $unlocked : []];
	}

	private function _achSave($userId, $stats, $unlocked)
	{
		$this->processQueries([
			['function' => 'query', 'query' => ['DELETE FROM `ACHIEVEMENTS-users` WHERE `user_id` = ?', (int)$userId]],
			['function' => 'query', 'query' => ['INSERT INTO [ACHIEVEMENTS-users]', [
				'user_id' => (int)$userId,
				'stats' => json_encode($stats),
				'unlocked' => json_encode((object)$unlocked),
				'updated' => gmdate('Y-m-d H:i:s'),
			]]],
		]);
	}

	/* ===================== tracking ===================== */

	/**
	 * The browser's local date (for streaks and special days), trusted only when it is within a day of the server's date.
	 */
	private function _achLocalTime($local)
	{
		$date = is_array($local) ? (string)($local['date'] ?? '') : '';
		$hour = is_array($local) && isset($local['hour']) ? (int)$local['hour'] : (int)date('G');
		$parsed = DateTime::createFromFormat('!Y-m-d', $date);
		if (!$parsed || $parsed->format('Y-m-d') !== $date || abs($parsed->getTimestamp() - strtotime(gmdate('Y-m-d'))) > 86400) {
			$parsed = new DateTime(date('Y-m-d'));
			$hour = (int)date('G');
		}
		return [$parsed, max(0, min(23, $hour))];
	}

	private function _achIncrement(&$stats, $key, $by = 1)
	{
		$stats[$key] = (int)($stats[$key] ?? 0) + $by;
	}

	private function _achCountActivity(&$stats, DateTime $day, $hour)
	{
		$hours = array_pad(array_slice((array)($stats['hours'] ?? []), 0, 24), 24, 0);
		$hours[$hour] = (int)$hours[$hour] + 1;
		$stats['hours'] = array_map('intval', $hours);
		$weekdays = array_pad(array_slice((array)($stats['weekdays'] ?? []), 0, 7), 7, 0);
		$weekday = (int)$day->format('w');
		$weekdays[$weekday] = (int)$weekdays[$weekday] + 1;
		$stats['weekdays'] = array_map('intval', $weekdays);
		if ($weekday === 5 && $hour >= 17) {
			$stats['friday_deploy'] = 1;
		}
	}

	private function _achTrackVisit(&$stats, DateTime $day, $hour)
	{
		$this->_achIncrement($stats, 'visits');
		$this->_achCountActivity($stats, $day, $hour);
		$stats['previous_visit'] = $stats['last_visit'] ?? null;
		$stats['last_visit'] = gmdate('Y-m-d H:i:s');
		$today = $day->format('Y-m-d');
		$stats['first_day'] = $stats['first_day'] ?? $today;
		$lastDay = $stats['last_day'] ?? null;
		if ($lastDay !== $today) {
			$this->_achIncrement($stats, 'days');
			$yesterday = (clone $day)->modify('-1 day')->format('Y-m-d');
			$stats['streak'] = ($lastDay === $yesterday) ? (int)($stats['streak'] ?? 0) + 1 : 1;
			$stats['best_streak'] = max((int)($stats['best_streak'] ?? 0), $stats['streak']);
			$stats['last_day'] = $today;
			if ($this->_achXpEnabled()) {
				$stats['xp'] = (int)($stats['xp'] ?? 0) + self::XP_DAILY_VISIT + self::XP_STREAK_STEP * min(self::XP_STREAK_MAX, $stats['streak'] - 1);
			}
			if ((int)$day->format('w') === 0 || (int)$day->format('w') === 6) {
				$this->_achIncrement($stats, 'weekend');
			}
		}
		if ($hour < 5) {
			$this->_achIncrement($stats, 'night');
		} elseif ($hour < 7) {
			$this->_achIncrement($stats, 'early');
		}
		foreach ($this->_achSpecialDays($day) as $special) {
			$stats['day_' . $special] = 1;
		}
	}

	private function _achSpecialDays(DateTime $day)
	{
		$days = [];
		$monthDay = $day->format('m-d');
		if ($monthDay === '01-01') {
			$days[] = 'newyear';
		}
		if ($monthDay === '02-29') {
			$days[] = 'leap';
		}
		if ($monthDay === '03-14') {
			$days[] = 'pi';
		}
		if ($monthDay === '03-31') {
			$days[] = 'backup';
		}
		if ($monthDay === '04-01') {
			$days[] = 'april1';
		}
		if ($monthDay === '05-04') {
			$days[] = 'may4';
		}
		if ($monthDay === '10-31') {
			$days[] = 'halloween';
		}
		if (in_array($monthDay, ['12-24', '12-25', '12-26'], true)) {
			$days[] = 'xmas';
		}
		if ((int)$day->format('z') === 255) {
			$days[] = 'programmers';
		}
		$sysadminDay = new DateTime('last friday of july ' . $day->format('Y'));
		if ($sysadminDay->format('Y-m-d') === $day->format('Y-m-d')) {
			$days[] = 'sysadmin';
		}
		return $days;
	}

	public function _achTrack($data)
	{
		$event = (string)($data['event'] ?? '');
		if (!in_array($event, self::EVENTS, true)) {
			$this->setAPIResponse('error', 'Unknown event', 422);
			return false;
		}
		[$day, $hour] = $this->_achLocalTime($data['local'] ?? null);
		[$stats, $unlocked] = $this->_achLoad($this->_achUserId());
		$xpOn = $this->_achXpEnabled();
		if ($xpOn) {
			$this->_achMigrateXp($stats, $unlocked);
			$this->_achToday($stats, $day);
		}
		$levelBefore = $this->_achLevel((int)($stats['xp'] ?? 0));
		switch ($event) {
			case 'visit':
				$this->_achTrackVisit($stats, $day, $hour);
				break;
			case 'tab':
				$tabId = (int)($data['tab'] ?? 0);
				if ($tabId <= 0) {
					$this->setAPIResponse('error', 'No tab supplied', 422);
					return false;
				}
				$this->_achIncrement($stats, 'tabs');
				$this->_achCountActivity($stats, $day, $hour);
				if ($xpOn) {
					$this->_achCountToday($stats, 'tab', $tabId);
				}
				$counts = (array)($stats['tab_counts'] ?? []);
				if (isset($counts[$tabId]) || count($counts) < self::MAX_TABS_TRACKED) {
					$counts[$tabId] = (int)($counts[$tabId] ?? 0) + 1;
				}
				$stats['tab_counts'] = $counts;
				break;
			case 'ai':
				// counted below
				break;
			default:
				// fullscreen, lock, split, popout, reload, bookmark, palette: counters; konami, logo, marathon: only need to happen once
				$this->_achIncrement($stats, $event);
		}
		if ($xpOn && $event !== 'tab') {
			$this->_achCountToday($stats, $event);
		}
		// new AI chats and messages are reported with "ai"; a visit catches up on ones from other devices
		if ($event === 'ai' || $event === 'visit') {
			$this->_achCountAiChat($stats);
		}
		[$new, $quests, $levelUp] = $this->_achProgress($stats, $unlocked, $levelBefore);
		$this->_achSave($this->_achUserId(), $stats, $unlocked);
		$this->setAPIResponse('success', null, 200, ['unlocked' => $new, 'quests' => $quests, 'levelUp' => $levelUp]);
		return $new;
	}

	/**
	 * After counting: finished quests, new badges (with their XP), and a level up. Badges can bring enough XP for a
	 * level badge, so it looks again until nothing changes.
	 */
	private function _achProgress(&$stats, &$unlocked, $levelBefore)
	{
		$xpOn = $this->_achXpEnabled();
		$quests = $xpOn ? $this->_achCompleteQuests($stats) : [];
		$new = [];
		for ($round = 0; $round < 5; $round++) {
			$found = $this->_achEvaluate($stats, $unlocked);
			if (!$found) {
				break;
			}
			foreach ($found as $badge) {
				if ($xpOn) {
					$stats['xp'] = (int)($stats['xp'] ?? 0) + $badge['xp'];
				}
				$new[] = $badge;
			}
		}
		$levelUp = null;
		if ($xpOn) {
			$level = $this->_achLevel((int)($stats['xp'] ?? 0));
			if ($level > $levelBefore) {
				$levelUp = ['level' => $level, 'title' => $this->_achLevelTitle($level)];
			}
		}
		return [$new, $quests, $levelUp];
	}

	/* ===================== evaluation ===================== */

	/**
	 * AI chats and messages, kept as the highest number ever seen in the AI Chat tables: deleting chats, editing a
	 * question or answering again removes rows there, but must not lower the count or undo progress.
	 * The browser reports every new chat and message ("ai"), so this runs right after each one, and on visits.
	 */
	private function _achCountAiChat(&$stats)
	{
		if (empty($this->config['AICHAT-enabled']) || !$this->_achAiTablesExist()) {
			return;
		}
		$chats = (int)$this->processQueries([[
			'function' => 'fetchSingle',
			'query' => ['SELECT COUNT(*) FROM `AICHAT-chats` WHERE `user_id` = ?', $this->_achUserId()]
		]]);
		$messages = (int)$this->processQueries([[
			'function' => 'fetchSingle',
			'query' => ['SELECT COUNT(*) FROM `AICHAT-messages` m INNER JOIN `AICHAT-chats` c ON c.`id` = m.`chat_id` WHERE c.`user_id` = ? AND m.`role` = %s', $this->_achUserId(), 'user']
		]]);
		$stats['ai_chats'] = max((int)($stats['ai_chats'] ?? 0), $chats);
		$stats['ai_messages'] = max((int)($stats['ai_messages'] ?? 0), $messages);
	}

	// AI Chat creates its tables on first use; once they exist, ACHIEVEMENTS-aiTables remembers that
	private function _achAiTablesExist()
	{
		if (!empty($this->config['ACHIEVEMENTS-aiTables'])) {
			return true;
		}
		if (!$this->_achTableExists('AICHAT-chats') || !$this->_achTableExists('AICHAT-messages')) {
			return false;
		}
		$this->updateConfig(['ACHIEVEMENTS-aiTables' => true]);
		return true;
	}

	/**
	 * Values that are not counted but looked up when needed (account age, number of badges).
	 */
	private function _achComputedStats($stats)
	{
		$user = $this->processQueries([[
			'function' => 'fetch',
			'query' => ['SELECT `register_date` FROM `users` WHERE `id` = ?', $this->_achUserId()]
		]]);
		$registered = $user && $user['register_date'] ? strtotime($this->_achDateString($user['register_date']) . ' UTC') : false;
		$computed = [
			'tabs_unique' => count((array)($stats['tab_counts'] ?? [])),
			'admin' => (int)($this->user['groupID'] ?? 999) <= 1 ? 1 : 0,
			'account_days' => $registered ? (int)floor((time() - $registered) / 86400) : 0,
			'level' => $this->_achLevel((int)($stats['xp'] ?? 0)),
		];
		return $computed;
	}

	private function _achDateString($value)
	{
		if (is_object($value) && method_exists($value, 'format')) {
			return $value->format('Y-m-d H:i:s');
		}
		return (string)$value;
	}

	private function _achAllStats($stats, $unlocked)
	{
		$all = array_merge($stats, $this->_achComputedStats($stats));
		$all['unlocked'] = count($unlocked);
		return $all;
	}

	/**
	 * Unlocks every badge whose target is reached; returns the newly unlocked ones. Badges stay unlocked
	 * even if a computed value drops later (for example after leaving the Admin group).
	 */
	private function _achEvaluate($stats, &$unlocked)
	{
		$all = $this->_achAllStats($stats, $unlocked);
		$new = [];
		$definitions = $this->_achDefinitions();
		// The meta badge counts the others, so it goes last
		usort($definitions, function ($a, $b) {
			return ($a['stat'] === 'unlocked') <=> ($b['stat'] === 'unlocked');
		});
		foreach ($definitions as $definition) {
			if (isset($unlocked[$definition['key']])) {
				continue;
			}
			if ($definition['stat'] === 'unlocked') {
				$all['unlocked'] = count($unlocked);
			}
			if ((int)($all[$definition['stat']] ?? 0) >= $definition['target']) {
				$unlocked[$definition['key']] = gmdate('Y-m-d\TH:i:s\Z');
				$new[] = $this->_achPublicDefinition($definition, $all, $unlocked);
			}
		}
		return $new;
	}

	private function _achPublicDefinition($definition, $all, $unlocked)
	{
		$isUnlocked = isset($unlocked[$definition['key']]);
		$hidden = !empty($definition['secret']) && !$isUnlocked;
		return [
			'key' => $definition['key'],
			'name' => $hidden ? '???' : $definition['name'],
			'description' => $hidden ? 'Secret badge. Keep exploring!' : $definition['description'],
			'icon' => $hidden ? '❔' : $definition['icon'],
			'category' => $definition['category'],
			'secret' => !empty($definition['secret']),
			'target' => $definition['target'],
			'progress' => $hidden ? 0 : min($definition['target'], (int)($all[$definition['stat']] ?? 0)),
			'unlocked' => $isUnlocked ? $unlocked[$definition['key']] : null,
			'xp' => $this->_achBadgeXp($definition),
		];
	}

	/* ===================== My Stats ===================== */

	public function _achMyStats($local = null)
	{
		[$stats, $unlocked] = $this->_achLoad($this->_achUserId());
		$before = $stats;
		$xpOn = $this->_achXpEnabled();
		$this->_achCountAiChat($stats);
		if ($xpOn) {
			$this->_achMigrateXp($stats, $unlocked);
			[$day] = $this->_achLocalTime($local);
			$this->_achToday($stats, $day);
			// looking at this page is one of the quests
			$this->_achCountToday($stats, 'mystats');
		}
		// Badges such as Sudoer or Year One can be earned without any new event
		[$new, $quests, $levelUp] = $this->_achProgress($stats, $unlocked, $this->_achLevel((int)($stats['xp'] ?? 0)));
		if ($new || $stats !== $before) {
			$this->_achSave($this->_achUserId(), $stats, $unlocked);
		}
		$all = $this->_achAllStats($stats, $unlocked);
		$achievements = array_map(function ($definition) use ($all, $unlocked) {
			return $this->_achPublicDefinition($definition, $all, $unlocked);
		}, $this->_achDefinitions());
		$account = $this->processQueries([[
			'function' => 'fetch',
			'query' => ['SELECT `username`, `email`, `group`, `register_date`, `auth_service` FROM `users` WHERE `id` = ?', $this->_achUserId()]
		]]);
		$sessions = (int)$this->processQueries([[
			'function' => 'fetchSingle',
			'query' => ['SELECT COUNT(*) FROM `tokens` WHERE `user_id` = ? AND `expires` > ?', $this->_achUserId(), gmdate('Y-m-d H:i:s')]
		]]);
		$data = [
			'account' => [
				'username' => $account['username'] ?? $this->user['username'],
				'email' => $account['email'] ?? '',
				'group' => $account['group'] ?? ($this->user['group'] ?? ''),
				'registered' => !empty($account['register_date']) ? $this->_achIsoDate($account['register_date']) : null,
				'accountDays' => $all['account_days'],
				'signIn' => $this->_achSignIn($account['auth_service'] ?? 'internal'),
				'sessions' => $sessions,
			],
			'activity' => [
				'visits' => (int)($stats['visits'] ?? 0),
				'days' => (int)($stats['days'] ?? 0),
				'streak' => $this->_achCurrentStreak($stats),
				'bestStreak' => (int)($stats['best_streak'] ?? 0),
				'firstDay' => $stats['first_day'] ?? null,
				'previousVisit' => !empty($stats['previous_visit']) ? $this->_achIsoDate($stats['previous_visit']) : null,
				'tabs' => (int)($stats['tabs'] ?? 0),
				'tabsUnique' => $all['tabs_unique'],
				'topTabs' => $this->_achTopTabs((array)($stats['tab_counts'] ?? [])),
				'hours' => array_map('intval', array_pad(array_slice((array)($stats['hours'] ?? []), 0, 24), 24, 0)),
				'weekdays' => array_map('intval', array_pad(array_slice((array)($stats['weekdays'] ?? []), 0, 7), 7, 0)),
				'aiEnabled' => !empty($this->config['AICHAT-enabled']),
				'aiChats' => (int)($all['ai_chats'] ?? 0),
				'aiMessages' => (int)($all['ai_messages'] ?? 0),
			],
			'achievements' => $achievements,
			'level' => $xpOn ? $this->_achLevelInfo((int)($stats['xp'] ?? 0)) : null,
			'quests' => $xpOn ? $this->_achQuestList($stats) : null,
			'questsBonus' => $xpOn ? ['done' => !empty($stats['today']['bonus']), 'xp' => self::XP_ALL_QUESTS] : null,
			'questsDone' => (int)($stats['quests_done'] ?? 0),
			'announce' => ['unlocked' => $new, 'quests' => $quests, 'levelUp' => $levelUp],
			'leaderboard' => !empty($this->config['ACHIEVEMENTS-leaderboard-include']) ? $this->_achLeaderboard(10) : null,
		];
		$this->setAPIResponse('success', null, 200, $data);
		return $data;
	}

	/**
	 * How the account signs in, without the stored value itself: for two-factor authentication it holds the secret.
	 */
	private function _achSignIn($authService)
	{
		$parts = explode('::', (string)$authService, 2);
		if ($parts[0] === 'oidc') {
			return ['method' => 'oidc', 'provider' => mb_substr($parts[1] ?? '', 0, 50), 'twoFactor' => false];
		}
		return ['method' => 'internal', 'provider' => null, 'twoFactor' => $parts[0] !== 'internal' && $parts[0] !== ''];
	}

	// A streak only counts while the last visit was today or yesterday (server date)
	private function _achCurrentStreak($stats)
	{
		$lastDay = $stats['last_day'] ?? null;
		if (!$lastDay) {
			return 0;
		}
		$age = (strtotime(date('Y-m-d')) - strtotime($lastDay)) / 86400;
		return $age <= 1 ? (int)($stats['streak'] ?? 0) : 0;
	}

	private function _achIsoDate($value)
	{
		$value = $this->_achDateString($value);
		return $value ? str_replace(' ', 'T', $value) . 'Z' : null;
	}

	/**
	 * Most opened tabs, by name. Tabs that were deleted since are left out.
	 */
	private function _achTopTabs($counts, $limit = 5)
	{
		arsort($counts);
		$ids = array_slice(array_keys($counts), 0, 25, true);
		if (!$ids) {
			return [];
		}
		$tabs = $this->processQueries([[
			'function' => 'fetchAll',
			'query' => ['SELECT `id`, `name`, `image` FROM `tabs` WHERE `id` IN %in', array_values(array_map('intval', $ids))]
		]]) ?: [];
		$byId = [];
		foreach ($tabs as $tab) {
			$byId[(int)$tab['id']] = $tab;
		}
		$top = [];
		foreach ($ids as $id) {
			if (isset($byId[(int)$id])) {
				$top[] = ['id' => (int)$id, 'name' => $byId[(int)$id]['name'], 'image' => $byId[(int)$id]['image'], 'count' => (int)$counts[$id]];
			}
			if (count($top) >= $limit) {
				break;
			}
		}
		return $top;
	}

	/**
	 * Users with the most badges (only badges that are currently switched on count).
	 */
	public function _achLeaderboard($limit = null)
	{
		$this->_achEnsureTable();
		$rows = $this->processQueries([[
			'function' => 'fetchAll',
			'query' => 'SELECT a.`user_id`, a.`stats`, a.`unlocked`, a.`updated`, u.`username`, u.`image`, u.`group` FROM `ACHIEVEMENTS-users` a INNER JOIN `users` u ON u.`id` = a.`user_id`'
		]]) ?: [];
		$keys = array_column($this->_achDefinitions(), 'key');
		$board = [];
		foreach ($rows as $row) {
			$unlocked = json_decode((string)$row['unlocked'], true) ?: [];
			$stats = json_decode((string)$row['stats'], true) ?: [];
			$board[] = [
				'username' => $row['username'],
				'image' => $row['image'],
				'group' => $row['group'],
				'badges' => count(array_intersect(array_keys($unlocked), $keys)),
				'xp' => (int)($stats['xp'] ?? 0),
				'level' => $this->_achLevel((int)($stats['xp'] ?? 0)),
				'days' => (int)($stats['days'] ?? 0),
				'tabs' => (int)($stats['tabs'] ?? 0),
				'lastVisit' => !empty($stats['last_visit']) ? $this->_achIsoDate($stats['last_visit']) : null,
			];
		}
		$byXp = $this->_achXpEnabled();
		usort($board, function ($a, $b) use ($byXp) {
			return $byXp ? [$b['xp'], $b['badges']] <=> [$a['xp'], $a['badges']] : [$b['badges'], $b['days']] <=> [$a['badges'], $a['days']];
		});
		return $limit ? array_slice($board, 0, $limit) : $board;
	}

	/* ===================== admin ===================== */

	public function _achResetAll()
	{
		$this->_achEnsureTable();
		$this->processQueries([['function' => 'query', 'query' => 'DELETE FROM `ACHIEVEMENTS-users`']]);
		$this->audit('plugin.reset', 'Achievements', ['what' => 'all badges and statistics']);
		$this->setAPIResponse('success', 'All achievements and statistics were reset', 200);
		return true;
	}

	private function _achOverviewHtml()
	{
		if (!$this->hasDB()) {
			return '';
		}
		$board = $this->_achLeaderboard();
		$total = count($this->_achDefinitions());
		if (!$board) {
			$rows = '<tr><td colspan="6" lang="en">Nobody has collected anything yet.</td></tr>';
		} else {
			$rows = '';
			foreach ($board as $entry) {
				$rows .= '<tr><td>' . htmlspecialchars((string)$entry['username'], ENT_QUOTES) . '</td><td>' . htmlspecialchars((string)$entry['group'], ENT_QUOTES) . '</td><td>' . $entry['level'] . ' (' . number_format($entry['xp']) . ' XP)</td><td>' . $entry['badges'] . ' / ' . $total . '</td><td>' . $entry['days'] . '</td><td>' . $entry['tabs'] . '</td></tr>';
			}
		}
		return '<div class="table-responsive"><table class="table table-sm"><thead><tr><th lang="en">User</th><th lang="en">Group</th><th lang="en">Level</th><th lang="en">Badges</th><th lang="en">Active days</th><th lang="en">Tabs opened</th></tr></thead><tbody>' . $rows . '</tbody></table></div>';
	}

	public function _achGetSettings()
	{
		$groupOptions = [];
		foreach ($this->groupSelect() as $group) {
			if ((int)$group['value'] < 999) {
				$groupOptions[] = ['name' => $group['name'], 'value' => (string)$group['value']];
			}
		}
		$badgeOptions = array_map(function ($definition) {
			return ['name' => $definition['icon'] . ' ' . $definition['name'] . ' (' . $definition['category'] . ')', 'value' => $definition['key']];
		}, $this->_achDefinitions(true));
		return array(
			'custom' => '
				<div class="row">
					<div class="col-xl-12">
						<div class="card card-info">
							<div class="card-header"><span lang="en">How it works</span></div>
							<div class="card-wrapper collapse show" aria-expanded="true">
								<div class="card-body">
									<ul class="list-icons">
										<li><i class="fa fa-chevron-right text-info"></i> <span lang="en">Logged in users in the chosen groups collect badges by using Londerland: visiting on several days, opening tabs, special days and a few secrets.</span></li>
										<li><i class="fa fa-chevron-right text-info"></i> <span lang="en">They find their badges and statistics under My Stats in the user menu (top right).</span></li>
										<li><i class="fa fa-chevron-right text-info"></i> <span lang="en">Guests have no account, so they never collect badges.</span></li>
										<li><i class="fa fa-chevron-right text-info"></i> <span lang="en">With XP, levels and daily quests on, badges, daily visits (more during a streak) and three daily quests give XP; levels go from Guest Account and Intern up to Root and Uptime Legend.</span></li>
										<li><i class="fa fa-chevron-right text-info"></i> <span lang="en">Prompt Engineer and Token Burner only appear while AI Chat is enabled, Bookworm only while Bookmarks is enabled.</span></li>
									</ul>
								</div>
							</div>
						</div>
					</div>
				</div>',
			'Options' => array(
				array(
					'type' => 'select2',
					'name' => 'ACHIEVEMENTS-groups-include',
					'class' => 'select2-multiple',
					'id' => 'ACHIEVEMENTS-groups-include-select',
					'label' => 'Groups that collect badges',
					'value' => implode(',', $this->_achAllowedGroups()),
					'options' => $groupOptions,
					'help' => 'Users in these groups collect badges and see My Stats in their user menu. Guests never do.'
				),
				array(
					'type' => 'switch',
					'name' => 'ACHIEVEMENTS-xp-include',
					'label' => 'XP, levels and daily quests',
					'help' => 'Users earn XP for badges, for every day they visit (more during a streak) and for three daily quests that change every day, and climb levels with titles. My Stats shows their level and today\'s quests.',
					'value' => $this->_achXpEnabled()
				),
				array(
					'type' => 'switch',
					'name' => 'ACHIEVEMENTS-notify-include',
					'label' => 'Show a message when a badge is unlocked',
					'value' => $this->config['ACHIEVEMENTS-notify-include']
				),
				array(
					'type' => 'switch',
					'name' => 'ACHIEVEMENTS-leaderboard-include',
					'label' => 'Show a leaderboard on My Stats',
					'help' => 'Shows the 10 users with the most XP (or the most badges while XP is off), with their name, picture, level and number of badges, to everyone who collects badges.',
					'value' => $this->config['ACHIEVEMENTS-leaderboard-include']
				),
				array(
					'type' => 'select2',
					'name' => 'ACHIEVEMENTS-disabled',
					'class' => 'select2-multiple',
					'id' => 'ACHIEVEMENTS-disabled-select',
					'label' => 'Switched off badges',
					'value' => implode(',', $this->_achSplitList($this->config['ACHIEVEMENTS-disabled'] ?? '')),
					'options' => $badgeOptions,
					'help' => 'These badges are not shown and cannot be unlocked.'
				),
			),
			'Users' => array(
				array(
					'type' => 'html',
					'override' => 12,
					'label' => 'Badges per user',
					'html' => $this->_achOverviewHtml()
				),
				array(
					'type' => 'button',
					'label' => 'Reset everything',
					'class' => ' achievementsResetAll',
					'icon' => 'fa fa-trash',
					'text' => 'Reset all badges and statistics'
				),
			)
		);
	}
}
