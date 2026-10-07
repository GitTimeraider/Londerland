<?php
// PLUGIN INFORMATION
$GLOBALS['plugins']['AI Chat'] = array( // Plugin Name
	'name' => 'AI Chat', // Plugin Name
	'author' => 'Organizr', // Who wrote the plugin
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
class AiChat extends Organizr
{
	// Text-like files are sent to the model as text, images as image parts, PDFs as extracted text
	private const TEXT_EXTENSIONS = ['txt', 'md', 'markdown', 'csv', 'tsv', 'json', 'xml', 'yaml', 'yml', 'ini', 'conf', 'cfg', 'toml', 'log', 'html', 'htm', 'css', 'js', 'mjs', 'ts', 'tsx', 'jsx', 'php', 'py', 'rb', 'go', 'rs', 'java', 'kt', 'c', 'h', 'cpp', 'hpp', 'cs', 'swift', 'sh', 'bash', 'zsh', 'ps1', 'bat', 'sql', 'env', 'dockerfile', 'vue', 'svelte', 'lua', 'pl', 'r', 'scala', 'dart', 'tex'];
	private const IMAGE_TYPES = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/gif' => 'gif', 'image/webp' => 'webp'];
	// Cap on extracted text per file so one big upload cannot blow up the request
	private const MAX_FILE_TEXT = 200000;
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
		// Even if the setting were set to Guest, guests stay out
		$minimum = min((int)$this->config['AICHAT-Auth-include'], 998);
		if (!$this->qualifyRequest($minimum, true)) {
			return false;
		}
		if ($this->config['AICHAT-baseUrl'] == '') {
			$this->setAPIResponse('error', 'AI Chat has no server configured yet', 409);
			return false;
		}
		$this->_aiChatEnsureTables();
		return true;
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
				`send_on_enter`	INTEGER DEFAULT 1
			);'
		];
		foreach ($tables as $name => $create) {
			if (!$this->_aiChatTableExists($name)) {
				$this->processQueries([['function' => 'query', 'query' => $create]]);
			}
		}
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
		$groups = array_values(array_filter($this->groupSelect(), function ($group) {
			return (int)$group['value'] !== 999;
		}));
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
										<li><i class="fa fa-chevron-right text-info"></i> <span lang="en">Logged in users in the chosen group (or higher) get a chat button in the bottom left corner. Guests never see it.</span></li>
										<li><i class="fa fa-chevron-right text-info"></i> <span lang="en">The API key stays on the Organizr server; browsers never receive it.</span></li>
										<li><i class="fa fa-chevron-right text-info"></i> <span lang="en">Save the settings, then use Test Connection to check the server and load its models.</span></li>
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
					'help' => 'Address up to and including /v1. Organizr adds /models and /chat/completions to it.'
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
					'type' => 'select',
					'name' => 'AICHAT-Auth-include',
					'label' => 'Minimum Authentication',
					'value' => $this->config['AICHAT-Auth-include'],
					'options' => $groups,
					'help' => 'Lowest group that gets the chat. Guests are always excluded.'
				),
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
	public function _aiChatModels($reportErrors = true)
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
		$models = array_values(array_unique(array_filter($models, function ($model) use ($allowed) {
			return $this->_aiChatModelAllowed($model, $allowed);
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
		$result = $this->_aiChatModels();
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
		$row = [
			'default_model' => $prefs['default_model'],
			'system_prompt' => $prefs['system_prompt'],
			'send_on_enter' => $prefs['send_on_enter'] ? 1 : 0,
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
				$messages[] = ['role' => $message['role'], 'content' => (string)$message['content']];
			}
		}
		return $messages;
	}

	private function _aiChatInsertMessage($chatId, $role, $content, $attachments = [], $model = null, $reasoning = null)
	{
		$this->processQueries([[
			'function' => 'query',
			'query' => ['INSERT INTO [AICHAT-messages]', [
				'chat_id' => (int)$chatId,
				'role' => $role,
				'content' => $content,
				'reasoning' => $reasoning,
				'attachments' => json_encode(array_values($attachments)),
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

	/**
	 * Streams an answer to the browser as server-sent events while forwarding the question to the AI server.
	 * Events: user (stored question), delta / reasoning (text pieces), done (stored answer), title, error.
	 */
	public function _aiChatStream($chatId, $data)
	{
		$chat = $this->_aiChatFindChat($chatId);
		if (!$chat) {
			return false;
		}
		$model = $this->_aiChatPickModel($data['model'] ?? $chat['model']);
		if (!$model) {
			$this->setAPIResponse('error', 'No model is available. Ask an admin to check the AI Chat settings.', 409);
			return false;
		}
		try {
			$userMessage = $this->_aiChatPrepareTurn($chat, $data);
		} catch (InvalidArgumentException $e) {
			$this->setAPIResponse('error', $e->getMessage(), 422);
			return false;
		}
		if ($model !== $chat['model']) {
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

		$body = ['model' => $model, 'messages' => $this->_aiChatBuildMessages($chatId), 'stream' => true];
		if (is_numeric($this->config['AICHAT-temperature'])) {
			$body['temperature'] = (float)$this->config['AICHAT-temperature'];
		}
		if ((int)$this->config['AICHAT-maxTokens'] > 0) {
			$body['max_tokens'] = (int)$this->config['AICHAT-maxTokens'];
		}

		$answer = '';
		$reasoning = '';
		$buffer = '';
		$errorBody = '';
		$aborted = false;
		$curl = $this->_aiChatCurl($this->_aiChatUrl('chat/completions'), max(30, (int)$this->config['AICHAT-requestTimeout']));
		curl_setopt_array($curl, [
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => json_encode($body),
			CURLOPT_HTTPHEADER => array_merge($this->_aiChatHeaders(), ['Accept: text/event-stream']),
			CURLOPT_WRITEFUNCTION => function ($curl, $chunk) use (&$answer, &$reasoning, &$buffer, &$errorBody, &$aborted) {
				if (connection_aborted()) {
					// The user pressed stop or closed the page: end the upstream request too
					$aborted = true;
					return 0;
				}
				$code = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
				if ($code >= 400) {
					$errorBody .= $chunk;
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
						$errorBody .= $payload;
						continue;
					}
					$delta = $event['choices'][0]['delta'] ?? [];
					// Reasoning models stream their thinking under different names depending on the server
					$thinking = $delta['reasoning_content'] ?? ($delta['reasoning'] ?? null);
					if (is_string($thinking) && $thinking !== '') {
						$reasoning .= $thinking;
						$this->_aiChatSendEvent(['type' => 'reasoning', 'content' => $thinking]);
					}
					$text = $delta['content'] ?? null;
					if (is_string($text) && $text !== '') {
						$answer .= $text;
						$this->_aiChatSendEvent(['type' => 'delta', 'content' => $text]);
					}
				}
				return strlen($chunk);
			},
		]);
		curl_exec($curl);
		$code = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
		$curlError = curl_error($curl);

		if ($errorBody !== '' || (!$aborted && $answer === '' && $reasoning === '' && ($code >= 400 || $curlError))) {
			$message = $errorBody !== ''
				? $this->_aiChatUpstreamError(json_decode($errorBody, true), $errorBody, $code ?: 500)
				: ($curlError ?: 'The AI server sent no answer');
			$this->setLoggerChannel('AI Chat')->warning($message, ['model' => $model]);
			if ($answer === '') {
				$this->_aiChatSendEvent(['type' => 'error', 'message' => $message]);
				exit;
			}
		}
		$stored = $this->_aiChatInsertMessage($chatId, 'assistant', $answer, [], $model, $reasoning !== '' ? $reasoning : null);
		if ($aborted) {
			exit;
		}
		$this->_aiChatSendEvent(['type' => 'done', 'message' => $stored]);
		if ($this->config['AICHAT-autoTitle'] && $chat['title'] === self::NEW_CHAT_TITLE && $answer !== '') {
			$title = $this->_aiChatGenerateTitle($chatId, $model);
			if ($title) {
				$this->_aiChatSendEvent(['type' => 'title', 'title' => $title]);
			}
		}
		exit;
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
