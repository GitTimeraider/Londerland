<?php
/*
 * Public files of Settings > Customize > Website: robots.txt, sitemap.xml and security.txt.
 * Apache hands /robots.txt, /sitemap.xml, /security.txt and /.well-known/security.txt at the root of the site to the
 * API (docker/apache-londerland.conf).
 */
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
$securityTxt = function ($request, $response, $args) {
	$Londerland = ($request->getAttribute('Londerland')) ?? new Londerland();
	$text = $Londerland->hasDB() ? $Londerland->seoSecurityTxt() : null;
	if ($text === null) {
		return $response->withStatus(404);
	}
	$response->getBody()->write($text);
	return $response
		->withHeader('Content-Type', 'text/plain;charset=UTF-8')
		->withHeader('Cache-Control', 'public, max-age=3600');
};
// /robots.txt and /sitemap.xml are the addresses crawlers ask for; Apache sends them to the API unchanged
$app->get('/robots.txt', $robotsTxt);
$app->get('/seo/robots.txt', $robotsTxt);
$app->get('/sitemap.xml', $sitemapXml);
$app->get('/seo/sitemap.xml', $sitemapXml);
// RFC 9116: /.well-known/security.txt is the address to use, /security.txt the older one some tools still ask for
$app->get('/.well-known/security.txt', $securityTxt);
$app->get('/security.txt', $securityTxt);
$app->get('/seo/security.txt', $securityTxt);
