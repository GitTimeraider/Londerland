<?php
$app->get('/plugins/healthchecks/settings', function ($request, $response, $args) {
	$HealthChecks = new HealthChecks();
	if ($HealthChecks->checkRoute($request)) {
		if ($HealthChecks->qualifyRequest(1, true)) {
			$GLOBALS['api']['response']['data'] = $HealthChecks->_healthCheckPluginGetSettings();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->get('/plugins/healthchecks/run', function ($request, $response, $args) {
	$HealthChecks = new HealthChecks();
	if ($HealthChecks->checkRoute($request)) {
		if ($HealthChecks->qualifyRequest($HealthChecks->config['HEALTHCHECKS-Auth-include'], true)) {
			$HealthChecks->_healthCheckPluginRun();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->get('/plugins/healthchecks/import', function ($request, $response, $args) {
	$HealthChecks = new HealthChecks();
	if ($HealthChecks->checkRoute($request)) {
		if ($HealthChecks->qualifyRequest(1, true)) {
			$HealthChecks->_healthCheckPluginImportChecks();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
