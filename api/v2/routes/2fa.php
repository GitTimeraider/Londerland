<?php
$app->post('/2fa', function ($request, $response, $args) {
	$Londerland = ($request->getAttribute('Londerland')) ?? new Londerland();
	if ($Londerland->qualifyRequest(998, true)) {
		$data = $Londerland->apiData($request);
		$GLOBALS['api']['response']['data'] = $Londerland->verify2FA($data['secret'], $data['code'], $data['type']);
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->put('/2fa', function ($request, $response, $args) {
	$Londerland = ($request->getAttribute('Londerland')) ?? new Londerland();
	if ($Londerland->qualifyRequest(998, true)) {
		$data = $Londerland->apiData($request);
		$Londerland->save2FA($data['secret'], $data['type']);
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/2fa/{type}', function ($request, $response, $args) {
	$Londerland = ($request->getAttribute('Londerland')) ?? new Londerland();
	if ($Londerland->qualifyRequest(998, true)) {
		$GLOBALS['api']['response']['data'] = $Londerland->create2FA($args['type']);
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->delete('/2fa', function ($request, $response, $args) {
	$Londerland = ($request->getAttribute('Londerland')) ?? new Londerland();
	if ($Londerland->qualifyRequest(998, true)) {
		$Londerland->remove2FA();
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});