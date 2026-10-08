<?php
$app->get('/londerland/{page}[/{var1}[/{var2}]]', function ($request, $response, $args) {
	$Londerland = ($request->getAttribute('Londerland')) ?? new Londerland();
	$_GET['londerland'] = true;
	$_GET['vars'] = $args;
	$page = null;
	if ($Londerland->checkRoute($request)) {
		$page = $Londerland->getPage($args['page']);
	}
	if ($page) {
		$response->getBody()->write($page);
		return $response
			->withHeader('Content-Type', 'text/html;charset=UTF-8')
			->withStatus($GLOBALS['responseCode']);
	} else {
		$response->getBody()->write(jsonE($GLOBALS['api']));
		return $response
			->withHeader('Content-Type', 'application/json;charset=UTF-8')
			->withStatus($GLOBALS['responseCode']);
	}
});