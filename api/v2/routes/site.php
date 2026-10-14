<?php
/*
 * Public files of Settings > Customize > Website: the web app manifest, robots.txt and sitemap.xml.
 * Apache hands /robots.txt and /sitemap.xml at the root of the site to the API (docker/apache-londerland.conf).
 */
$app->get('/pwa/manifest', function ($request, $response, $args) {
	$Londerland = ($request->getAttribute('Londerland')) ?? new Londerland();
	if (!$Londerland->pwaEnabled()) {
		return $response->withStatus(404);
	}
	$response->getBody()->write(json_encode($Londerland->pwaManifest(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
	return $response
		->withHeader('Content-Type', 'application/manifest+json;charset=UTF-8')
		->withHeader('Cache-Control', 'no-cache');
});
$robotsTxt = function ($request, $response, $args) {
	$Londerland = ($request->getAttribute('Londerland')) ?? new Londerland();
	$response->getBody()->write($Londerland->hasDB() ? $Londerland->seoRobotsTxt() : "User-agent: *\nDisallow: /\n");
	return $response
		->withHeader('Content-Type', 'text/plain;charset=UTF-8')
		->withHeader('Cache-Control', 'public, max-age=3600');
};
$sitemapXml = function ($request, $response, $args) {
	$Londerland = ($request->getAttribute('Londerland')) ?? new Londerland();
	$xml = $Londerland->hasDB() ? $Londerland->seoSitemapXml() : null;
	if ($xml === null) {
		return $response->withStatus(404);
	}
	$response->getBody()->write($xml);
	return $response
		->withHeader('Content-Type', 'application/xml;charset=UTF-8')
		->withHeader('Cache-Control', 'public, max-age=3600');
};
// /robots.txt and /sitemap.xml are the addresses crawlers ask for; Apache sends them to the API unchanged
$app->get('/robots.txt', $robotsTxt);
$app->get('/seo/robots.txt', $robotsTxt);
$app->get('/sitemap.xml', $sitemapXml);
$app->get('/seo/sitemap.xml', $sitemapXml);
