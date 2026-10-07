<?php
$app->get('/plugins/chat/settings', function ($request, $response, $args) {
	$Chat = new Chat();
	if ($Chat->checkRoute($request)) {
		if ($Chat->qualifyRequest(1, true)) {
			$GLOBALS['api']['response']['data'] = $Chat->_chatPluginGetSettings();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->get('/plugins/chat/message', function ($request, $response, $args) {
	$Chat = new Chat();
	if ($Chat->checkRoute($request)) {
		if ($Chat->qualifyRequest($Chat->config['CHAT-Auth-include'], true)) {
			$GLOBALS['api']['response']['data'] = $Chat->_chatPluginGetChatMessages();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/plugins/chat/message', function ($request, $response, $args) {
	$Chat = new Chat();
	if ($Chat->checkRoute($request)) {
		if ($Chat->qualifyRequest($Chat->config['CHAT-Auth-include'], true)) {
			$Chat->_chatPluginSendChatMessage($Chat->apiData($request));
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});