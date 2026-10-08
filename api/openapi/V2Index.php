<?php

// OpenAPI documentation for the routes in api/v2/index.php (collected by docs/index.php)

namespace Londerland\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
	title: 'Londerland API',
	description: 'Londerland - all your web apps in one place',
	version: '2.0',
)]
#[OA\Server(
	url: \API_HOST,
	description: 'This Londerland Install',
)]
#[OA\Server(
	url: '{schema}://{hostPath}',
	description: 'Custom Londerland API',
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
			description: 'Your Londerland URL',
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
