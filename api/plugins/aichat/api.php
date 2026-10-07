<?php
/*
 * AI Chat routes. Every route except settings/test needs a logged in (non-guest) user and only
 * touches that user's own chats, files and preferences.
 */
function aiChatJsonResponse($response)
{
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
}

$app->get('/plugins/aichat/settings', function ($request, $response, $args) {
	$AiChat = new AiChat();
	if ($AiChat->_aiChatAdminAccess($request)) {
		$GLOBALS['api']['response']['data'] = $AiChat->_aiChatGetSettings();
	}
	return aiChatJsonResponse($response);
});
$app->get('/plugins/aichat/test', function ($request, $response, $args) {
	$AiChat = new AiChat();
	if ($AiChat->_aiChatAdminAccess($request)) {
		if ($AiChat->config['AICHAT-baseUrl'] == '') {
			$AiChat->setAPIResponse('error', 'Enter and save the API Base URL first', 409);
		} else {
			$AiChat->_aiChatTestConnection();
		}
	}
	return aiChatJsonResponse($response);
});
$app->get('/plugins/aichat/models', function ($request, $response, $args) {
	$AiChat = new AiChat();
	if ($AiChat->_aiChatAccess($request)) {
		$models = $AiChat->_aiChatModels();
		if ($models !== false) {
			$GLOBALS['api']['response']['data'] = [
				'models' => $models['models'],
				'defaultModel' => trim($AiChat->config['AICHAT-defaultModel']) ?: null,
				'prefs' => $AiChat->_aiChatGetPrefs(),
			];
		}
	}
	return aiChatJsonResponse($response);
});
$app->put('/plugins/aichat/prefs', function ($request, $response, $args) {
	$AiChat = new AiChat();
	if ($AiChat->_aiChatAccess($request)) {
		$AiChat->_aiChatSavePrefs($AiChat->apiData($request) ?? []);
	}
	return aiChatJsonResponse($response);
});
$app->get('/plugins/aichat/chats', function ($request, $response, $args) {
	$AiChat = new AiChat();
	if ($AiChat->_aiChatAccess($request)) {
		$GLOBALS['api']['response']['data'] = $AiChat->_aiChatListChats($request->getQueryParams()['search'] ?? null);
	}
	return aiChatJsonResponse($response);
});
$app->post('/plugins/aichat/chats', function ($request, $response, $args) {
	$AiChat = new AiChat();
	if ($AiChat->_aiChatAccess($request)) {
		$AiChat->_aiChatCreateChat($AiChat->apiData($request) ?? []);
	}
	return aiChatJsonResponse($response);
});
$app->delete('/plugins/aichat/chats', function ($request, $response, $args) {
	$AiChat = new AiChat();
	if ($AiChat->_aiChatAccess($request)) {
		$AiChat->_aiChatDeleteAllChats();
	}
	return aiChatJsonResponse($response);
});
$app->get('/plugins/aichat/chats/{id}', function ($request, $response, $args) {
	$AiChat = new AiChat();
	if ($AiChat->_aiChatAccess($request)) {
		$chat = $AiChat->_aiChatGetChat($args['id']);
		if ($chat !== false) {
			$GLOBALS['api']['response']['data'] = $chat;
		}
	}
	return aiChatJsonResponse($response);
});
$app->put('/plugins/aichat/chats/{id}', function ($request, $response, $args) {
	$AiChat = new AiChat();
	if ($AiChat->_aiChatAccess($request)) {
		$AiChat->_aiChatUpdateChat($args['id'], $AiChat->apiData($request) ?? []);
	}
	return aiChatJsonResponse($response);
});
$app->delete('/plugins/aichat/chats/{id}', function ($request, $response, $args) {
	$AiChat = new AiChat();
	if ($AiChat->_aiChatAccess($request)) {
		$AiChat->_aiChatDeleteChat($args['id']);
	}
	return aiChatJsonResponse($response);
});
// Streams the answer as server-sent events; only falls back to JSON when the request is refused
$app->post('/plugins/aichat/chats/{id}/stream', function ($request, $response, $args) {
	$AiChat = new AiChat();
	if ($AiChat->_aiChatAccess($request)) {
		$AiChat->_aiChatStream($args['id'], $AiChat->apiData($request) ?? []);
	}
	return aiChatJsonResponse($response);
});
$app->post('/plugins/aichat/files', function ($request, $response, $args) {
	$AiChat = new AiChat();
	if ($AiChat->_aiChatAccess($request)) {
		$AiChat->_aiChatUpload($request);
	}
	return aiChatJsonResponse($response);
});
$app->get('/plugins/aichat/files/{id}', function ($request, $response, $args) {
	$AiChat = new AiChat();
	if ($AiChat->_aiChatAccess($request)) {
		$file = $AiChat->_aiChatFindFile($args['id']);
		if ($file) {
			$response->getBody()->write(file_get_contents($file['fullPath']));
			$inline = strpos($file['mime'], 'image/') === 0;
			return $response
				->withHeader('Content-Type', $inline ? $file['mime'] : 'application/octet-stream')
				->withHeader('Content-Disposition', ($inline ? 'inline' : 'attachment') . '; filename="' . addcslashes($file['name'], '"\\') . '"')
				->withHeader('X-Content-Type-Options', 'nosniff')
				->withHeader('Cache-Control', 'private, max-age=86400');
		}
	}
	return aiChatJsonResponse($response);
});
