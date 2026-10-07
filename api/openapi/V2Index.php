<?php

// OpenAPI documentation for the routes in api/v2/index.php (collected by docs/index.php)

namespace Organizr\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
	title: 'Organizr API',
	description: 'Organizr - Accept no others',
	version: '2.0',
)]
#[OA\Server(
	url: \API_HOST,
	description: 'This Organizr Install',
)]
#[OA\Server(
	url: 'https://demo.organizr.app',
	description: 'Organizr Demo API',
)]
#[OA\Server(
	url: '{schema}://{hostPath}',
	description: 'Custom Organizr API',
	variables: [
		new OA\ServerVariable(
			serverVariable: 'schema',
			enum: [
				'https',
				'http',
			],
			default: 'http',
		),
		new OA\ServerVariable(
			serverVariable: 'hostPath',
			default: 'localhost',
			description: 'Your Organizr URL',
		),
	],
)]
#[OA\SecurityScheme(
	securityScheme: 'api_key',
	type: 'apiKey',
	name: 'Token',
	in: 'header',
)]
final class V2Index
{
}
