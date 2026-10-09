<?php
// PLUGIN INFORMATION
$GLOBALS['plugins']['AI Chat'] = array( // Plugin Name
	'name' => 'AI Chat', // Plugin Name
	'author' => 'Londerland', // Who wrote the plugin
	'category' => 'Utilities', // One to Two Word Description
	'link' => '', // Link to plugin info
	'license' => 'personal,business', // License Type use , for multiple
	'idPrefix' => 'AICHAT', // html element id prefix
	'configPrefix' => 'AICHAT', // config file prefix for array items without the hyphen
	'dbPrefix' => 'AICHAT', // db prefix
	'version' => '1.0.0', // SemVer of plugin
	'image' => 'api/plugins/aichat/logo.png', // 1:1 non transparent image for plugin
	'settings' => true, // does plugin need a settings modal?
	'bind' => true, // use default bind to make settings page - true or false
	'api' => 'api/v2/plugins/aichat/settings', // api route for settings page
	'homepage' => false // Is plugin for use on homepage? true or false
);

/**
 * Chat with any OpenAI-compatible (/v1/chat/completions) server for logged in users.
 * The browser only talks to these routes; the API key never leaves the server.
 */
class AiChat extends Londerland
{
	// Text-like files are sent to the model as text, images as image parts, PDFs as extracted text
	private const TEXT_EXTENSIONS = ['txt', 'md', 'markdown', 'csv', 'tsv', 'json', 'xml', 'yaml', 'yml', 'ini', 'conf', 'cfg', 'toml', 'log', 'html', 'htm', 'css', 'js', 'mjs', 'ts', 'tsx', 'jsx', 'php', 'py', 'rb', 'go', 'rs', 'java', 'kt', 'c', 'h', 'cpp', 'hpp', 'cs', 'swift', 'sh', 'bash', 'zsh', 'ps1', 'bat', 'sql', 'env', 'dockerfile', 'vue', 'svelte', 'lua', 'pl', 'r', 'scala', 'dart', 'tex'];
	private const IMAGE_TYPES = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/gif' => 'gif', 'image/webp' => 'webp'];
	// Cap on extracted text per file so one big upload cannot blow up the request
	private const MAX_FILE_TEXT = 200000;
	// fetch_url: largest download and how much page text the model gets per call
	private const MAX_FETCH_BYTES = 5242880;
	private const MAX_PAGE_TEXT = 20000;
	private const NEW_CHAT_TITLE = 'New chat';

	/* ===================== access & setup ===================== */

	/**
	 * Logged in user that may use the chat (never a guest or a bare API key), with the tables in place.
	 */
	public function _aiChatAccess($request)
	{
		if (!$this->checkRoute($request)) {
			return false;
		}
		if (!$this->config['AICHAT-enabled'] || !$this->hasDB()) {
			$this->setAPIResponse('error', 'AI Chat is not enabled', 404);
			return false;
		}
		$userId = $this->user['userID'] ?? null;
		$groupId = (int)($this->user['groupID'] ?? 999);
		if (!$userId || empty($this->user['loggedin']) || $groupId >= 999) {
			$this->setAPIResponse('error', 'Please log in to use AI Chat', 401);
			return false;
		}
		if (!$this->_aiChatGroupAllowed($groupId)) {
			$this->setAPIResponse('error', 'Your group may not use AI Chat', 401);
			return false;
		}
		if ($this->config['AICHAT-baseUrl'] == '') {
			$this->setAPIResponse('error', 'AI Chat has no server configured yet', 409);
			return false;
		}
		$this->_aiChatEnsureTables();
		return true;
	}

	/**
	 * Groups chosen under the former "Groups with AI Chat" list, or the Minimum Authentication group and higher before
	 * that was set. Only a fallback now: a group's own switch under Groups (AICHAT-groupAccess-<id>-include) wins once
	 * it has been saved.
	 */
	private function _aiChatLegacyGroups()
	{
		$setting = trim((string)($this->config['AICHAT-groups-include'] ?? 'auto'));
		$groups = array_map(function ($group) {
			return (int)$group['value'];
		}, $this->groupSelect());
		if ($setting === 'auto') {
			$minimum = (int)$this->config['AICHAT-Auth-include'];
			$allowed = array_filter($groups, function ($group) use ($minimum) {
				return $group <= $minimum;
			});
		} else {
			$allowed = array_map('intval', $this->_aiChatSplitList($setting));
		}
		return array_values(array_filter($allowed, function ($group) {
			return $group < 999;
		}));
	}

	/**
	 * Whether a group gets the chat: its switch under Groups, or the older group list while that switch was never
	 * saved. Guests (999) never get the chat, whatever the settings say.
	 */
	private function _aiChatGroupAllowed($groupId)
	{
		$groupId = (int)$groupId;
		if ($groupId >= 999) {
			return false;
		}
		$key = 'AICHAT-groupAccess-' . $groupId . '-include';
		if (array_key_exists($key, $this->config) && $this->config[$key] !== '') {
			return filter_var($this->config[$key], FILTER_VALIDATE_BOOLEAN);
		}
		return in_array($groupId, $this->_aiChatLegacyGroups(), true);
	}

	public function _aiChatAdminAccess($request)
	{
		return $this->checkRoute($request) && $this->qualifyRequest(1, true);
	}

	private function _aiChatUserId()
	{
		return (int)$this->user['userID'];
	}

	private function _aiChatTableExists($table)
	{
		if ($this->config['driver'] == 'sqlite3') {
			$query = ["SELECT `name` FROM `sqlite_master` WHERE `type` = 'table' AND `name` = %s", $table];
		} else {
			$query = ['SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s', (string)$this->config['dbName'], $table];
		}
		return (bool)$this->processQueries([['function' => 'fetchSingle', 'query' => $query]]);
	}

