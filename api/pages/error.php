<?php
$GLOBALS['londerlandPages'][] = 'error';
function get_page_error($Londerland)
{
	if (!$Londerland) {
		$Londerland = new Londerland();
	}
	if ((!$Londerland->hasDB())) {
		return false;
	}
	$nonRoot = isset($_GET['londerland']);
	$nonRootPath = ($nonRoot) ? $Londerland->getRootPath() : '';
	$error = $_GET['vars']['var1'] ?? 404;
	$errorDetails = $Londerland->errorCodes($error);
	$redirect = $_GET['vars']['var2'] ?? null;
	if ($redirect) {
		$Londerland->logger->debug($redirect);
	}
	$GLOBALS['responseCode'] = 200;
	return '
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
	<meta charset="utf-8">
	<meta content="IE=edge" http-equiv="X-UA-Compatible">
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<meta content="' . $Londerland->config['description'] . '" name="description">
	<meta content="Londerland" name="author">
	' . $Londerland->favIcons($nonRootPath) . '
	<title>Error ' . $Londerland->config['title'] . '</title>
	' . $Londerland->loadResources(
			[
				'assets/vendor/bootstrap/bootstrap.min.css',
				'assets/vendor/fontawesome/css/all.min.css',
				'css/dark.min.css',
				'css/londerland.min.css',
				'assets/vendor/jquery/jquery.min.js',
				'js/i18n.js'
			], $nonRootPath
		) . '
	' . $Londerland->setTheme(null, $nonRootPath) . '
	<style id="user-appearance"></style>
	<style id="custom-theme-css"></style>
	<style id="custom-css"></style>
</head>
<body class="fix-header">
<!-- ============================================================== -->
<!-- Preloader -->
<!-- ==============================================================
<div id="preloader" class="preloader">
	<svg class="circular" viewbox="25 25 50 50">
		<circle class="path" cx="50" cy="50" fill="none" r="20" stroke-miterlimit="10" stroke-width="10"></circle>
	</svg>
</div>-->
<!-- ============================================================== -->
<!-- Wrapper -->
<!-- ============================================================== -->
<section id="wrapper">
	<div class="error-box">
		<div class="error-body text-center">
			<h1 class="text-danger">' . $error . '</h1>
			<h2 class="text-uppercase" lang="en">' . $errorDetails['type'] . '</h2>
			<h3 class="text-uppercase" lang="en">' . $errorDetails['description'] . '</h3>
			<p class="text-muted my-4">Hey there, ' . $Londerland->user['username'] . ', ' . $Londerland->config['customErrorMessage'] . ' . </p>
			<a href="' . $nonRootPath . '" class="btn btn-danger rounded-pill mb-5">Back Home</a>
		</div>
	</div>
</section>
<script>
$.urlParam = function(name){
	let results = new RegExp("[\?&]" + name + "=([^&#]*)").exec(window.location.href);
	if (results == null) {
		return null;
	} else {
		return decodeURI(results[1]) || 0;
	}
};
if ($.urlParam("return") !== null && "' . $Londerland->user['groupID'] . '" === "999") {
	local("set", "uri", $.urlParam("return"));
}
function localStorageSupport() {
	return (("localStorage" in window) && window["localStorage"] !== null)
}
function local(type,key,value=null){
	if (localStorageSupport) {
		switch (type) {
			case "set":
			case "s":
				localStorage.setItem(key,value);
				break;
			case "get":
			case "g":
				return localStorage.getItem(key);
				break;
			case "remove":
			case "r":
				localStorage.removeItem(key);
				break;
		}
	}
}
function getCookie(cname) {
	var name = cname + "=";
	var decodedCookie = decodeURIComponent(document.cookie);
	var ca = decodedCookie.split(";");
	for(var i = 0; i <ca.length; i++) {
		var c = ca[i];
		while (c.charAt(0) == " ") {
			c = c.substring(1);
		}
		if (c.indexOf(name) == 0) {
			return c.substring(name.length, c.length);
		}
	}
	return "";
}
</script>
</body>
</html>
';
}