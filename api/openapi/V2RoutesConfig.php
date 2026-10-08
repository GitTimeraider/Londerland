<?php

// OpenAPI documentation for the routes in api/v2/routes/config.php (collected by docs/index.php)

namespace Londerland\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Tag(
	name: 'config',
	description: 'Londerland Configuration Items',
)]
#[OA\Get(
	path: '/api/v2/config',
	tags: [
		'config',
	],
	summary: 'Get Londerland Coniguration Items',
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
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
#[OA\Get(
	path: '/api/v2/config/{item}',
	tags: [
		'config',
	],
	summary: 'Get Londerland Coniguration Item',
	parameters: [
		new OA\Parameter(
			name: 'item',
			in: 'path',
			description: 'The key of the item you want to grab',
			required: true,
			schema: new OA\Schema(
				type: 'string',
			),
			example: 'configVersion',
		),
	],
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
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
#[OA\Get(
	path: '/api/v2/config/search/{term}',
	tags: [
		'config',
	],
	summary: 'Search Londerland Coniguration Items',
	parameters: [
		new OA\Parameter(
			name: 'term',
			in: 'path',
			description: 'The term of the items you want to grab',
			required: true,
			schema: new OA\Schema(
				type: 'string',
			),
			example: 'version',
		),
	],
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
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
#[OA\Put(
	path: '/api/v2/config',
	tags: [
		'config',
	],
	summary: 'Update Londerland Coniguration Item(s)',
	requestBody: new OA\RequestBody(
		description: 'Success',
		required: true,
		content: new OA\JsonContent(
			ref: '#/components/schemas/config-items-example',
		),
	),
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
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
final class V2RoutesConfig
{
}
