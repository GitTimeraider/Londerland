<?php
$app->get('/config[/{item}[/{term}]]', function ($request, $response, $args) {
	$Londerland = ($request->getAttribute('Londerland')) ?? new Londerland();
	if ($Londerland->qualifyRequest(1, true)) {
		if (isset($args['item'])) {
			$search = ($args['term']) ?? null;
			$Londerland->getConfigItem($args['item'], $search);
		} else {
			$GLOBALS['api']['response']['data'] = $Londerland->getConfigItems();
		}
		
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->put('/config', function ($request, $response, $args) {
	$Londerland = ($request->getAttribute('Londerland')) ?? new Londerland();
	if ($Londerland->qualifyRequest(1, true)) {
		$Londerland->updateConfigItems($Londerland->apiData($request));
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});