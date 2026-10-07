<?php
$app->get('/plugins/shuck-stop/settings', function ($request, $response, $args) {
	$shuckStop = new ShuckStop();
	if ($shuckStop->checkRoute($request)) {
		if ($shuckStop->qualifyRequest(1, true)) {
			$GLOBALS['api']['response']['data'] = $shuckStop->_shuckStopPluginGetSettings();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->get('/plugins/shuck-stop/run', function ($request, $response, $args) {
	$shuckStop = new ShuckStop();
	if ($shuckStop->checkRoute($request)) {
		if ($shuckStop->qualifyRequest(1, true)) {
			$shuckStop->_shuckStopPluginRun();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});