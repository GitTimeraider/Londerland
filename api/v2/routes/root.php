<?php
/* Forward root to /status */
$app->get('', function ($request, $response, $args) {
	return $response
		->withHeader('Location', '/api/v2/status')
		->withStatus(302);
});
$app->get('/', function ($request, $response, $args) {
	return $response
		->withHeader('Location', '/api/v2/status')
		->withStatus(302);
});
$app->get('/status[/]', function ($request, $response, $args) {
	$Londerland = ($request->getAttribute('Londerland')) ?? new Londerland();
	if ($Londerland->checkRoute($request)) {
		$GLOBALS['api']['response']['data'] = $Londerland->status(false);
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->any('/auth-[{group}[/]]', function ($request, $response, $args) {
	$Londerland = ($request->getAttribute('Londerland')) ?? new Londerland();
	$_GET['group'] = $args['group'] ?? 0;
	$Londerland->auth();
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->any('/auth[/[{group}[/{type}[/{ips}]]]]', function ($request, $response, $args) {
	$Londerland = ($request->getAttribute('Londerland')) ?? new Londerland();
	$_GET['group'] = $args['group'] ?? 0;
	$_GET['type'] = $args['type'] ?? 'deny';
	$_GET['ips'] = $args['ips'] ?? '192.0.0.0';
	$Londerland->auth();
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->any('/londerland-auth[/[{group}[/{type}[/{ips}]]]]', function ($request, $response, $args) {
	$Londerland = ($request->getAttribute('Londerland')) ?? new Londerland();
	$_GET['group'] = $args['group'] ?? 0;
	$_GET['type'] = $args['type'] ?? 'deny';
	$_GET['ips'] = $args['ips'] ?? '192.0.0.0';
	$Londerland->auth();
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->get('/launch[/]', function ($request, $response, $args) {
	$Londerland = ($request->getAttribute('Londerland')) ?? new Londerland();
	$tabInfo = $Londerland->getUserTabsAndCategories();
	$GLOBALS['api']['response']['data']['categories'] = ($tabInfo['categories']) ?? false;
	$GLOBALS['api']['response']['data']['tabs'] = ($tabInfo['tabs']) ?? false;
	$GLOBALS['api']['response']['data']['user'] = $Londerland->user;
	$GLOBALS['api']['response']['data']['theme'] = $Londerland->config['theme'];
	$GLOBALS['api']['response']['data']['style'] = $Londerland->config['style'];
	$GLOBALS['api']['response']['data']['version'] = $Londerland->version;
	$GLOBALS['api']['response']['data']['settings'] = $Londerland->londerlandSpecialSettings();
	$GLOBALS['api']['response']['data']['plugins'] = $Londerland->pluginGlobalList();
	$GLOBALS['api']['response']['data']['appearance'] = $Londerland->loadAppearance();
	$GLOBALS['api']['response']['data']['status'] = $Londerland->launch();
	$GLOBALS['api']['response']['data']['sso'] = $Londerland->ssoCookies();
	$GLOBALS['api']['response']['data']['warnings'] = $Londerland->warnings;
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});