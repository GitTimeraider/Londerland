<?php
$app->get('/ip/{ip}', function ($request, $response, $args) {
	$Londerland = ($request->getAttribute('Londerland')) ?? new Londerland();
	if ($Londerland->checkRoute($request)) {
		if ($Londerland->qualifyRequest(1, true)) {
			$Londerland->getIpInfo($args['ip']);
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});