<?php
$app->get('/plugins/php-mailer/settings', function ($request, $response, $args) {
	$PhpMailer = new PhpMailer();
	if ($PhpMailer->checkRoute($request)) {
		if ($PhpMailer->qualifyRequest(1, true)) {
			$GLOBALS['api']['response']['data'] = $PhpMailer->_phpMailerPluginGetSettings();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->get('/plugins/php-mailer/email/test', function ($request, $response, $args) {
	$PhpMailer = new PhpMailer();
	if ($PhpMailer->checkRoute($request)) {
		if ($PhpMailer->qualifyRequest(1, true)) {
			$PhpMailer->_phpMailerPluginSendTestEmail();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/plugins/php-mailer/email/send', function ($request, $response, $args) {
	$PhpMailer = new PhpMailer();
	if ($PhpMailer->checkRoute($request)) {
		if ($PhpMailer->qualifyRequest(1, true)) {
			$PhpMailer->_phpMailerPluginAdminSendEmail($PhpMailer->apiData($request));
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->get('/plugins/php-mailer/email/list', function ($request, $response, $args) {
	$PhpMailer = new PhpMailer();
	if ($PhpMailer->checkRoute($request)) {
		if ($PhpMailer->qualifyRequest(1, true)) {
			$GLOBALS['api']['response']['data'] = $PhpMailer->_phpMailerPluginGetEmails();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});