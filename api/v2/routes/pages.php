<?php
$app->get('/page/{page}', function ($request, $response, $args) {
	$Londerland = ($request->getAttribute('Londerland')) ?? new Londerland();
	if ($Londerland->checkRoute($request)) {
		$page = $Londerland->getPage($args['page']);
		if ($page) {
			$GLOBALS['api']['response']['data'] = $page;
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->get('/page', function ($request, $response, $args) {
	$Londerland = ($request->getAttribute('Londerland')) ?? new Londerland();
	if ($Londerland->checkRoute($request)) {
		$page = $Londerland->getPageList();
		if ($page) {
			$GLOBALS['api']['response']['data'] = $page;
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});