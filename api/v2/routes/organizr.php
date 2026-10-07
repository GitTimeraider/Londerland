<?php
$app->get('/organizr/{page}[/{var1}[/{var2}]]', function ($request, $response, $args) {
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	$_GET['organizr'] = true;
	$_GET['vars'] = $args;
	$page = null;
	if ($Organizr->checkRoute($request)) {
		$page = $Organizr->getPage($args['page']);
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