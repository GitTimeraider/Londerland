<?php
/*
 * Audit log (Settings > System Settings > Audit Log), admins only.
 */
$app->get('/audit', function ($request, $response, $args) {
	$Londerland = ($request->getAttribute('Londerland')) ?? new Londerland();
	if ($Londerland->qualifyRequest(0, true)) {
		$log = $Londerland->getAuditLog($request->getQueryParams());
		if ($log !== false) {
			$GLOBALS['api']['response']['data'] = $log;
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->get('/audit/export', function ($request, $response, $args) {
	$Londerland = ($request->getAttribute('Londerland')) ?? new Londerland();
	if (!$Londerland->qualifyRequest(0, true)) {
		$response->getBody()->write(jsonE($GLOBALS['api']));
		return $response
			->withHeader('Content-Type', 'application/json;charset=UTF-8')
			->withStatus($GLOBALS['responseCode']);
	}
	$response->getBody()->write($Londerland->auditLogCsv($request->getQueryParams()));
	return $response
		->withHeader('Content-Type', 'text/csv;charset=UTF-8')
		->withHeader('Content-Disposition', 'attachment; filename="londerland-audit-log-' . gmdate('Y-m-d') . '.csv"')
		->withHeader('Cache-Control', 'no-store');
});
$app->delete('/audit', function ($request, $response, $args) {
	$Londerland = ($request->getAttribute('Londerland')) ?? new Londerland();
	if ($Londerland->checkRoute($request) && $Londerland->qualifyRequest(0, true)) {
		$Londerland->clearAuditLog();
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
