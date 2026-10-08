<?php
$app->any('/multiple/socks/{app}/{instance}/{route:.*}', function ($request, $response, $args) {
	$Londerland = ($request->getAttribute('Londerland')) ?? new Londerland();
	$appDetails = $Londerland->socksListing($args['app']);
	if (!$appDetails) {
		$Londerland->setAPIResponse('error', 'Application not supported for socks', 404);
		$response->getBody()->write(jsonE($GLOBALS['api']));
		return $response
			->withHeader('Content-Type', 'application/json;charset=UTF-8')
			->withStatus($GLOBALS['responseCode']);
	}
	$socks = $Londerland->socks($appDetails, $request, $args['instance']);
	$data = $socks ?? jsonE($GLOBALS['api']);
	$response->getBody()->write($data);
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->any('/socks/{app}/{route:.*}', function ($request, $response, $args) {
	$Londerland = ($request->getAttribute('Londerland')) ?? new Londerland();
	$appDetails = $Londerland->socksListing($args['app']);
	if (!$appDetails) {
		$Londerland->setAPIResponse('error', 'Application not supported for socks', 404);
		$response->getBody()->write(jsonE($GLOBALS['api']));
		return $response
			->withHeader('Content-Type', 'application/json;charset=UTF-8')
			->withStatus($GLOBALS['responseCode']);
	}
	$socks = $Londerland->socks($appDetails, $request);
	$data = $socks ?? jsonE($GLOBALS['api']);
	$response->getBody()->write($data);
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});