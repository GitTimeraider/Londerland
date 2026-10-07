<?php
$app->get('/plugins/invites/settings', function ($request, $response, $args) {
	$Invites = new Invites();
	if ($Invites->checkRoute($request)) {
		if ($Invites->qualifyRequest(1, true)) {
			$GLOBALS['api']['response']['data'] = $Invites->_invitesPluginGetSettings();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->get('/plugins/invites', function ($request, $response, $args) {
	$Invites = new Invites();
	if ($Invites->checkRoute($request)) {
		if ($Invites->qualifyRequest($Invites->config['INVITES-Auth-include'], true)) {
			$GLOBALS['api']['response']['data'] = $Invites->_invitesPluginGetCodes();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/plugins/invites', function ($request, $response, $args) {
	$Invites = new Invites();
	if ($Invites->checkRoute($request)) {
		if ($Invites->qualifyRequest($Invites->config['INVITES-Auth-include'], true)) {
			$Invites->_invitesPluginCreateCode($Invites->apiData($request));
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->get('/plugins/invites/{code}', function ($request, $response, $args) {
	$Invites = new Invites();
	if ($Invites->checkRoute($request)) {
		if ($Invites->qualifyRequest(999, true)) {
			$Invites->_invitesPluginVerifyCode($args['code']);
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/plugins/invites/{code}', function ($request, $response, $args) {
	$Invites = new Invites();
	if ($Invites->checkRoute($request)) {
		if ($Invites->qualifyRequest(999, true)) {
			$Invites->_invitesPluginUseCode($args['code'], $Invites->apiData($request));
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->delete('/plugins/invites/{code}', function ($request, $response, $args) {
	$Invites = new Invites();
	if ($Invites->checkRoute($request)) {
		if ($Invites->qualifyRequest($Invites->config['INVITES-Auth-include'], true)) {
			$Invites->_invitesPluginDeleteCode($args['code']);
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
