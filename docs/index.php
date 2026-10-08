<?php
require_once '../api/functions.php';
define("API_HOST", getServerPath(false) . '');
if (!isset($_GET['spec'])) {
	header("Location: home/");
	exit;
}
// API documentation lives as PHP attributes on the classes in api/openapi
$files = glob(dirname(__DIR__) . '/api/openapi/*.php');
foreach ($files as $file) {
	require_once $file;
}
$openapi = (new \OpenApi\Generator())
	// Operations are documented on placeholder classes, so don't derive operationIds from them
	->withProcessorPipeline(fn($pipeline) => $pipeline->remove(\OpenApi\Processors\OperationId::class))
	->generate($files, null, false);
// Generated on request, so nothing has to be written to the (possibly read-only) app folder
header('Content-Type: application/json');
echo $openapi->toJson();
