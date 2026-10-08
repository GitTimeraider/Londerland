<?php
$app->get('/oauth/trakt', function ($request, $response, $args) {
	$Londerland = ($request->getAttribute('Londerland')) ?? new Londerland();
	if ($Londerland->checkRoute($request)) {
		if ($Londerland->qualifyRequest(1, true)) {
			$Londerland->traktOAuth();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});