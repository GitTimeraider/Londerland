<?php
require_once '../functions.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\StreamInterface;
use Slim\Factory\AppFactory;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

$GLOBALS['api'] = array(
	'response' => array(
		'result' => 'success',
		'message' => null,
		'data' => null
	)
);
$GLOBALS['bypass'] = array(
	'/api/v2/upgrade',
	'/api/v2/update',
	'/api/v2/force',
	'/api/v2/auth',
	'/api/v2/wizard',
	'/api/v2/login',
	'/api/v2/wizard/path',
	'/api/v2/login/api',
	'/api/v2/plex/register',
	'/api/v2/oidc/providers'
);
$GLOBALS['responseCode'] = 200;
function jsonE($json)
{
	return safe_json_encode($json, JSON_HEX_QUOT | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); // JSON_HEX_TAG
}

function getBasePath()
{
	$uri = $_SERVER['REQUEST_URI'];
	$uriUse = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
	if (stripos($uri, 'api/v2/') !== false) {
		return $uriUse;
	} else {
		return '';
	}
}

function overWriteURI()
{
	$uri = $_SERVER['REQUEST_URI'];
	$query = $_SERVER['QUERY_STRING'];
	if (stripos($query, 'group=') !== false) {
		$group = explode('group=', $query);
		$_SERVER['REQUEST_URI'] = 'auth-' . $group[1];
	}
}

/*
 * Endpoints that visitors who are not logged in may use: the login page and its sign-in methods, reverse proxy auth,
 * robots.txt/sitemap.xml and shared AI chats. Everything else needs a login or the API key (see apiGateAllows).
 */
$GLOBALS['publicRoutes'] = array(
	array('GET', '#^/launch/?$#'),
	array('GET', '#^/page/(login|tabs|error|dependencies)$#'),
	array('GET', '#^/londerland/error(/.*)?$#'),
	array('POST', '#^/login/?$#'),
	array('GET', '#^/logout/?$#'),
	array('GET', '#^/oidc/providers$#'),
	array('GET', '#^/oidc/[^/]+/(authorize|callback)$#'),
	array('POST', '#^/users/(register|recover)$#'),
	// Nginx auth_request, Traefik/Caddy forward auth; these answer 401 themselves when nobody is logged in
	array('ANY', '#^/?(londerland-)?auth([-/].*)?$#'),
	// tab ping dots; the ping itself checks who may use it (Settings > Main > Ping)
	array('GET', '#^/ping/[^/]+$#'),
	array('GET', '#^/(seo/)?(robots\.txt|sitemap\.xml)$#'),
	array('GET', '#^/plugins/aichat/shared/[^/]+$#'),
);

function apiGatePath($request)
{
	$path = $request->getUri()->getPath();
	$base = getBasePath();
	if ($base !== '' && stripos($path, $base) === 0) {
		$path = substr($path, strlen($base));
	}
	return '/' . ltrim($path, '/');
}

// The api/v2 part of a tab's address, e.g. /plugins/bookmark/page, or null when the tab is not a Londerland page
function apiGateTabPath($url)
{
	$path = (string)parse_url((string)$url, PHP_URL_PATH);
	$position = stripos($path, 'api/v2/');
	return $position === false ? null : '/' . substr($path, $position + strlen('api/v2/'));
}

/*
 * Visitors who are not logged in only reach the public routes, plus what the tabs open to guests need:
 * the tab's own page and, for a plugin tab (Bookmarks), the plugin's other GET endpoints. Those check the group themselves.
 */
function apiGateAllows($Londerland, $method, $path)
{
	// before the wizard has run there are no accounts yet
	if (!$Londerland->hasDB()) {
		return true;
	}
	// logged in, or the API key
	if ($Londerland->getUserLevel() < 999) {
		return true;
	}
	foreach ($GLOBALS['publicRoutes'] as $route) {
		if (($route[0] === 'ANY' || $route[0] === $method) && preg_match($route[1], $path)) {
			return true;
		}
	}
	if ($method !== 'GET') {
		return false;
	}
	foreach (($Londerland->getUserTabsAndCategories('tabs') ?: []) as $tab) {
		$tabPath = apiGateTabPath($tab['access_url'] ?? $tab['url'] ?? '');
		if ($tabPath === null) {
			continue;
		}
		if ($tabPath === $path) {
			return true;
		}
		if (preg_match('#^/plugins/[^/]+/#', $tabPath, $plugin) && stripos($path, $plugin[0]) === 0) {
			return true;
		}
	}
	return false;
}

overWriteURI();
// Instantiate App
$app = AppFactory::create();
// Add error middleware; error details (file paths, traces) go to the PHP error log, never to the visitor
$app->addRoutingMiddleware();
$app->addErrorMiddleware(false, true, true);
$app->setBasePath(getBasePath());
$app->add(function ($request, $handler) {
	// add the londerland to your request as [READ-ONLY]
	$Londerland = new Londerland();
	$request = $request->withAttribute('Londerland', $Londerland);
	// set custom error handler
	set_error_handler([$Londerland, 'setAPIErrorResponse']);
	if (!apiGateAllows($Londerland, strtoupper($request->getMethod()), apiGatePath($request))) {
		$response = new \Slim\Psr7\Response();
		$response->getBody()->write(jsonE(array(
			'response' => array(
				'result' => 'error',
				'message' => 'Not Authorized',
				'data' => null
			)
		)));
		return $response
			->withHeader('Content-Type', 'application/json;charset=UTF-8')
			->withStatus(401);
	}
	return $handler->handle($request);
});
//$app->add(new Lowercase());
/*
 * Include all routes
 */
foreach (glob(__DIR__ . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . '*.php') as $filename) {
	require_once $filename;
}
/*
 * Include all custom routes
 */
if (file_exists(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'routes')) {
	foreach (glob(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . '*.php') as $filename) {
		require_once $filename;
	}
}
/*
 * Include all Plugin routes
 */
$folder = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'plugins';
$directoryIterator = new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS);
$iteratorIterator = new RecursiveIteratorIterator($directoryIterator);
foreach ($iteratorIterator as $info) {
	if ($info->getFilename() == 'api.php') {
		require_once $info->getPathname();
	}
}
/*
 * Include all custom Plugin routes
 */
if (file_exists(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'plugins')) {
	$folder = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'plugins';
	$directoryIterator = new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS);
	$iteratorIterator = new RecursiveIteratorIterator($directoryIterator);
	foreach ($iteratorIterator as $info) {
		if ($info->getFilename() == 'api.php') {
			require_once $info->getPathname();
		}
	}
}
/*
 * Include Plugin routes from plugins/ directory (for external git repos)
 */
if (file_exists(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'plugins')) {
	$folder = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'plugins';
	$directoryIterator = new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS);
	$iteratorIterator = new RecursiveIteratorIterator($directoryIterator);
	foreach ($iteratorIterator as $info) {
		if ($info->getFilename() == 'routes.php' || ($info->getFilename() == 'api.php' && strpos($info->getPathname(), '/api/') !== false)) {
			require_once $info->getPathname();
		}
	}
}
/*
 *
 *  This is the last defined api endpoint to catch all undefined endpoints
 *
 */
$app->any('{route:.*}', function ($request, $response) {
	$GLOBALS['api']['response']['data'] = array(
		'endpoint' => $request->getUri()->getPath(),
		'method' => $request->getMethod(),
	);
	$GLOBALS['api']['response']['result'] = 'error';
	$GLOBALS['api']['response']['message'] = 'Endpoint Not Found or Defined';
	$GLOBALS['responseCode'] = 404;
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->run();