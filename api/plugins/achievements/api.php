<?php
/*
 * Achievements routes. track and stats need a logged in (non-guest) user in a group that collects badges
 * and only touch that user's own row; settings and reset are for admins.
 */
function achievementsJsonResponse($response)
{
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
}

$app->get('/plugins/achievements/settings', function ($request, $response, $args) {
	$Achievements = new Achievements();
	if ($Achievements->_achAdminAccess($request)) {
		$GLOBALS['api']['response']['data'] = $Achievements->_achGetSettings();
	}
	return achievementsJsonResponse($response);
});
$app->post('/plugins/achievements/track', function ($request, $response, $args) {
	$Achievements = new Achievements();
	if ($Achievements->_achAccess($request)) {
		$Achievements->_achTrack($Achievements->apiData($request) ?? []);
	}
	return achievementsJsonResponse($response);
});
$app->get('/plugins/achievements/stats', function ($request, $response, $args) {
	$Achievements = new Achievements();
	if ($Achievements->_achAccess($request)) {
		$Achievements->_achMyStats();
	}
	return achievementsJsonResponse($response);
});
$app->delete('/plugins/achievements/reset', function ($request, $response, $args) {
	$Achievements = new Achievements();
	if ($Achievements->_achAdminAccess($request)) {
		$Achievements->_achResetAll();
	}
	return achievementsJsonResponse($response);
});
