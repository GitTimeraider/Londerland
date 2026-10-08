<?php
$app->get('/groups', function ($request, $response, $args) {
	$Londerland = ($request->getAttribute('Londerland')) ?? new Londerland();
	if ($Londerland->qualifyRequest(1, true)) {
		$GLOBALS['api']['response']['data'] = $Londerland->getAllGroups();
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
	
});
$app->get('/groups/{id}', function ($request, $response, $args) {
	$Londerland = ($request->getAttribute('Londerland')) ?? new Londerland();
	if ($Londerland->qualifyRequest(1, true)) {
		$GLOBALS['api']['response']['data'] = $Londerland->getGroupById($args['id']);
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
	
});
$app->post('/groups', function ($request, $response, $args) {
	$Londerland = ($request->getAttribute('Londerland')) ?? new Londerland();
	if ($Londerland->checkRoute($request)) {
		if ($Londerland->qualifyRequest(1, true)) {
			$Londerland->addGroup($Londerland->apiData($request));
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
	
});
$app->put('/groups/{id}', function ($request, $response, $args) {
	$Londerland = ($request->getAttribute('Londerland')) ?? new Londerland();
	if ($Londerland->checkRoute($request)) {
		if ($Londerland->qualifyRequest(1, true)) {
			$Londerland->updateGroup($args['id'], $Londerland->apiData($request));
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json')
		->withStatus($GLOBALS['responseCode']);
});
$app->delete('/groups/{id}', function ($request, $response, $args) {
	$Londerland = ($request->getAttribute('Londerland')) ?? new Londerland();
	if ($Londerland->checkRoute($request)) {
		if ($Londerland->qualifyRequest(1, true)) {
			$Londerland->deleteGroup($args['id']);
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json')
		->withStatus($GLOBALS['responseCode']);
});