<?php
$app->get('/plugins/speedtest/settings', function ($request, $response, $args) {
	$SpeedTest = new SpeedTest();
	if ($SpeedTest->checkRoute($request)) {
		if ($SpeedTest->qualifyRequest(1, true)) {
			$GLOBALS['api']['response']['data'] = $SpeedTest->speedTestGetSettings();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
