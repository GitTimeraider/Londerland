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
$app->get('/plugins/aichat/test/search', function ($request, $response, $args) {
	$AiChat = new AiChat();
	if ($AiChat->_aiChatAdminAccess($request)) {
		if (!$AiChat->_aiChatSearchEnabled()) {
			$AiChat->setAPIResponse('error', 'Choose and save a search provider first', 409);
		} else {
			[$results, $error] = $AiChat->_aiChatWebSearch($request->getQueryParams()['q'] ?? 'Londerland homelab dashboard');
			if ($error) {
				$AiChat->setAPIResponse('error', $error, 502);
			} else {
				$AiChat->setAPIResponse('success', 'Search works. Results: ' . count($results), 200, $results);
			}
		}
	}
	return aiChatJsonResponse($response);
});
$app->get('/plugins/aichat/test/usage', function ($request, $response, $args) {
	$AiChat = new AiChat();
	if ($AiChat->_aiChatAdminAccess($request)) {
		@unlink($AiChat->_aiChatUsageCacheFile());
		$usage = $AiChat->_aiChatUsage();
		if ($usage !== false) {
			$AiChat->setAPIResponse('success', 'OmniRoute answered. Connections with limits: ' . count($usage), 200, $usage);
		}
	}
	return aiChatJsonResponse($response);
});
// Used percentages of the OmniRoute connections (5 hour / weekly limits), if the admin turned it on
$app->get('/plugins/aichat/usage', function ($request, $response, $args) {
	$AiChat = new AiChat();
	if ($AiChat->_aiChatAccess($request)) {
		if (!$AiChat->_aiChatUsageAllowed()) {
			$AiChat->setAPIResponse('error', 'Usage limits are not shown to your group', 403);
		} else {
			$usage = $AiChat->_aiChatUsage(!empty($request->getQueryParams()['refresh']));
			if ($usage !== false) {
				$GLOBALS['api']['response']['data'] = $usage;
			}
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
				'webTools' => (bool)$AiChat->config['AICHAT-searchAuto'],
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
$app->get('/plugins/aichat/folders', function ($request, $response, $args) {
	$AiChat = new AiChat();
	if ($AiChat->_aiChatAccess($request)) {
		$GLOBALS['api']['response']['data'] = $AiChat->_aiChatListFolders();
	}
	return aiChatJsonResponse($response);
});
$app->post('/plugins/aichat/folders', function ($request, $response, $args) {
	$AiChat = new AiChat();
	if ($AiChat->_aiChatAccess($request)) {
		$AiChat->_aiChatCreateFolder($AiChat->apiData($request) ?? []);
	}
	return aiChatJsonResponse($response);
});
$app->put('/plugins/aichat/folders/{id}', function ($request, $response, $args) {
	$AiChat = new AiChat();
	if ($AiChat->_aiChatAccess($request)) {
		$AiChat->_aiChatUpdateFolder($args['id'], $AiChat->apiData($request) ?? []);
	}
	return aiChatJsonResponse($response);
});
// Deleting a folder keeps its chats; they move back to the main list
$app->delete('/plugins/aichat/folders/{id}', function ($request, $response, $args) {
	$AiChat = new AiChat();
	if ($AiChat->_aiChatAccess($request)) {
		$AiChat->_aiChatDeleteFolder($args['id']);
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
// Public read-only link of a chat: the owner's current link, make a (new) one, or stop it
$app->get('/plugins/aichat/chats/{id}/share', function ($request, $response, $args) {
	$AiChat = new AiChat();
	if ($AiChat->_aiChatShareAccess($request)) {
		$AiChat->_aiChatShareGet($args['id']);
	}
	return aiChatJsonResponse($response);
});
$app->post('/plugins/aichat/chats/{id}/share', function ($request, $response, $args) {
	$AiChat = new AiChat();
	if ($AiChat->_aiChatShareAccess($request)) {
		$AiChat->_aiChatShareCreate($args['id'], $AiChat->apiData($request) ?? []);
	}
	return aiChatJsonResponse($response);
});
$app->delete('/plugins/aichat/chats/{id}/share', function ($request, $response, $args) {
	$AiChat = new AiChat();
	// stopping a link is always allowed, also after sharing was switched off for the group
	if ($AiChat->_aiChatAccess($request)) {
		$AiChat->_aiChatShareDelete($args['id']);
	}
	return aiChatJsonResponse($response);
});
$app->delete('/plugins/aichat/shares/{id}', function ($request, $response, $args) {
	$AiChat = new AiChat();
	if ($AiChat->_aiChatAdminAccess($request)) {
		$AiChat->_aiChatAdminRevokeShare($args['id']);
	}
	return aiChatJsonResponse($response);
});
// The public page: no login. Only the copy made when the link was created, under a strict Content-Security-Policy.
$app->get('/plugins/aichat/shared/{token}', function ($request, $response, $args) {
	$AiChat = new AiChat();
	$chat = $AiChat->_aiChatSharedChat($args['token']);
	$response->getBody()->write($AiChat->_aiChatSharedPageHtml($chat));
	return $response
		->withStatus($chat === null ? 404 : 200)
		->withHeader('Content-Type', 'text/html;charset=UTF-8')
		->withHeader('Content-Security-Policy', "default-src 'none'; script-src 'self'; style-src 'self'; img-src 'none'; connect-src 'none'; font-src 'none'; base-uri 'none'; form-action 'none'; frame-ancestors 'none'")
		->withHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
		->withHeader('Referrer-Policy', 'no-referrer')
		->withHeader('X-Content-Type-Options', 'nosniff')
		->withHeader('X-Frame-Options', 'DENY')
		->withHeader('Cross-Origin-Opener-Policy', 'same-origin')
		->withHeader('Cache-Control', 'no-store');
});
