<?php

// OpenAPI documentation for the routes in api/v2/routes/update.php (collected by docs/index.php)

namespace Londerland\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Tag(
	name: 'update',
	description: 'Database and config migrations',
)]
#[OA\Get(
	path: '/api/v2/update/migrate/{version}',
	tags: [
		'update',
	],
	summary: 'Run Londerland Version Mirgation for specific version',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/success-message',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
			content: new OA\JsonContent(
				ref: '#/components/schemas/unauthorized-message',
			),
		),
		new OA\Response(
			response: '404',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
		new OA\Response(
			response: '422',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
		new OA\Response(
			response: '500',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
#[OA\Get(
	path: '/api/v2/update/reset/{feature}',
	tags: [
		'update',
	],
	summary: 'Reset an Londerland feature back to default values',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/success-message',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
			content: new OA\JsonContent(
				ref: '#/components/schemas/unauthorized-message',
			),
		),
		new OA\Response(
			response: '404',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
		new OA\Response(
			response: '422',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
		new OA\Response(
			response: '500',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
final class V2RoutesUpdate
{
}
