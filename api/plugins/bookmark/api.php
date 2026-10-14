<?php
/*
 * Bookmarks routes. page and list are for everyone who can open the Bookmarks tab (the list only holds the bookmarks
 * of their group); everything that changes bookmarks or categories is for admins.
 */
function bookmarkJsonResponse($response)
{
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
}

$app->get('/plugins/bookmark/settings', function ($request, $response, $args) {
	$Bookmark = new Bookmark();
	if ($Bookmark->_bookmarkSettingsAccess($request)) {
		$GLOBALS['api']['response']['data'] = $Bookmark->_bookmarkGetSettings();
	}
	return bookmarkJsonResponse($response);
});
$app->post('/plugins/bookmark/setup', function ($request, $response, $args) {
	$Bookmark = new Bookmark();
	if ($Bookmark->_bookmarkAdminAccess($request)) {
		$Bookmark->_bookmarkSetup();
	}
	return bookmarkJsonResponse($response);
});
// The Londerland tab (type Londerland) loads this; main.js fills it
$app->get('/plugins/bookmark/page', function ($request, $response, $args) {
	$Bookmark = new Bookmark();
	if ($Bookmark->_bookmarkReady($request)) {
		$GLOBALS['api']['response']['data'] = '<div class="bookmarks-app"><div class="bookmarks-loading"><i class="fa fa-spinner fa-pulse fa-2x"></i></div></div>';
	}
	return bookmarkJsonResponse($response);
});
$app->get('/plugins/bookmark/list', function ($request, $response, $args) {
	$Bookmark = new Bookmark();
	if ($Bookmark->_bookmarkReady($request)) {
		$GLOBALS['api']['response']['data'] = $Bookmark->_bookmarkList(!empty($request->getQueryParams()['all']));
	}
	return bookmarkJsonResponse($response);
});
$app->post('/plugins/bookmark/bookmarks', function ($request, $response, $args) {
	$Bookmark = new Bookmark();
	if ($Bookmark->_bookmarkAdminAccess($request)) {
		$Bookmark->_bookmarkAdd($Bookmark->apiData($request) ?? []);
	}
	return bookmarkJsonResponse($response);
});
// before {id}, so "order" is not taken for an id
$app->put('/plugins/bookmark/bookmarks/order', function ($request, $response, $args) {
	$Bookmark = new Bookmark();
	if ($Bookmark->_bookmarkAdminAccess($request)) {
		$Bookmark->_bookmarkSort($Bookmark->apiData($request) ?? []);
	}
	return bookmarkJsonResponse($response);
});
$app->put('/plugins/bookmark/bookmarks/{id}', function ($request, $response, $args) {
	$Bookmark = new Bookmark();
	if ($Bookmark->_bookmarkAdminAccess($request)) {
		$Bookmark->_bookmarkUpdate($args['id'], $Bookmark->apiData($request) ?? []);
	}
	return bookmarkJsonResponse($response);
});
$app->delete('/plugins/bookmark/bookmarks/{id}', function ($request, $response, $args) {
	$Bookmark = new Bookmark();
	if ($Bookmark->_bookmarkAdminAccess($request)) {
		$Bookmark->_bookmarkDelete($args['id']);
	}
	return bookmarkJsonResponse($response);
});
$app->post('/plugins/bookmark/categories', function ($request, $response, $args) {
	$Bookmark = new Bookmark();
	if ($Bookmark->_bookmarkAdminAccess($request)) {
		$Bookmark->_bookmarkAddCategory($Bookmark->apiData($request) ?? []);
	}
	return bookmarkJsonResponse($response);
});
$app->put('/plugins/bookmark/categories/order', function ($request, $response, $args) {
	$Bookmark = new Bookmark();
	if ($Bookmark->_bookmarkAdminAccess($request)) {
		$Bookmark->_bookmarkSortCategories($Bookmark->apiData($request) ?? []);
	}
	return bookmarkJsonResponse($response);
});
$app->put('/plugins/bookmark/categories/{id}', function ($request, $response, $args) {
	$Bookmark = new Bookmark();
	if ($Bookmark->_bookmarkAdminAccess($request)) {
		$Bookmark->_bookmarkUpdateCategory($args['id'], $Bookmark->apiData($request) ?? []);
	}
	return bookmarkJsonResponse($response);
});
$app->delete('/plugins/bookmark/categories/{id}', function ($request, $response, $args) {
	$Bookmark = new Bookmark();
	if ($Bookmark->_bookmarkAdminAccess($request)) {
		$Bookmark->_bookmarkDeleteCategory($args['id']);
	}
	return bookmarkJsonResponse($response);
});
// A browser bookmarks file (Netscape format) with the bookmarks this user may see; admins get all of them
$app->get('/plugins/bookmark/export', function ($request, $response, $args) {
	$Bookmark = new Bookmark();
	if (!$Bookmark->_bookmarkReady($request)) {
		return bookmarkJsonResponse($response);
	}
	$response->getBody()->write($Bookmark->_bookmarkExportHtml());
	return $response
		->withHeader('Content-Type', 'text/html;charset=UTF-8')
		->withHeader('Content-Disposition', 'attachment; filename="bookmarks-' . gmdate('Y-m-d') . '.html"')
		->withHeader('X-Content-Type-Options', 'nosniff')
		->withHeader('Content-Security-Policy', "default-src 'none'")
		->withHeader('Cache-Control', 'no-store');
});
$app->post('/plugins/bookmark/import', function ($request, $response, $args) {
	$Bookmark = new Bookmark();
	if ($Bookmark->_bookmarkAdminAccess($request)) {
		$Bookmark->_bookmarkImportHtml($Bookmark->apiData($request) ?? []);
	}
	return bookmarkJsonResponse($response);
});
