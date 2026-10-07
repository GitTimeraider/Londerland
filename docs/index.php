<?php
require_once '../api/functions.php';
define("API_HOST", getServerPath(false) . '');
// API documentation lives as PHP attributes on the classes in api/openapi
$files = glob(dirname(__DIR__) . '/api/openapi/*.php');
foreach ($files as $file) {
	require_once $file;
}
$openapi = (new \OpenApi\Generator())
	// Operations are documented on placeholder classes, so don't derive operationIds from them
	->withProcessorPipeline(fn($pipeline) => $pipeline->remove(\OpenApi\Processors\OperationId::class))
	->generate($files, null, false);
ob_start();
header('Content-Type: application/json');
$json = $openapi->toJson();
echo $json;
//  Return the contents of the output buffer
$htmlStr = ob_get_contents();
// Clean (erase) the output buffer and turn off output buffering
ob_end_clean();
// Write final string to file
file_put_contents('./api.json', $htmlStr);
header("Location: home/");
echo $json;
