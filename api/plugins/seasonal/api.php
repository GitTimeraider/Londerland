<?php
$app->get('/plugins/seasonal/settings', function ($request, $response, $args) {
	$SeasonalThemes = new SeasonalThemes();
	if ($SeasonalThemes->checkRoute($request)) {
		if ($SeasonalThemes->qualifyRequest(1, true)) {
			$GLOBALS['api']['response']['data'] = $SeasonalThemes->_seasonalGetSettings();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