	private function _aiChatEnsureTables()
	{
		$tables = [
			'AICHAT-chats' => 'CREATE TABLE `AICHAT-chats` (
				`id`	INTEGER PRIMARY KEY AUTOINCREMENT UNIQUE,
				`user_id`	INTEGER,
				`title`	TEXT,
				`model`	TEXT,
				`pinned`	INTEGER DEFAULT 0,
				`created`	DATETIME,
				`updated`	DATETIME
			);',
			'AICHAT-messages' => 'CREATE TABLE `AICHAT-messages` (
				`id`	INTEGER PRIMARY KEY AUTOINCREMENT UNIQUE,
				`chat_id`	INTEGER,
				`role`	TEXT,
				`content`	LONGTEXT,
				`reasoning`	LONGTEXT,
				`attachments`	TEXT,
				`meta`	LONGTEXT,
				`model`	TEXT,
				`created`	DATETIME
			);',
			'AICHAT-files' => 'CREATE TABLE `AICHAT-files` (
				`id`	INTEGER PRIMARY KEY AUTOINCREMENT UNIQUE,
				`user_id`	INTEGER,
				`name`	TEXT,
				`mime`	TEXT,
				`size`	INTEGER,
				`path`	TEXT,
				`created`	DATETIME
			);',
			'AICHAT-prefs' => 'CREATE TABLE `AICHAT-prefs` (
				`user_id`	INTEGER PRIMARY KEY,
				`default_model`	TEXT,
				`system_prompt`	TEXT,
				`send_on_enter`	INTEGER DEFAULT 1,
				`web_tools`	INTEGER DEFAULT 1
			);'
		];
		foreach ($tables as $name => $create) {
			if (!$this->_aiChatTableExists($name)) {
				$this->processQueries([['function' => 'query', 'query' => $create]]);
			}
		}
		// Tables from the first version of the plugin have no meta column yet
		if (!$this->_aiChatColumnExists('AICHAT-messages', 'meta')) {
			$this->processQueries([['function' => 'query', 'query' => 'ALTER TABLE `AICHAT-messages` ADD `meta` LONGTEXT']]);
		}
		if (!$this->_aiChatColumnExists('AICHAT-prefs', 'web_tools')) {
			$this->processQueries([['function' => 'query', 'query' => 'ALTER TABLE `AICHAT-prefs` ADD `web_tools` INTEGER DEFAULT 1']]);
		}
	}

	private function _aiChatColumnExists($table, $column)
	{
		if ($this->config['driver'] == 'sqlite3') {
			$columns = $this->processQueries([['function' => 'fetchAll', 'query' => ['PRAGMA table_info(%n)', $table]]]) ?: [];
			foreach ($columns as $info) {
				if ($info['name'] === $column) {
					return true;
				}
			}
			return false;
		}
		return (bool)$this->processQueries([[
			'function' => 'fetchSingle',
			'query' => ['SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s', (string)$this->config['dbName'], $table, $column]
		]]);
	}

	private function _aiChatNow()
	{
		return gmdate('Y-m-d H:i:s');
	}

	// Stored as UTC; the browser gets ISO 8601 so it can show local time
	private function _aiChatIsoDate($value)
	{
		if (is_object($value) && method_exists($value, 'format')) {
			$value = $value->format('Y-m-d H:i:s');
		}
		return $value ? str_replace(' ', 'T', (string)$value) . 'Z' : null;
	}

	/* ===================== settings ===================== */

	public function _aiChatGetSettings()
	{
		// per group: AI Chat on or off (off = no chat button and no access) and the models it may use; guests are
		// never offered
		$groupSettings = array(array(
			'type' => 'html',
			'override' => 12,
			'label' => 'Groups',
			'html' => '<p lang="en">Turn AI Chat on or off per group. Users in a group that is off get no chat button and cannot use the chat. Guests never get it.</p><p lang="en">Models: comma separated model IDs the group may use; <code>*</code> is a wildcard (e.g. <code>gpt-4o*, llama3*</code>). Empty = every model allowed under Models.</p>'
		));
		foreach ($this->groupSelect() as $group) {
			$groupId = (int)$group['value'];
			if ($groupId >= 999) {
				continue;
			}
			$groupSettings[] = array(
				'type' => 'switch',
				'name' => 'AICHAT-groupAccess-' . $groupId . '-include',
				'label' => $group['name'] . ': AI Chat',
				'value' => $this->_aiChatGroupAllowed($groupId)
			);
			$groupSettings[] = array(
				'type' => 'input',
				'name' => 'AICHAT-groupModels-' . $groupId,
				'label' => $group['name'] . ': Models',
				'value' => $this->config['AICHAT-groupModels-' . $groupId] ?? '',
				'placeholder' => 'Empty = all allowed models'
			);
		}
		return array(
			'custom' => '
				<div class="row">
					<div class="col-xl-12">
						<div class="card card-info">
							<div class="card-header"><span lang="en">How it works</span></div>
							<div class="card-wrapper collapse show" aria-expanded="true">
								<div class="card-body">
									<ul class="list-icons">
										<li><i class="fa fa-chevron-right text-info"></i> <span lang="en">Works with any server that speaks the OpenAI API (/v1/chat/completions), for example OpenAI, Anthropic (https://api.anthropic.com/v1/), Ollama (http://ollama:11434/v1), LM Studio, LiteLLM, OpenRouter, vLLM or LocalAI.</span></li>
										<li><i class="fa fa-chevron-right text-info"></i> <span lang="en">Logged in users in the groups turned on under Groups get a chat button in the bottom right corner. Guests never see it.</span></li>
										<li><i class="fa fa-chevron-right text-info"></i> <span lang="en">The API key stays on the Londerland server; browsers never receive it.</span></li>
										<li><i class="fa fa-chevron-right text-info"></i> <span lang="en">Save the settings, then use Test Connection to check the server and load its models.</span></li>
										<li><i class="fa fa-chevron-right text-info"></i> <span lang="en">Optional: set up Web Search and Images to give users a globe button (search the web first) and an image button (create a picture).</span></li>
									</ul>
								</div>
							</div>
						</div>
					</div>
				</div>',
			'Connection' => array(
				array(
					'type' => 'input',
					'name' => 'AICHAT-baseUrl',
					'label' => 'API Base URL',
					'value' => $this->config['AICHAT-baseUrl'],
					'placeholder' => 'https://api.openai.com/v1',
					'help' => 'Address up to and including /v1. Londerland adds /models and /chat/completions to it.'
				),
				array(
					'type' => 'password-alt',
					'name' => 'AICHAT-apiKey',
					'label' => 'API Key',
					'value' => $this->config['AICHAT-apiKey'],
					'help' => 'Sent as "Authorization: Bearer <key>". Leave empty for servers without a key (for example a local Ollama).'
				),
				array(
					'type' => 'number',
					'name' => 'AICHAT-requestTimeout',
					'label' => 'Request Timeout (seconds)',
					'value' => $this->config['AICHAT-requestTimeout'],
					'placeholder' => '300'
				),
				array(
					'type' => 'switch',
					'name' => 'AICHAT-verifySSL',
					'label' => 'Verify SSL Certificate',
					'help' => 'Turn off only for servers with a self-signed certificate.',
					'value' => $this->config['AICHAT-verifySSL']
				),
				array(
					'type' => 'button',
					'label' => 'Test Connection',
					'class' => ' aichatTestConnection',
					'icon' => 'fa fa-flask',
					'text' => 'Test (save first)'
				),
			),
			'Models' => array(
				array(
					'type' => 'input',
					'name' => 'AICHAT-defaultModel',
					'label' => 'Default Model',
					'value' => $this->config['AICHAT-defaultModel'],
					'placeholder' => 'gpt-4o-mini',
					'help' => 'Used for new chats when a user has not picked their own default.'
				),
				array(
					'type' => 'input',
					'name' => 'AICHAT-allowedModels',
					'label' => 'Allowed Models',
					'value' => $this->config['AICHAT-allowedModels'],
					'placeholder' => 'Empty = every model the server lists',
					'help' => 'Comma separated model IDs users may pick. A * works as wildcard, for example "gpt-4o*, claude-*".'
				),
				array(
					'type' => 'input',
					'name' => 'AICHAT-extraModels',
					'label' => 'Extra Models',
					'value' => $this->config['AICHAT-extraModels'],
					'placeholder' => 'model-a, model-b',
					'help' => 'Comma separated model IDs to offer even if the server does not list them under /models.'
				),
			),
			'Chat' => array(
				array(
					'type' => 'textbox',
					'name' => 'AICHAT-systemPrompt',
					'label' => 'System Prompt',
					'value' => $this->config['AICHAT-systemPrompt'],
					'placeholder' => 'You are a helpful assistant.',
					'attr' => 'rows="4"'
				),
				array(
					'type' => 'input',
					'name' => 'AICHAT-temperature',
					'label' => 'Temperature',
					'value' => $this->config['AICHAT-temperature'],
					'placeholder' => 'Empty = server default (0 to 2)'
				),
				array(
					'type' => 'number',
					'name' => 'AICHAT-maxTokens',
					'label' => 'Max Answer Tokens',
					'value' => $this->config['AICHAT-maxTokens'],
					'placeholder' => 'Empty = server default'
				),
				array(
					'type' => 'number',
					'name' => 'AICHAT-contextMessages',
					'label' => 'Messages Sent as Context',
					'value' => $this->config['AICHAT-contextMessages'],
					'placeholder' => '40',
					'help' => 'How many earlier messages of a chat are sent along with each question.'
				),
				array(
					'type' => 'switch',
					'name' => 'AICHAT-autoTitle',
					'label' => 'Name Chats Automatically',
					'help' => 'Asks the model for a short title after the first answer.',
					'value' => $this->config['AICHAT-autoTitle']
				),
			),
			'Groups' => $groupSettings,
			'Web Search' => array(
				array(
					'type' => 'select',
					'name' => 'AICHAT-searchProvider-include',
					'label' => 'Search Provider',
					'value' => $this->config['AICHAT-searchProvider-include'],
					'options' => [
						['name' => 'Off', 'value' => 'none'],
						['name' => 'SearXNG (self-hosted, no key)', 'value' => 'searxng'],
						['name' => 'Brave Search API (key)', 'value' => 'brave'],
						['name' => 'Tavily (key)', 'value' => 'tavily'],
						['name' => 'DuckDuckGo (no key, best effort)', 'value' => 'duckduckgo'],
					],
					'help' => 'Users get a globe button to search the web before the answer.'
				),
				array(
					'type' => 'input',
					'name' => 'AICHAT-searchUrl',
					'label' => 'SearXNG URL',
					'value' => $this->config['AICHAT-searchUrl'],
					'placeholder' => 'http://searxng:8080',
					'help' => 'Only for SearXNG. Its settings.yml must allow the json format (search: formats: [html, json]).'
				),
				array(
					'type' => 'password-alt',
					'name' => 'AICHAT-searchApiKey',
					'label' => 'Search API Key',
					'value' => $this->config['AICHAT-searchApiKey'],
					'help' => 'For Brave or Tavily.'
				),
				array(
					'type' => 'number',
					'name' => 'AICHAT-searchResults',
					'label' => 'Results per Search',
					'value' => $this->config['AICHAT-searchResults'],
					'placeholder' => '5'
				),
				array(
					'type' => 'switch',
					'name' => 'AICHAT-searchAuto',
					'label' => 'Model May Use the Web by Itself',
					'help' => 'Offers models with tool calling a fetch_url tool to open and read web pages (a link the user pasted or a search result) and, when a Search Provider is chosen above, a web_search tool. Users get a Web button in the chat box to turn this off and on for themselves.',
					'value' => $this->config['AICHAT-searchAuto']
				),
				array(
					'type' => 'switch',
					'name' => 'AICHAT-fetchAllowPrivate',
					'label' => 'Allow Reading Local Addresses',
					'help' => 'Off = pages on localhost and private networks (192.168.x.x, 10.x.x.x, Docker networks, ...) are refused, so nobody can make the chat read your internal services. Only turn on if every chat user may see those.',
					'value' => $this->config['AICHAT-fetchAllowPrivate']
				),
				array(
					'type' => 'button',
					'label' => 'Test Search',
					'class' => ' aichatTestSearch',
					'icon' => 'fa fa-search',
					'text' => 'Test (save first)'
				),
			),
			'Images' => array(
				array(
					'type' => 'switch',
					'name' => 'AICHAT-images-include',
					'label' => 'Allow Image Generation',
					'help' => 'Users get an image button that turns their message into a picture.',
					'value' => $this->config['AICHAT-images-include']
				),
				array(
					'type' => 'input',
					'name' => 'AICHAT-imageBaseUrl',
					'label' => 'Image API Base URL',
					'value' => $this->config['AICHAT-imageBaseUrl'],
					'placeholder' => 'Empty = same as the chat server',
					'help' => 'An OpenAI-compatible server with /images/generations, for example https://api.openai.com/v1 or a LocalAI server. Claude cannot create images, so use another server here when chatting with Claude.'
				),
				array(
					'type' => 'password-alt',
					'name' => 'AICHAT-imageApiKey',
					'label' => 'Image API Key',
					'value' => $this->config['AICHAT-imageApiKey'],
					'help' => 'Empty = same key as the chat server.'
				),
				array(
					'type' => 'input',
					'name' => 'AICHAT-imageModel',
					'label' => 'Image Model',
					'value' => $this->config['AICHAT-imageModel'],
					'placeholder' => 'gpt-image-1'
				),
				array(
					'type' => 'input',
					'name' => 'AICHAT-imageSize',
					'label' => 'Image Size',
					'value' => $this->config['AICHAT-imageSize'],
					'placeholder' => '1024x1024'
				),
				array(
					'type' => 'switch',
					'name' => 'AICHAT-imageAuto',
					'label' => 'Model May Create Images by Itself',
					'help' => 'Offers a generate_image tool, so models with tool calling can make a picture when asked in normal chat.',
					'value' => $this->config['AICHAT-imageAuto']
				),
			),
			'Uploads' => array(
				array(
					'type' => 'switch',
					'name' => 'AICHAT-uploads-include',
					'label' => 'Allow Image and File Uploads',
					'help' => 'Images are sent to vision models; text, code and PDF files are sent as text.',
					'value' => $this->config['AICHAT-uploads-include']
				),
				array(
					'type' => 'number',
					'name' => 'AICHAT-maxUploadMB-include',
					'label' => 'Max Upload Size (MB)',
					'value' => $this->config['AICHAT-maxUploadMB-include'],
					'placeholder' => '20'
				),
			),
			'Chat Button' => array(
				array(
					'type' => 'input',
					'name' => 'AICHAT-launcherLabel-include',
					'label' => 'Button Name',
					'value' => $this->config['AICHAT-launcherLabel-include'],
					'placeholder' => 'Empty = only "AI"',
					'help' => 'Short name shown under "AI" on the chat button (up to about 10 characters fit). Leave empty to show only "AI".'
				),
				array(
					'type' => 'input',
					'name' => 'AICHAT-launcherColor-include',
					'label' => 'Button Color',
					'value' => $this->config['AICHAT-launcherColor-include'],
					'class' => 'aichat-color-picker',
					'placeholder' => '#2cabe3',
					'help' => 'Any colour code, e.g. #b39ddb (light purple) or #5e35b1 (dark purple). Empty = default blue. The text turns dark on light colours.'
				),
			)
		);
	}

	/* ===================== upstream API ===================== */

	private function _aiChatUrl($path)
	{
		return rtrim(trim($this->config['AICHAT-baseUrl']), '/') . '/' . ltrim($path, '/');
	}

	private function _aiChatHeaders()
	{
		$headers = ['Content-Type: application/json', 'Accept: application/json'];
		if (trim($this->config['AICHAT-apiKey']) !== '') {
			$headers[] = 'Authorization: Bearer ' . trim($this->config['AICHAT-apiKey']);
		}
		return $headers;
	}

	private function _aiChatCurl($url, $timeout)
	{
		$curl = curl_init($url);
		curl_setopt_array($curl, [
			CURLOPT_HTTPHEADER => $this->_aiChatHeaders(),
			CURLOPT_CONNECTTIMEOUT => 15,
			CURLOPT_TIMEOUT => (int)$timeout,
			CURLOPT_SSL_VERIFYPEER => (bool)$this->config['AICHAT-verifySSL'],
			CURLOPT_SSL_VERIFYHOST => $this->config['AICHAT-verifySSL'] ? 2 : 0,
			CURLOPT_FOLLOWLOCATION => true,
		]);
		return $curl;
	}

	/**
	 * Plain JSON request to the upstream server: [httpCode, decodedBody, errorText]
	 */
	private function _aiChatRequest($method, $path, $body = null, $timeout = 30)
	{
		$curl = $this->_aiChatCurl($this->_aiChatUrl($path), $timeout);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		if ($method === 'POST') {
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body));
		}
		$raw = curl_exec($curl);
		$error = curl_error($curl);
		$code = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
		if ($raw === false) {
			return [0, null, $error ?: 'Could not reach the AI server'];
		}
		$decoded = json_decode($raw, true);
		if ($code < 200 || $code >= 300) {
			return [$code, $decoded, $this->_aiChatUpstreamError($decoded, $raw, $code)];
		}
		return [$code, $decoded, null];
	}

	private function _aiChatUpstreamError($decoded, $raw, $code)
	{
		$message = $decoded['error']['message'] ?? ($decoded['error'] ?? ($decoded['message'] ?? null));
		if (!is_string($message) || $message === '') {
			$message = trim(strip_tags((string)$raw)) ?: 'No details';
		}
		return 'AI server answered ' . $code . ': ' . mb_substr($message, 0, 500);
	}

	private function _aiChatSplitList($value)
	{
		return array_values(array_filter(array_map('trim', explode(',', (string)$value)), 'strlen'));
	}

	private function _aiChatModelAllowed($model, $allowed)
	{
		if (!$allowed) {
			return true;
		}
		foreach ($allowed as $pattern) {
			if (fnmatch($pattern, $model, FNM_CASEFOLD)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Models users may pick: what the server lists plus the extra models, filtered by the allow list
	 */
	public function _aiChatModels($reportErrors = true, $forCurrentUser = true)
	{
		[$code, $body, $error] = $this->_aiChatRequest('GET', 'models', null, 20);
		$models = [];
		foreach ($body['data'] ?? ($body['models'] ?? []) as $model) {
			$id = is_array($model) ? ($model['id'] ?? ($model['name'] ?? null)) : $model;
			if (is_string($id) && $id !== '') {
				$models[] = $id;
			}
		}
		$models = array_merge($models, $this->_aiChatSplitList($this->config['AICHAT-extraModels']));
		$defaultModel = trim($this->config['AICHAT-defaultModel']);
		if ($defaultModel !== '') {
			$models[] = $defaultModel;
		}
		$allowed = $this->_aiChatSplitList($this->config['AICHAT-allowedModels']);
		// a group's own list narrows the overall allow list further
		$groupAllowed = $forCurrentUser ? $this->_aiChatSplitList($this->config['AICHAT-groupModels-' . (int)($this->user['groupID'] ?? 999)] ?? '') : [];
		$models = array_values(array_unique(array_filter($models, function ($model) use ($allowed, $groupAllowed) {
			return $this->_aiChatModelAllowed($model, $allowed) && $this->_aiChatModelAllowed($model, $groupAllowed);
		})));
		natcasesort($models);
		$models = array_values($models);
		if ($error && !$models && $reportErrors) {
			$this->setAPIResponse('error', $error, 502);
			return false;
		}
		return ['models' => $models, 'warning' => $error];
	}

	private function _aiChatPickModel($requested)
	{
		$list = $this->_aiChatModels(false)['models'] ?? [];
		if ($requested && in_array($requested, $list, true)) {
			return $requested;
		}
		$prefs = $this->_aiChatGetPrefs();
		foreach ([$prefs['default_model'] ?? null, trim($this->config['AICHAT-defaultModel'])] as $candidate) {
			if ($candidate && in_array($candidate, $list, true)) {
				return $candidate;
			}
		}
		return $list[0] ?? null;
	}

	public function _aiChatTestConnection()
	{
		$result = $this->_aiChatModels(true, false);
		if ($result === false) {
			return false;
		}
		$count = count($result['models']);
		if ($result['warning']) {
			// Usually a wrong URL or key: say so, even though the configured models still work as fallback
			$this->setAPIResponse('error', $result['warning'] . ' (users can still pick the ' . $count . ' configured model(s), but answers will probably fail)', 502, $result['models']);
			return false;
		}
		$this->setAPIResponse('success', 'Connected. Models available to users: ' . $count, 200, $result['models']);
		return $result['models'];
	}

	/* ===================== preferences ===================== */

	public function _aiChatGetPrefs()
	{
		$prefs = $this->processQueries([[
			'function' => 'fetch',
			'query' => ['SELECT * FROM `AICHAT-prefs` WHERE `user_id` = ?', $this->_aiChatUserId()]
		]]);
		return [
			'default_model' => $prefs['default_model'] ?? null,
			'system_prompt' => $prefs['system_prompt'] ?? '',
			'send_on_enter' => isset($prefs['send_on_enter']) ? (bool)$prefs['send_on_enter'] : true,
			// The web button in the chat box: may the model search and read pages by itself
			'web_tools' => isset($prefs['web_tools']) ? (bool)$prefs['web_tools'] : true,
		];
	}

	public function _aiChatSavePrefs($data)
	{
		$prefs = $this->_aiChatGetPrefs();
		if (array_key_exists('default_model', $data)) {
			$prefs['default_model'] = $data['default_model'] ? mb_substr((string)$data['default_model'], 0, 200) : null;
		}
		if (array_key_exists('system_prompt', $data)) {
			$prefs['system_prompt'] = mb_substr((string)$data['system_prompt'], 0, 8000);
		}
		if (array_key_exists('send_on_enter', $data)) {
			$prefs['send_on_enter'] = filter_var($data['send_on_enter'], FILTER_VALIDATE_BOOLEAN);
		}
		if (array_key_exists('web_tools', $data)) {
			$prefs['web_tools'] = filter_var($data['web_tools'], FILTER_VALIDATE_BOOLEAN);
		}
		$row = [
			'default_model' => $prefs['default_model'],
			'system_prompt' => $prefs['system_prompt'],
			'send_on_enter' => $prefs['send_on_enter'] ? 1 : 0,
			'web_tools' => $prefs['web_tools'] ? 1 : 0,
		];
		$this->processQueries([
			['function' => 'query', 'query' => ['DELETE FROM `AICHAT-prefs` WHERE `user_id` = ?', $this->_aiChatUserId()]],
			['function' => 'query', 'query' => ['INSERT INTO [AICHAT-prefs]', array_merge(['user_id' => $this->_aiChatUserId()], $row)]],
		]);
		$this->setAPIResponse('success', 'Preferences saved', 200, $prefs);
		return $prefs;
	}

	/* ===================== chats ===================== */

	private function _aiChatFormatChat($chat)
	{
		return [
			'id' => (int)$chat['id'],
			'title' => $chat['title'],
			'model' => $chat['model'],
			'pinned' => (bool)$chat['pinned'],
			'created' => $this->_aiChatIsoDate($chat['created']),
			'updated' => $this->_aiChatIsoDate($chat['updated']),
		];
	}

	private function _aiChatFindChat($chatId)
	{
		$chat = $this->processQueries([[
			'function' => 'fetch',
			'query' => ['SELECT * FROM `AICHAT-chats` WHERE `id` = ? AND `user_id` = ?', (int)$chatId, $this->_aiChatUserId()]
		]]);
		if (!$chat) {
			$this->setAPIResponse('error', 'Chat not found', 404);
			return false;
		}
		return $chat;
	}

	public function _aiChatListChats($search = null)
	{
		$userId = $this->_aiChatUserId();
		$search = trim((string)$search);
		if ($search !== '') {
			$like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
			$query = ['SELECT * FROM `AICHAT-chats` WHERE `user_id` = ? AND (`title` LIKE ? OR `id` IN (SELECT `chat_id` FROM `AICHAT-messages` WHERE `content` LIKE ?)) ORDER BY `pinned` DESC, `updated` DESC', $userId, $like, $like];
		} else {
			$query = ['SELECT * FROM `AICHAT-chats` WHERE `user_id` = ? ORDER BY `pinned` DESC, `updated` DESC', $userId];
		}
		$chats = $this->processQueries([['function' => 'fetchAll', 'query' => $query]]) ?: [];
		return array_map([$this, '_aiChatFormatChat'], $chats);
	}

	private function _aiChatMessages($chatId)
	{
		return $this->processQueries([[
			'function' => 'fetchAll',
			'query' => ['SELECT * FROM `AICHAT-messages` WHERE `chat_id` = ? ORDER BY `id` ASC', (int)$chatId]
		]]) ?: [];
	}

	private function _aiChatFormatMessage($message)
	{
		return [
			'id' => (int)$message['id'],
			'role' => $message['role'],
			'content' => $message['content'],
			'reasoning' => $message['reasoning'] ?: null,
			'attachments' => json_decode($message['attachments'] ?: '[]', true) ?: [],
			'meta' => json_decode($message['meta'] ?? '', true) ?: new stdClass(),
			'model' => $message['model'],
			'created' => $this->_aiChatIsoDate($message['created']),
		];
	}

	public function _aiChatGetChat($chatId)
	{
		$chat = $this->_aiChatFindChat($chatId);
		if (!$chat) {
			return false;
		}
		$formatted = $this->_aiChatFormatChat($chat);
		$formatted['messages'] = array_map([$this, '_aiChatFormatMessage'], $this->_aiChatMessages($chatId));
		return $formatted;
	}

	public function _aiChatCreateChat($data)
	{
		$now = $this->_aiChatNow();
		$title = trim((string)($data['title'] ?? '')) ?: self::NEW_CHAT_TITLE;
		$this->processQueries([[
			'function' => 'query',
			'query' => ['INSERT INTO [AICHAT-chats]', [
				'user_id' => $this->_aiChatUserId(),
				'title' => mb_substr($title, 0, 200),
				'model' => $this->_aiChatPickModel($data['model'] ?? null),
				'pinned' => 0,
				'created' => $now,
				'updated' => $now,
			]]
		]]);
		$chat = $this->_aiChatGetChat($this->db->getInsertId());
		$this->setAPIResponse('success', 'Chat created', 200, $chat);
		return $chat;
	}

	public function _aiChatUpdateChat($chatId, $data)
	{
		$chat = $this->_aiChatFindChat($chatId);
		if (!$chat) {
			return false;
		}
		$update = [];
		if (isset($data['title']) && trim((string)$data['title']) !== '') {
			$update['title'] = mb_substr(trim((string)$data['title']), 0, 200);
		}
		if (isset($data['pinned'])) {
			$update['pinned'] = filter_var($data['pinned'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
		}
		if (isset($data['model']) && $data['model'] !== '') {
			$update['model'] = mb_substr((string)$data['model'], 0, 200);
		}
		if ($update) {
			$this->processQueries([[
				'function' => 'query',
				'query' => ['UPDATE [AICHAT-chats] SET', $update, 'WHERE `id` = ?', (int)$chatId]
			]]);
		}
		$formatted = $this->_aiChatFormatChat(array_merge($chat, $update));
		$this->setAPIResponse('success', 'Chat updated', 200, $formatted);
		return $formatted;
	}

	public function _aiChatDeleteChat($chatId)
	{
		if (!$this->_aiChatFindChat($chatId)) {
			return false;
		}
		$this->_aiChatDeleteChatRows([(int)$chatId]);
		$this->setAPIResponse('success', 'Chat deleted', 200);
		return true;
	}

	public function _aiChatDeleteAllChats()
	{
		$ids = array_map(function ($chat) {
			return $chat['id'];
		}, $this->_aiChatListChats());
		$this->_aiChatDeleteChatRows($ids);
		$this->_aiChatCleanupFiles(true);
		$this->setAPIResponse('success', 'All chats deleted', 200);
		return true;
	}

	// Deletes chats, their messages and the files only those messages used
	private function _aiChatDeleteChatRows($chatIds)
	{
		if (!$chatIds) {
			return;
		}
		$fileIds = [];
		foreach ($chatIds as $chatId) {
			foreach ($this->_aiChatMessages($chatId) as $message) {
				foreach (json_decode($message['attachments'] ?: '[]', true) ?: [] as $attachment) {
					$fileIds[] = (int)$attachment['id'];
				}
			}
		}
		$this->processQueries([
			['function' => 'query', 'query' => ['DELETE FROM `AICHAT-messages` WHERE `chat_id` IN %in', $chatIds]],
			['function' => 'query', 'query' => ['DELETE FROM `AICHAT-chats` WHERE `id` IN %in AND `user_id` = ?', $chatIds, $this->_aiChatUserId()]],
		]);
		foreach (array_unique($fileIds) as $fileId) {
			$this->_aiChatDeleteFile($fileId);
		}
		$this->_aiChatCleanupFiles();
	}

	/**
	 * Removes this user's uploads that no message uses anymore (after edits, regenerates or uploads that were
	 * never sent). Fresh uploads get an hour, since they may still be waiting in the message box.
	 */
	private function _aiChatCleanupFiles($all = false)
	{
		$userId = $this->_aiChatUserId();
		$files = $this->processQueries([[
			'function' => 'fetchAll',
			'query' => ['SELECT `id`, `created` FROM `AICHAT-files` WHERE `user_id` = ?', $userId]
		]]) ?: [];
		if (!$files) {
			return;
		}
		$used = [];
		if (!$all) {
			$rows = $this->processQueries([[
				'function' => 'fetchAll',
				'query' => ['SELECT m.`attachments` FROM `AICHAT-messages` m INNER JOIN `AICHAT-chats` c ON c.`id` = m.`chat_id` WHERE c.`user_id` = ? AND m.`attachments` != ?', $userId, '[]']
			]]) ?: [];
			foreach ($rows as $row) {
				foreach (json_decode($row['attachments'] ?: '[]', true) ?: [] as $attachment) {
					$used[(int)$attachment['id']] = true;
				}
			}
		}
		$cutoff = gmdate('Y-m-d H:i:s', time() - 3600);
		foreach ($files as $file) {
			$created = is_object($file['created']) ? $file['created']->format('Y-m-d H:i:s') : (string)$file['created'];
			if (!isset($used[(int)$file['id']]) && ($all || $created < $cutoff)) {
				$this->_aiChatDeleteFile($file['id']);
			}
		}
	}

	/* ===================== files ===================== */

	private function _aiChatFileDir()
	{
		$dir = $this->root . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'aichat';
		if (!is_dir($dir)) {
			mkdir($dir, 0750, true);
		}
		// Uploads are only served through the API (owner check); block direct web access on Apache
		if (!is_file($dir . DIRECTORY_SEPARATOR . '.htaccess')) {
			file_put_contents($dir . DIRECTORY_SEPARATOR . '.htaccess', "Require all denied\n");
		}
		return $dir;
	}

	private function _aiChatFileKind($name, $mime)
	{
		$extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
		if (isset(self::IMAGE_TYPES[$mime])) {
			return 'image';
		}
		if ($extension === 'pdf' || $mime === 'application/pdf') {
			return 'pdf';
		}
		if (in_array($extension, self::TEXT_EXTENSIONS, true) || strpos($mime, 'text/') === 0 || in_array($mime, ['application/json', 'application/xml', 'application/x-yaml'], true)) {
			return 'text';
		}
		return null;
	}

	public function _aiChatUpload($request)
	{
		if (!$this->config['AICHAT-uploads-include']) {
			$this->setAPIResponse('error', 'Uploads are turned off', 403);
			return false;
		}
		$files = $request->getUploadedFiles();
		$file = $files['file'] ?? null;
		if (!$file || $file->getError() !== UPLOAD_ERR_OK) {
			$this->setAPIResponse('error', 'Upload failed', 400);
			return false;
		}
		$maxBytes = max(1, (int)$this->config['AICHAT-maxUploadMB-include']) * 1024 * 1024;
		if ($file->getSize() > $maxBytes) {
			$this->setAPIResponse('error', 'File is larger than ' . (int)$this->config['AICHAT-maxUploadMB-include'] . ' MB', 413);
			return false;
		}
		// The name ends up in a download header, so no control characters or quotes
		$name = preg_replace('/[\x00-\x1F\x7F"]+/u', '', basename(str_replace('\\', '/', (string)$file->getClientFilename())));
		$name = mb_substr(trim($name), 0, 200) ?: 'file';
		$dir = $this->_aiChatFileDir();
		$stored = bin2hex(random_bytes(16));
		$path = $dir . DIRECTORY_SEPARATOR . $stored;
		$file->moveTo($path);
		// Trust the content, not the browser's claimed type
		$mime = (new finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
		if ($mime === 'text/plain' || $mime === 'application/octet-stream') {
			$claimed = $file->getClientMediaType();
			$mime = ($claimed && strpos($claimed, 'image/') !== 0) ? $claimed : $mime;
		}
		$kind = $this->_aiChatFileKind($name, $mime);
		if (!$kind) {
			unlink($path);
			$this->setAPIResponse('error', 'This file type is not supported. Use images, PDFs or text/code files.', 415);
			return false;
		}
		$this->processQueries([[
			'function' => 'query',
			'query' => ['INSERT INTO [AICHAT-files]', [
				'user_id' => $this->_aiChatUserId(),
				'name' => $name,
				'mime' => $mime,
				'size' => filesize($path),
				'path' => $stored,
				'created' => $this->_aiChatNow(),
			]]
		]]);
		$attachment = ['id' => (int)$this->db->getInsertId(), 'name' => $name, 'mime' => $mime, 'size' => filesize($path), 'kind' => $kind];
		$this->setAPIResponse('success', 'File uploaded', 200, $attachment);
		return $attachment;
	}

	public function _aiChatFindFile($fileId)
	{
		$file = $this->processQueries([[
			'function' => 'fetch',
			'query' => ['SELECT * FROM `AICHAT-files` WHERE `id` = ? AND `user_id` = ?', (int)$fileId, $this->_aiChatUserId()]
		]]);
		if (!$file || !preg_match('/^[a-f0-9]{32}$/', $file['path'])) {
			$this->setAPIResponse('error', 'File not found', 404);
			return false;
		}
		$file['fullPath'] = $this->_aiChatFileDir() . DIRECTORY_SEPARATOR . $file['path'];
		if (!is_file($file['fullPath'])) {
			$this->setAPIResponse('error', 'File not found', 404);
			return false;
		}
		return $file;
	}

	private function _aiChatDeleteFile($fileId)
	{
		$file = $this->processQueries([[
			'function' => 'fetch',
			'query' => ['SELECT * FROM `AICHAT-files` WHERE `id` = ? AND `user_id` = ?', (int)$fileId, $this->_aiChatUserId()]
		]]);
		if (!$file) {
			return;
		}
		$path = $this->_aiChatFileDir() . DIRECTORY_SEPARATOR . $file['path'];
		if (preg_match('/^[a-f0-9]{32}$/', $file['path']) && is_file($path)) {
			unlink($path);
		}
		$this->processQueries([['function' => 'query', 'query' => ['DELETE FROM `AICHAT-files` WHERE `id` = ?', (int)$fileId]]]);
	}

	private function _aiChatFileText($file)
	{
		$kind = $this->_aiChatFileKind($file['name'], $file['mime']);
		try {
			if ($kind === 'pdf') {
				$text = (new \Smalot\PdfParser\Parser())->parseFile($file['fullPath'])->getText();
			} else {
				$text = file_get_contents($file['fullPath']);
				if (!mb_check_encoding($text, 'UTF-8')) {
					$text = mb_convert_encoding($text, 'UTF-8', 'Windows-1252');
				}
			}
		} catch (\Throwable $e) {
			return '[Could not read ' . $file['name'] . ': ' . $e->getMessage() . ']';
		}
		if (mb_strlen($text) > self::MAX_FILE_TEXT) {
			$text = mb_substr($text, 0, self::MAX_FILE_TEXT) . "\n[... file shortened ...]";
		}
		return $text;
	}

	/* ===================== conversation ===================== */

	/**
	 * Builds the OpenAI "messages" array: system prompts, then the latest messages with their attachments
	 */
	private function _aiChatBuildMessages($chatId)
	{
		$messages = [];
		$system = trim($this->config['AICHAT-systemPrompt']);
		$personal = trim($this->_aiChatGetPrefs()['system_prompt'] ?? '');
		if ($personal !== '') {
			$system = trim($system . "\n\n" . 'Instructions from the user ' . $this->user['username'] . ":\n" . $personal);
		}
		if ($system !== '') {
			$messages[] = ['role' => 'system', 'content' => $system];
		}
		$history = $this->_aiChatMessages($chatId);
		$limit = max(1, (int)$this->config['AICHAT-contextMessages'] ?: 40);
		$history = array_slice($history, -$limit);
		foreach ($history as $message) {
			if (!in_array($message['role'], ['user', 'assistant'], true)) {
				continue;
			}
			$attachments = json_decode($message['attachments'] ?: '[]', true) ?: [];
			if ($message['role'] === 'user' && $attachments) {
				$parts = [];
				$text = (string)$message['content'];
				foreach ($attachments as $attachment) {
					$file = $this->_aiChatFindFile($attachment['id']);
					if (!$file) {
						$text .= "\n\n[Attachment " . $attachment['name'] . ' is no longer available]';
						continue;
					}
					if ($this->_aiChatFileKind($file['name'], $file['mime']) === 'image') {
						$parts[] = ['type' => 'image_url', 'image_url' => ['url' => 'data:' . $file['mime'] . ';base64,' . base64_encode(file_get_contents($file['fullPath']))]];
					} else {
						$text .= "\n\n<file name=\"" . $file['name'] . "\">\n" . $this->_aiChatFileText($file) . "\n</file>";
					}
				}
				array_unshift($parts, ['type' => 'text', 'text' => $text]);
				$messages[] = ['role' => 'user', 'content' => $parts];
			} else {
				$content = (string)$message['content'];
				if ($message['role'] === 'assistant') {
					foreach ($attachments as $attachment) {
						$content .= "\n\n[Image created for the user: " . ($attachment['prompt'] ?? $attachment['name']) . ']';
					}
				}
				$messages[] = ['role' => $message['role'], 'content' => trim($content)];
			}
		}
		return $messages;
	}

	private function _aiChatInsertMessage($chatId, $role, $content, $attachments = [], $model = null, $reasoning = null, $meta = null)
	{
		$this->processQueries([[
			'function' => 'query',
			'query' => ['INSERT INTO [AICHAT-messages]', [
				'chat_id' => (int)$chatId,
				'role' => $role,
				'content' => $content,
				'reasoning' => $reasoning,
				'attachments' => json_encode(array_values($attachments)),
				'meta' => $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
				'model' => $model,
				'created' => $this->_aiChatNow(),
			]]
		]]);
		$id = (int)$this->db->getInsertId();
		$this->processQueries([[
			'function' => 'query',
			'query' => ['UPDATE [AICHAT-chats] SET', ['updated' => $this->_aiChatNow()], 'WHERE `id` = ?', (int)$chatId]
		]]);
		$row = $this->processQueries([[
			'function' => 'fetch',
			'query' => ['SELECT * FROM `AICHAT-messages` WHERE `id` = ?', $id]
		]]);
		return $this->_aiChatFormatMessage($row);
	}

	/**
	 * Applies a send / edit / regenerate to the stored chat. Returns the new user message (or null for a regenerate).
	 */
	private function _aiChatPrepareTurn($chat, $data)
	{
		$chatId = (int)$chat['id'];
		$messages = $this->_aiChatMessages($chatId);
		if (!empty($data['regenerate'])) {
			// Drop the answer(s) after the last question and answer that question again
			$lastUser = null;
			foreach ($messages as $message) {
				if ($message['role'] === 'user') {
					$lastUser = (int)$message['id'];
				}
			}
			if ($lastUser === null) {
				throw new InvalidArgumentException('There is no question to answer again');
			}
			$this->processQueries([['function' => 'query', 'query' => ['DELETE FROM `AICHAT-messages` WHERE `chat_id` = ? AND `id` > ?', $chatId, $lastUser]]]);
			return null;
		}
		$content = trim((string)($data['content'] ?? ''));
		$attachments = [];
		foreach ((array)($data['files'] ?? []) as $fileId) {
			$file = $this->_aiChatFindFile($fileId);
			if ($file) {
				$attachments[] = ['id' => (int)$file['id'], 'name' => $file['name'], 'mime' => $file['mime'], 'size' => (int)$file['size'], 'kind' => $this->_aiChatFileKind($file['name'], $file['mime'])];
			}
		}
		if ($content === '' && !$attachments) {
			throw new InvalidArgumentException('Type a message or add a file');
		}
		if (!empty($data['editFrom'])) {
			// Editing a question removes it and everything after it
			$this->processQueries([['function' => 'query', 'query' => ['DELETE FROM `AICHAT-messages` WHERE `chat_id` = ? AND `id` >= ?', $chatId, (int)$data['editFrom']]]]);
		}
		$message = $this->_aiChatInsertMessage($chatId, 'user', $content, $attachments);
		if (!empty($data['editFrom'])) {
			$this->_aiChatCleanupFiles();
		}
		return $message;
	}

	private function _aiChatSendEvent($event)
	{
		echo 'data: ' . json_encode($event, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
		flush();
	}

	/* ===================== web search ===================== */

	public function _aiChatSearchEnabled()
	{
		return in_array($this->config['AICHAT-searchProvider-include'], ['searxng', 'brave', 'tavily', 'duckduckgo'], true);
	}

	/**
	 * Generic HTTP call for search and image servers: [httpCode, rawBody, errorText]
	 */
	private function _aiChatHttp($method, $url, $headers = [], $body = null, $timeout = 30)
	{
		$curl = curl_init($url);
		curl_setopt_array($curl, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_CONNECTTIMEOUT => 15,
			CURLOPT_TIMEOUT => (int)$timeout,
			CURLOPT_SSL_VERIFYPEER => (bool)$this->config['AICHAT-verifySSL'],
			CURLOPT_SSL_VERIFYHOST => $this->config['AICHAT-verifySSL'] ? 2 : 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; Londerland AI Chat)',
		]);
		if ($method === 'POST') {
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
		}
		$raw = curl_exec($curl);
		$code = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
		if ($raw === false) {
			return [0, null, curl_error($curl) ?: 'Could not reach ' . parse_url($url, PHP_URL_HOST)];
		}
		if ($code < 200 || $code >= 300) {
			return [$code, $raw, $this->_aiChatUpstreamError(json_decode($raw, true), $raw, $code)];
		}
		return [$code, $raw, null];
	}

	/**
	 * Searches the web with the configured provider: [results, errorText], results are [title, url, snippet]
	 */
	public function _aiChatWebSearch($query)
	{
		$provider = $this->config['AICHAT-searchProvider-include'];
		$count = min(10, max(1, (int)$this->config['AICHAT-searchResults'] ?: 5));
		$key = trim($this->config['AICHAT-searchApiKey']);
		switch ($provider) {
			case 'searxng':
				$base = rtrim(trim($this->config['AICHAT-searchUrl']), '/');
				if ($base === '') {
					return [[], 'No SearXNG address is configured'];
				}
				[$code, $raw, $error] = $this->_aiChatHttp('GET', $base . '/search?' . http_build_query(['q' => $query, 'format' => 'json', 'safesearch' => 1]), ['Accept: application/json']);
				break;
			case 'brave':
				[$code, $raw, $error] = $this->_aiChatHttp('GET', 'https://api.search.brave.com/res/v1/web/search?' . http_build_query(['q' => $query, 'count' => $count]), ['Accept: application/json', 'X-Subscription-Token: ' . $key]);
				break;
			case 'tavily':
				[$code, $raw, $error] = $this->_aiChatHttp('POST', 'https://api.tavily.com/search', ['Content-Type: application/json', 'Authorization: Bearer ' . $key], json_encode(['query' => $query, 'max_results' => $count]));
				break;
			case 'duckduckgo':
				[$code, $raw, $error] = $this->_aiChatHttp('POST', 'https://html.duckduckgo.com/html/', ['Content-Type: application/x-www-form-urlencoded'], http_build_query(['q' => $query]));
				break;
			default:
				return [[], 'Web search is not configured'];
		}
		if ($error) {
			return [[], 'Web search failed: ' . $error];
		}
		return [array_slice($this->_aiChatParseSearchResults($provider, $raw), 0, $count), null];
	}

	public function _aiChatParseSearchResults($provider, $raw)
	{
		$results = [];
		$add = function ($title, $url, $snippet) use (&$results) {
			$url = trim((string)$url);
			if (!preg_match('#^https?://#i', $url)) {
				return;
			}
			$results[] = [
				'title' => mb_substr(trim(html_entity_decode(strip_tags((string)$title))) ?: $url, 0, 200),
				'url' => $url,
				'snippet' => mb_substr(trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags((string)$snippet)))), 0, 600),
			];
		};
		if ($provider === 'duckduckgo') {
			$dom = new DOMDocument();
			libxml_use_internal_errors(true);
			$dom->loadHTML('<?xml encoding="UTF-8">' . $raw);
			libxml_clear_errors();
			$xpath = new DOMXPath($dom);
			foreach ($xpath->query("//div[contains(concat(' ', normalize-space(@class), ' '), ' result ')]") as $node) {
				$link = $xpath->query(".//a[contains(@class, 'result__a')]", $node)->item(0);
				if (!$link) {
					continue;
				}
				$href = $link->getAttribute('href');
				// Result links go through a DuckDuckGo redirect that carries the real address in "uddg"
				parse_str((string)parse_url($href, PHP_URL_QUERY), $params);
				$url = $params['uddg'] ?? (strpos($href, '//') === 0 ? 'https:' . $href : $href);
				if (strpos($url, 'duckduckgo.com/y.js') !== false) {
					continue; // advertisement
				}
				$snippet = $xpath->query(".//*[contains(@class, 'result__snippet')]", $node)->item(0);
				$add($link->textContent, $url, $snippet ? $snippet->textContent : '');
			}
			return $results;
		}
		$json = json_decode((string)$raw, true) ?: [];
		$items = $provider === 'brave' ? ($json['web']['results'] ?? []) : ($json['results'] ?? []);
		foreach ($items as $item) {
			$add($item['title'] ?? '', $item['url'] ?? '', $item['content'] ?? ($item['description'] ?? ($item['snippet'] ?? '')));
		}
		return $results;
	}

	/**
	 * Asks the chat model for a good search query for the latest question; falls back to the question itself
	 */
	private function _aiChatSearchQuery($chatId, $model)
	{
		$history = array_slice($this->_aiChatMessages($chatId), -5);
		$question = '';
		$context = '';
		foreach ($history as $message) {
			$context .= strtoupper($message['role']) . ': ' . mb_substr((string)$message['content'], 0, 800) . "\n";
			if ($message['role'] === 'user') {
				$question = (string)$message['content'];
			}
		}
		[$code, $body, $error] = $this->_aiChatRequest('POST', 'chat/completions', [
			'model' => $model,
			'messages' => [
				['role' => 'system', 'content' => 'You write web search queries. Today is ' . gmdate('Y-m-d') . '. Read the conversation and write one short search engine query (at most 12 words) that finds what the last USER message needs. Answer with the query only.'],
				['role' => 'user', 'content' => $context],
			],
			'max_tokens' => 40,
		], 30);
		$query = is_string($body['choices'][0]['message']['content'] ?? null) ? $body['choices'][0]['message']['content'] : '';
		$query = trim(preg_replace('/<think>.*?<\/think>/s', '', $query));
		$query = trim(strtok($query, "\n"), " \t\"'`");
		if ($error || $query === '') {
			$query = mb_substr(trim(preg_replace('/\s+/', ' ', $question)), 0, 200);
		}
		return mb_substr($query, 0, 200);
	}

	// Numbered results for the model; numbering continues across searches so [n] stays unique in one answer
	private function _aiChatFormatResults($results, $offset)
	{
		if (!$results) {
			return 'No results found.';
		}
		$lines = [];
		foreach ($results as $index => $result) {
			$lines[] = '[' . ($offset + $index + 1) . '] ' . $result['title'] . "\nURL: " . $result['url'] . "\n" . $result['snippet'];
		}
		return implode("\n\n", $lines);
	}

	/* ===================== reading web pages ===================== */

	/**
	 * IP addresses a host name points to (or the address itself when the host is one)
	 */
	private function _aiChatResolveHost($host)
	{
		$host = trim($host, '[]');
		if (filter_var($host, FILTER_VALIDATE_IP)) {
			return [$host];
		}
		$ips = [];
		foreach (@dns_get_record($host, DNS_A | DNS_AAAA) ?: [] as $record) {
			$ips[] = $record['ip'] ?? ($record['ipv6'] ?? null);
		}
		$ips = array_merge($ips, @gethostbynamel($host) ?: []);
		return array_values(array_unique(array_filter($ips)));
	}

	private function _aiChatPublicIp($ip)
	{
		// IPv4 addresses written as IPv6 (::ffff:10.0.0.1) are checked as IPv4
		if (stripos($ip, '::ffff:') === 0 && filter_var(substr($ip, 7), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
			$ip = substr($ip, 7);
		}
		if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
			return false;
		}
		// Ranges PHP does not count as private or reserved: carrier-grade NAT and IPv6 unique local / link local
		$packed = inet_pton($ip);
		if (strlen($packed) === 4) {
			return !(ord($packed[0]) === 100 && (ord($packed[1]) & 0xC0) === 64);
		}
		return !((ord($packed[0]) & 0xFE) === 0xFC || (ord($packed[0]) === 0xFE && (ord($packed[1]) & 0xC0) === 0x80));
	}

	/**
	 * Downloads a web page and returns [title, finalUrl, text]; throws RuntimeException with a message for the model
	 */
	private function _aiChatFetchPage($url)
	{
		$allowPrivate = (bool)$this->config['AICHAT-fetchAllowPrivate'];
		// Redirects are followed by hand so every hop gets the address check
		for ($hop = 0; $hop < 6; $hop++) {
			$parts = parse_url($url);
			$scheme = strtolower($parts['scheme'] ?? '');
			$host = $parts['host'] ?? '';
			if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
				throw new RuntimeException('Only http and https links can be read: ' . $url);
			}
			$port = (int)($parts['port'] ?? ($scheme === 'https' ? 443 : 80));
			$ips = $this->_aiChatResolveHost($host);
			if (!$ips) {
				throw new RuntimeException('Could not find the server ' . $host);
			}
			if (!$allowPrivate) {
				foreach ($ips as $ip) {
					if (!$this->_aiChatPublicIp($ip)) {
						throw new RuntimeException('Reading pages on local or private addresses (' . $host . ') is turned off by the admin');
					}
				}
			}
			$body = '';
			$tooBig = false;
			$curl = curl_init($url);
			curl_setopt_array($curl, [
				CURLOPT_HTTPHEADER => ['Accept: text/html,application/xhtml+xml,text/plain,application/json,application/pdf;q=0.9,*/*;q=0.5', 'Accept-Language: en,*;q=0.5'],
				CURLOPT_CONNECTTIMEOUT => 15,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_SSL_VERIFYPEER => (bool)$this->config['AICHAT-verifySSL'],
				CURLOPT_SSL_VERIFYHOST => $this->config['AICHAT-verifySSL'] ? 2 : 0,
				CURLOPT_FOLLOWLOCATION => false,
				CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
				CURLOPT_ENCODING => '',
				CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; Londerland AI Chat)',
				// Connect to the address that was checked, so DNS cannot point somewhere else in between
				CURLOPT_RESOLVE => [trim($host, '[]') . ':' . $port . ':' . (strpos($ips[0], ':') !== false ? '[' . $ips[0] . ']' : $ips[0])],
				CURLOPT_WRITEFUNCTION => function ($curl, $chunk) use (&$body, &$tooBig) {
					$body .= $chunk;
					if (strlen($body) > self::MAX_FETCH_BYTES) {
						$tooBig = true;
						return 0;
					}
					return strlen($chunk);
				},
			]);
			$ok = curl_exec($curl);
			$code = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
			$type = strtolower((string)curl_getinfo($curl, CURLINFO_CONTENT_TYPE));
			$location = (string)curl_getinfo($curl, CURLINFO_REDIRECT_URL);
			if ($ok === false && !$tooBig) {
				throw new RuntimeException('Could not read ' . $url . ': ' . (curl_error($curl) ?: 'no answer'));
			}
			if ($code >= 300 && $code < 400 && $location !== '') {
				$url = $location;
				continue;
			}
			if ($code < 200 || $code >= 300) {
				throw new RuntimeException('The page ' . $url . ' answered with HTTP ' . $code);
			}
			return $this->_aiChatPageText($url, $type, $body);
		}
		throw new RuntimeException('Too many redirects for ' . $url);
	}

	/**
	 * Turns a downloaded page into [title, url, text]
	 */
	private function _aiChatPageText($url, $type, $body)
	{
		$isPdf = strpos($type, 'application/pdf') !== false || strncmp($body, '%PDF', 4) === 0;
		$isHtml = !$isPdf && (strpos($type, 'html') !== false || ($type === '' && preg_match('/^\s*(<!doctype html|<html)/i', $body)));
		if ($isPdf) {
			try {
				$text = (new \Smalot\PdfParser\Parser())->parseContent($body)->getText();
			} catch (\Throwable $e) {
				throw new RuntimeException('Could not read the PDF ' . $url . ': ' . $e->getMessage());
			}
			return [basename((string)parse_url($url, PHP_URL_PATH)) ?: $url, $url, trim($text)];
		}
		if (!$isHtml) {
			if ($type !== '' && !preg_match('#^text/|json|xml|javascript|yaml|csv#', $type)) {
				throw new RuntimeException('The page ' . $url . ' is not text (' . $type . ')');
			}
			if (!mb_check_encoding($body, 'UTF-8')) {
				$body = mb_convert_encoding($body, 'UTF-8', 'Windows-1252');
			}
			return [$url, $url, trim($body)];
		}
		$dom = new DOMDocument();
		libxml_use_internal_errors(true);
		// Pages without a charset are read as UTF-8 instead of the libxml default (Latin-1)
		$dom->loadHTML((preg_match('/<meta[^>]+charset/i', substr($body, 0, 4096)) ? '' : '<?xml encoding="UTF-8">') . $body);
		libxml_clear_errors();
		$xpath = new DOMXPath($dom);
		$title = trim(preg_replace('/\s+/', ' ', (string)($xpath->query('//title')->item(0)->textContent ?? ''))) ?: $url;
		foreach (iterator_to_array($xpath->query('//script|//style|//noscript|//template|//svg|//iframe|//form|//nav|//header|//footer|//aside|//*[@hidden]|//*[@aria-hidden="true"]')) as $node) {
			$node->parentNode->removeChild($node);
		}
		// The main content when the page marks it, otherwise the whole body
		$root = $xpath->query('//main')->item(0) ?: ($xpath->query('//article')->item(0) ?: ($xpath->query('//body')->item(0) ?: $dom->documentElement));
		$text = $root ? $this->_aiChatNodeText($root) : '';
		$text = preg_replace("/[ \t]+\n/", "\n", $text);
		$text = trim(preg_replace("/\n{3,}/", "\n\n", $text));
		return [mb_substr($title, 0, 200), $url, $text];
	}

	// Plain text of an HTML element, keeping headings, list items, paragraphs and table rows on their own lines
	private function _aiChatNodeText($node)
	{
		if ($node->nodeType === XML_TEXT_NODE) {
			return preg_replace('/\s+/', ' ', $node->textContent);
		}
		if ($node->nodeType !== XML_ELEMENT_NODE) {
			return '';
		}
		$name = strtolower($node->nodeName);
		if ($name === 'br') {
			return "\n";
		}
		if ($name === 'pre') {
			return "\n```\n" . rtrim($node->textContent) . "\n```\n";
		}
		$text = '';
		foreach ($node->childNodes as $child) {
			$text .= $this->_aiChatNodeText($child);
		}
		if (preg_match('/^h([1-6])$/', $name, $level)) {
			return "\n\n" . str_repeat('#', (int)$level[1]) . ' ' . trim($text) . "\n\n";
		}
		if ($name === 'li') {
			return "\n- " . trim($text);
		}
		if (in_array($name, ['td', 'th'], true)) {
			return trim($text) . ' | ';
		}
		if (in_array($name, ['p', 'div', 'section', 'article', 'main', 'ul', 'ol', 'table', 'tr', 'blockquote', 'dl', 'dt', 'dd', 'figure', 'figcaption', 'details', 'summary'], true)) {
			return "\n" . trim($text) . "\n";
		}
		return $text;
	}

	/* ===================== image generation ===================== */

	public function _aiChatImagesEnabled()
	{
		return (bool)$this->config['AICHAT-images-include'];
	}

	/**
	 * Creates an image with the OpenAI-compatible /images/generations endpoint and stores it as a file of this user
	 */
	private function _aiChatGenerateImage($prompt)
	{
		$base = trim($this->config['AICHAT-imageBaseUrl']) ?: trim($this->config['AICHAT-baseUrl']);
		$key = trim($this->config['AICHAT-imageApiKey']) ?: trim($this->config['AICHAT-apiKey']);
		$headers = ['Content-Type: application/json', 'Accept: application/json'];
		if ($key !== '') {
			$headers[] = 'Authorization: Bearer ' . $key;
		}
		$request = ['prompt' => mb_substr($prompt, 0, 4000), 'n' => 1];
		if (trim($this->config['AICHAT-imageModel']) !== '') {
			$request['model'] = trim($this->config['AICHAT-imageModel']);
		}
		if (trim($this->config['AICHAT-imageSize']) !== '') {
			$request['size'] = trim($this->config['AICHAT-imageSize']);
		}
		[$code, $raw, $error] = $this->_aiChatHttp('POST', rtrim($base, '/') . '/images/generations', $headers, json_encode($request), max(60, (int)$this->config['AICHAT-requestTimeout']));
		if ($error) {
			throw new RuntimeException('Image generation failed: ' . $error);
		}
		$data = json_decode($raw, true)['data'][0] ?? [];
		if (!empty($data['b64_json'])) {
			$bytes = base64_decode($data['b64_json'], true);
		} elseif (!empty($data['url'])) {
			// Some servers (dall-e-*) answer with a temporary link: keep a copy, the link expires
			[$code, $bytes, $error] = $this->_aiChatHttp('GET', $data['url'], [], null, 60);
			if ($error) {
				throw new RuntimeException('Could not download the created image: ' . $error);
			}
		} else {
			throw new RuntimeException('The image server returned no image');
		}
		$stored = bin2hex(random_bytes(16));
		$path = $this->_aiChatFileDir() . DIRECTORY_SEPARATOR . $stored;
		file_put_contents($path, $bytes);
		$mime = (new finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
		if (!isset(self::IMAGE_TYPES[$mime])) {
			unlink($path);
			throw new RuntimeException('The image server returned something that is not an image');
		}
		$name = 'image-' . gmdate('Ymd-His') . '.' . self::IMAGE_TYPES[$mime];
		$this->processQueries([[
			'function' => 'query',
			'query' => ['INSERT INTO [AICHAT-files]', [
				'user_id' => $this->_aiChatUserId(),
				'name' => $name,
				'mime' => $mime,
				'size' => filesize($path),
				'path' => $stored,
				'created' => $this->_aiChatNow(),
			]]
		]]);
		return [
			'id' => (int)$this->db->getInsertId(),
			'name' => $name,
			'mime' => $mime,
			'size' => filesize($path),
			'kind' => 'image',
			'prompt' => mb_substr($data['revised_prompt'] ?? $prompt, 0, 1000),
		];
	}

	/* ===================== answering ===================== */

	// Tools the model may call on its own (only when the admin allows it and the feature is set up)
	private function _aiChatTools()
	{
		$tools = [];
		// Admin switch plus the user's own Web button in the chat box
		$webTools = $this->config['AICHAT-searchAuto'] && $this->_aiChatGetPrefs()['web_tools'];
		if ($webTools && $this->_aiChatSearchEnabled()) {
			$tools[] = ['type' => 'function', 'function' => [
				'name' => 'web_search',
				'description' => 'Search the web for current or specific information. Use it for recent events, facts you are unsure about, prices, versions and documentation.',
				'parameters' => ['type' => 'object', 'properties' => ['query' => ['type' => 'string', 'description' => 'Search engine query']], 'required' => ['query']],
			]];
		}
		if ($webTools) {
			$tools[] = ['type' => 'function', 'function' => [
				'name' => 'fetch_url',
				'description' => 'Open a web page and read its text. Use it when the user gives a link, and to read a search result in full when its snippet is not enough. Long pages come in parts: call again with the offset given at the end to read further.',
				'parameters' => ['type' => 'object', 'properties' => [
					'url' => ['type' => 'string', 'description' => 'Full http or https address of the page'],
					'offset' => ['type' => 'integer', 'description' => 'Character position to continue reading from (default 0)'],
				], 'required' => ['url']],
			]];
		}
		if ($this->_aiChatImagesEnabled() && $this->config['AICHAT-imageAuto']) {
			$tools[] = ['type' => 'function', 'function' => [
				'name' => 'generate_image',
				'description' => 'Create an image from a detailed description when the user asks for a picture, drawing, logo or other image.',
				'parameters' => ['type' => 'object', 'properties' => ['prompt' => ['type' => 'string', 'description' => 'Detailed description of the image']], 'required' => ['prompt']],
			]];
		}
		return $tools;
	}

	/**
	 * One streamed request to /chat/completions. Text and thinking are forwarded to the browser as they arrive.
	 */
	private function _aiChatStreamRound($body)
	{
		$round = ['answer' => '', 'reasoning' => '', 'toolCalls' => [], 'errorBody' => '', 'aborted' => false, 'code' => 0, 'curlError' => ''];
		$buffer = '';
		$curl = $this->_aiChatCurl($this->_aiChatUrl('chat/completions'), max(30, (int)$this->config['AICHAT-requestTimeout']));
		curl_setopt_array($curl, [
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => json_encode($body),
			CURLOPT_HTTPHEADER => array_merge($this->_aiChatHeaders(), ['Accept: text/event-stream']),
			CURLOPT_WRITEFUNCTION => function ($curl, $chunk) use (&$round, &$buffer) {
				if (connection_aborted()) {
					// The user pressed stop or closed the page: end the upstream request too
					$round['aborted'] = true;
					return 0;
				}
				if ((int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE) >= 400) {
					$round['errorBody'] .= $chunk;
					return strlen($chunk);
				}
				$buffer .= $chunk;
				while (($end = strpos($buffer, "\n")) !== false) {
					$line = trim(substr($buffer, 0, $end));
					$buffer = substr($buffer, $end + 1);
					if (strpos($line, 'data:') !== 0) {
						continue;
					}
					$payload = trim(substr($line, 5));
					if ($payload === '[DONE]') {
						continue;
					}
					$event = json_decode($payload, true);
					if (isset($event['error'])) {
						$round['errorBody'] .= $payload;
						continue;
					}
					$delta = $event['choices'][0]['delta'] ?? [];
					// Reasoning models stream their thinking under different names depending on the server
					$thinking = $delta['reasoning_content'] ?? ($delta['reasoning'] ?? null);
					if (is_string($thinking) && $thinking !== '') {
						$round['reasoning'] .= $thinking;
						$this->_aiChatSendEvent(['type' => 'reasoning', 'content' => $thinking]);
					}
					$text = $delta['content'] ?? null;
					if (is_string($text) && $text !== '') {
						$round['answer'] .= $text;
						$this->_aiChatSendEvent(['type' => 'delta', 'content' => $text]);
					}
					// Tool calls arrive in pieces: the name first, the JSON arguments spread over several chunks
					foreach ($delta['tool_calls'] ?? [] as $position => $call) {
						$index = $call['index'] ?? $position;
						$current = $round['toolCalls'][$index] ?? ['id' => null, 'name' => '', 'arguments' => ''];
						$current['id'] = $call['id'] ?? $current['id'];
						$current['name'] .= $call['function']['name'] ?? '';
						$current['arguments'] .= $call['function']['arguments'] ?? '';
						$round['toolCalls'][$index] = $current;
					}
				}
				return strlen($chunk);
			},
		]);
		curl_exec($curl);
		$round['code'] = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
		$round['curlError'] = curl_error($curl);
		ksort($round['toolCalls']);
		$round['toolCalls'] = array_values($round['toolCalls']);
		return $round;
	}

	private function _aiChatRoundError($round)
	{
		if ($round['errorBody'] !== '') {
			return $this->_aiChatUpstreamError(json_decode($round['errorBody'], true), $round['errorBody'], $round['code'] ?: 500);
		}
		if (!$round['aborted'] && $round['answer'] === '' && $round['reasoning'] === '' && !$round['toolCalls'] && ($round['code'] >= 400 || $round['curlError'])) {
			return $round['curlError'] ?: 'The AI server answered ' . $round['code'];
		}
		return null;
	}

	// Adds the search results to the latest question, so they work with every model (no tool support needed)
	private function _aiChatAddResultsToQuestion(&$messages, $query, $results)
	{
		$block = "\n\n<web_search_results query=\"" . $query . "\">\n" . $this->_aiChatFormatResults($results, 0) . "\n</web_search_results>\n" .
			'Use these web search results (searched ' . gmdate('Y-m-d') . ') where they help, and cite them inline as [1], [2] etc.';
		for ($i = count($messages) - 1; $i >= 0; $i--) {
			if ($messages[$i]['role'] !== 'user') {
				continue;
			}
			if (is_array($messages[$i]['content'])) {
				$messages[$i]['content'][0]['text'] .= $block;
			} else {
				$messages[$i]['content'] .= $block;
			}
			return;
		}
	}

	private function _aiChatLastQuestion($chatId)
	{
		$question = '';
		foreach ($this->_aiChatMessages($chatId) as $message) {
			if ($message['role'] === 'user') {
				$question = (string)$message['content'];
			}
		}
		return $question;
	}

	/**
	 * Streams an answer to the browser as server-sent events.
	 * Events: user (stored question), status, sources, image, delta / reasoning (text pieces), done (stored answer), title, error.
	 */
	public function _aiChatStream($chatId, $data)
	{
		$chat = $this->_aiChatFindChat($chatId);
		if (!$chat) {
			return false;
		}
		$imageMode = !empty($data['image']);
		$searchMode = !empty($data['search']) && !$imageMode;
		if ($imageMode && !$this->_aiChatImagesEnabled()) {
			$this->setAPIResponse('error', 'Image generation is turned off', 409);
			return false;
		}
		if ($searchMode && !$this->_aiChatSearchEnabled()) {
			$this->setAPIResponse('error', 'Web search is not set up', 409);
			return false;
		}
		$model = $this->_aiChatPickModel($data['model'] ?? $chat['model']);
		if (!$model && !$imageMode) {
			$this->setAPIResponse('error', 'No model is available. Ask an admin to check the AI Chat settings.', 409);
			return false;
		}
		try {
			$userMessage = $this->_aiChatPrepareTurn($chat, $data);
		} catch (InvalidArgumentException $e) {
			$this->setAPIResponse('error', $e->getMessage(), 422);
			return false;
		}
		if ($model && $model !== $chat['model']) {
			$this->processQueries([['function' => 'query', 'query' => ['UPDATE [AICHAT-chats] SET', ['model' => $model], 'WHERE `id` = ?', (int)$chatId]]]);
		}

		// From here on the response is a live event stream, not Slim's JSON response
		set_time_limit(0);
		ignore_user_abort(true);
		while (ob_get_level() > 0) {
			ob_end_clean();
		}
		if (function_exists('apache_setenv')) {
			@apache_setenv('no-gzip', '1');
		}
		@ini_set('zlib.output_compression', '0');
		http_response_code(200);
		header('Content-Type: text/event-stream; charset=UTF-8');
		header('Cache-Control: no-cache, no-transform');
		header('X-Accel-Buffering: no');
		if ($userMessage) {
			$this->_aiChatSendEvent(['type' => 'user', 'message' => $userMessage]);
		}

		// "Create image" turns the message into an image prompt; no chat model is involved
		if ($imageMode) {
			$prompt = $this->_aiChatLastQuestion($chatId);
			$this->_aiChatSendEvent(['type' => 'status', 'text' => 'Creating image...']);
			try {
				$image = $this->_aiChatGenerateImage($prompt);
			} catch (RuntimeException $e) {
				$this->setLoggerChannel('AI Chat')->warning($e->getMessage());
				$this->_aiChatSendEvent(['type' => 'error', 'message' => $e->getMessage()]);
				exit;
			}
			$this->_aiChatSendEvent(['type' => 'image', 'image' => $image]);
			$stored = $this->_aiChatInsertMessage($chatId, 'assistant', '', [$image], trim($this->config['AICHAT-imageModel']) ?: 'image', null, ['image' => true]);
			$this->_aiChatSendEvent(['type' => 'done', 'message' => $stored]);
			$this->_aiChatFinishTitle($chat, $chatId, $model, $prompt);
			exit;
		}

		$messages = $this->_aiChatBuildMessages($chatId);
		$sources = [];
		$searches = [];
		if ($searchMode) {
			$this->_aiChatSendEvent(['type' => 'status', 'text' => 'Thinking of a search...']);
			$query = $this->_aiChatSearchQuery($chatId, $model);
			$this->_aiChatSendEvent(['type' => 'status', 'text' => 'Searching the web for "' . $query . '"']);
			[$results, $error] = $this->_aiChatWebSearch($query);
			if ($error) {
				$this->setLoggerChannel('AI Chat')->warning($error);
				$this->_aiChatSendEvent(['type' => 'status', 'text' => $error . ' - answering without it']);
			} else {
				$searches[] = $query;
				$sources = $results;
				$this->_aiChatSendEvent(['type' => 'sources', 'sources' => $sources, 'query' => $query]);
				$this->_aiChatAddResultsToQuestion($messages, $query, $results);
			}
		}

		$tools = $this->_aiChatTools();
		$notices = [];
		$answer = '';
		$reasoning = '';
		$images = [];
		$aborted = false;
		$error = null;
		for ($round = 0; $round < 5; $round++) {
			$body = ['model' => $model, 'messages' => $messages, 'stream' => true];
			if (is_numeric($this->config['AICHAT-temperature'])) {
				$body['temperature'] = (float)$this->config['AICHAT-temperature'];
			}
			if ((int)$this->config['AICHAT-maxTokens'] > 0) {
				$body['max_tokens'] = (int)$this->config['AICHAT-maxTokens'];
			}
			// The last round never offers tools, so the model has to answer
			$offerTools = $tools && $round < 4;
			if ($offerTools) {
				$body['tools'] = $tools;
			}
			$result = $this->_aiChatStreamRound($body);
			$error = $this->_aiChatRoundError($result);
			if ($error && $offerTools && $round === 0 && $result['answer'] === '' && in_array($result['code'], [400, 404, 422, 500], true)) {
				// Model or server without tool support: ask again without tools, and say so
				$this->setLoggerChannel('AI Chat')->warning('The AI server refused the tools, retrying without them: ' . $error, ['model' => $model]);
				$notices[] = 'The AI server refused the ' . implode(' and ', array_map(function ($tool) {
						return $tool['function']['name'];
					}, $tools)) . ' tool, so this answer was made without it. Server message: ' . mb_substr($error, 0, 300);
				$this->_aiChatSendEvent(['type' => 'notice', 'notices' => $notices]);
				$tools = [];
				$round = -1;
				$error = null;
				continue;
			}
			$answer .= $result['answer'];
			$reasoning .= $result['reasoning'];
			$aborted = $result['aborted'];
			if ($error || $aborted || !$result['toolCalls']) {
				break;
			}
			// Run the tools the model asked for and give it the results
			$calls = [];
			foreach ($result['toolCalls'] as $index => $call) {
				$calls[] = ['id' => $call['id'] ?: 'call_' . $round . '_' . $index, 'type' => 'function', 'function' => ['name' => $call['name'], 'arguments' => $call['arguments'] !== '' ? $call['arguments'] : '{}']];
			}
			$messages[] = ['role' => 'assistant', 'content' => $result['answer'] !== '' ? $result['answer'] : null, 'tool_calls' => $calls];
			foreach ($calls as $call) {
				$arguments = json_decode($call['function']['arguments'], true) ?: [];
				$output = $this->_aiChatRunTool($call['function']['name'], $arguments, $sources, $searches, $images);
				$messages[] = ['role' => 'tool', 'tool_call_id' => $call['id'], 'content' => $output];
			}
			if ($answer !== '' && substr($answer, -1) !== "\n") {
				$answer .= "\n\n";
				$this->_aiChatSendEvent(['type' => 'delta', 'content' => "\n\n"]);
			}
		}

		if ($error && $answer === '' && !$images) {
			$this->setLoggerChannel('AI Chat')->warning($error, ['model' => $model]);
			$this->_aiChatSendEvent(['type' => 'error', 'message' => $error]);
			exit;
		}
		$meta = [];
		if ($notices) {
			$meta['notices'] = $notices;
		}
		if ($sources) {
			$meta['sources'] = $sources;
			$meta['searches'] = $searches;
		}
		$stored = $this->_aiChatInsertMessage($chatId, 'assistant', trim($answer), $images, $model, $reasoning !== '' ? $reasoning : null, $meta ?: null);
		if ($aborted) {
			exit;
		}
		$this->_aiChatSendEvent(['type' => 'done', 'message' => $stored]);
		$this->_aiChatFinishTitle($chat, $chatId, $model, null);
		exit;
	}

	private function _aiChatRunTool($name, $arguments, &$sources, &$searches, &$images)
	{
		switch ($name) {
			case 'web_search':
				$query = mb_substr(trim((string)($arguments['query'] ?? '')), 0, 200);
				if ($query === '' || !$this->_aiChatSearchEnabled()) {
					return 'Web search is not available.';
				}
				$this->_aiChatSendEvent(['type' => 'status', 'text' => 'Searching the web for "' . $query . '"']);
				[$results, $error] = $this->_aiChatWebSearch($query);
				if ($error) {
					return $error;
				}
				$text = $this->_aiChatFormatResults($results, count($sources)) . "\n\nCite the results you use inline as [n].";
				$searches[] = $query;
				$sources = array_merge($sources, $results);
				$this->_aiChatSendEvent(['type' => 'sources', 'sources' => $sources, 'query' => $query]);
				return $text;
			case 'fetch_url':
				$url = trim((string)($arguments['url'] ?? ''));
				if ($url === '' || !$this->config['AICHAT-searchAuto']) {
					return 'Reading web pages is not available.';
				}
				if (!preg_match('#^[a-z][a-z0-9+.-]*://#i', $url)) {
					$url = 'https://' . $url;
				}
				$offset = max(0, (int)($arguments['offset'] ?? 0));
				$this->_aiChatSendEvent(['type' => 'status', 'text' => 'Reading ' . (parse_url($url, PHP_URL_HOST) ?: $url)]);
				try {
					[$title, $finalUrl, $text] = $this->_aiChatFetchPage($url);
				} catch (RuntimeException $e) {
					return $e->getMessage();
				}
				if ($text === '') {
					return 'The page ' . $finalUrl . ' has no readable text (it may need JavaScript or a login).';
				}
				$length = mb_strlen($text);
				$part = mb_substr($text, $offset, self::MAX_PAGE_TEXT);
				// The same page read again (next part) keeps its citation number
				$number = null;
				foreach ($sources as $index => $source) {
					if ($source['url'] === $finalUrl) {
						$number = $index + 1;
					}
				}
				if ($number === null) {
					$sources[] = ['title' => $title, 'url' => $finalUrl, 'snippet' => mb_substr(trim(preg_replace('/\s+/', ' ', $text)), 0, 300)];
					$number = count($sources);
					$this->_aiChatSendEvent(['type' => 'sources', 'sources' => $sources]);
				}
				$end = $offset + mb_strlen($part);
				$more = $end < $length ? "\n\n[Page text shortened: characters " . $offset . '-' . $end . ' of ' . $length . '. Call fetch_url with offset ' . $end . ' to read further.]' : '';
				return '[' . $number . '] ' . $title . "\nURL: " . $finalUrl . "\n\n" . ($part !== '' ? $part : '(nothing after this offset)') . $more . "\n\nCite this page inline as [" . $number . '].';
			case 'generate_image':
				$prompt = trim((string)($arguments['prompt'] ?? ''));
				if ($prompt === '' || !$this->_aiChatImagesEnabled()) {
					return 'Image generation is not available.';
				}
				$this->_aiChatSendEvent(['type' => 'status', 'text' => 'Creating image...']);
				try {
					$image = $this->_aiChatGenerateImage($prompt);
				} catch (RuntimeException $e) {
					return $e->getMessage();
				}
				$images[] = $image;
				$this->_aiChatSendEvent(['type' => 'image', 'image' => $image]);
				return 'The image was created and is already shown to the user below your message. Do not add a link or markdown image for it.';
			default:
				return 'Unknown tool ' . $name;
		}
	}

	private function _aiChatFinishTitle($chat, $chatId, $model, $fallback)
	{
		if (!$this->config['AICHAT-autoTitle'] || $chat['title'] !== self::NEW_CHAT_TITLE) {
			return;
		}
		$title = $model ? $this->_aiChatGenerateTitle($chatId, $model) : null;
		if (!$title && $fallback) {
			// No chat model (image only): name the chat after the prompt
			$title = mb_substr(trim(preg_replace('/\s+/', ' ', $fallback)), 0, 60);
			$this->processQueries([['function' => 'query', 'query' => ['UPDATE [AICHAT-chats] SET', ['title' => $title], 'WHERE `id` = ?', (int)$chatId]]]);
		}
		if ($title) {
			$this->_aiChatSendEvent(['type' => 'title', 'title' => $title]);
		}
	}

	private function _aiChatGenerateTitle($chatId, $model)
	{
		$conversation = '';
		foreach (array_slice($this->_aiChatMessages($chatId), 0, 2) as $message) {
			$conversation .= strtoupper($message['role']) . ': ' . mb_substr((string)$message['content'], 0, 1500) . "\n";
		}
		[$code, $body, $error] = $this->_aiChatRequest('POST', 'chat/completions', [
			'model' => $model,
			'messages' => [
				['role' => 'system', 'content' => 'Write a short title (at most 6 words) for this conversation. Answer with the title only, without quotes or punctuation at the end.'],
				['role' => 'user', 'content' => $conversation],
			],
			'max_tokens' => 30,
		], 30);
		$title = $body['choices'][0]['message']['content'] ?? null;
		if ($error || !is_string($title)) {
			return null;
		}
		// Some reasoning models wrap their thinking in <think> tags
		$title = trim(preg_replace('/<think>.*?<\/think>/s', '', $title));
		$title = trim(strtok($title, "\n"), " \t\"'`*#.");
		if ($title === '') {
			return null;
		}
		$title = mb_substr($title, 0, 80);
		$this->processQueries([['function' => 'query', 'query' => ['UPDATE [AICHAT-chats] SET', ['title' => $title], 'WHERE `id` = ?', (int)$chatId]]]);
		return $title;
	}
}
