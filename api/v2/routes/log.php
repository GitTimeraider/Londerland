<?php
$app->get('/log[/{number}[/{trace_id}]]', function ($request, $response, $args) {
	$Londerland = ($request->getAttribute('Londerland')) ?? new Londerland();
	if ($Londerland->checkRoute($request)) {
		if ($Londerland->qualifyRequest(1, true)) {
			$args['number'] = $args['number'] ?? 0;
			$args['trace_id'] = $args['trace_id'] ?? null;
			$_GET['pageSize'] = $_GET['pageSize'] ?? 1000;
			$_GET['offset'] = $_GET['offset'] ?? 0;
			$_GET['filter'] = $_GET['filter'] ?? 'NONE';
			$Londerland->getLog($_GET['pageSize'], $_GET['offset'], $_GET['filter'], $args['number'], $args['trace_id']);
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->delete('/log[/{number}]', function ($request, $response, $args) {
	$Londerland = ($request->getAttribute('Londerland')) ?? new Londerland();
	if ($Londerland->checkRoute($request)) {
		if ($Londerland->qualifyRequest(1, true)) {
			$args['number'] = $args['number'] ?? 0;
			$Londerland->purgeLog($args['number']);
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});