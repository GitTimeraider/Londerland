<?php

// OpenAPI documentation for the routes in api/v2/routes/update.php (collected by docs/index.php)

namespace Organizr\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Tag(
	name: 'update',
	description: 'Organizr Update',
)]
#[OA\Get(
	path: '/api/v2/update',
	tags: [
		'update',
	],
	summary: 'Update Organizr install using update script',
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
	path: '/api/v2/update/download/{branch}',
	tags: [
		'update',
	],
	summary: 'Download Organizr Update Files',
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
	path: '/api/v2/update/unzip/{branch}',
	tags: [
		'update',
	],
	summary: 'Unzip Organizr Update Files',
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
	path: '/api/v2/update/move/{branch}',
	tags: [
		'update',
	],
	summary: 'Move Organizr Update Files',
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
	path: '/api/v2/update/cleanup/{branch}',
	tags: [
		'update',
	],
	summary: 'Cleanup Organizr Update Files',
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
	path: '/api/v2/update/docker',
	tags: [
		'update',
	],
	summary: 'Update Organizr install using Docker Container script',
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
	path: '/api/v2/update/windows',
	tags: [
		'update',
	],
	summary: 'Update Organizr install using Windows script',
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
	path: '/api/v2/update/linux',
	tags: [
		'update',
	],
	summary: 'Update Organizr install using Linux script',
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
	path: '/api/v2/update/migrate/{version}',
	tags: [
		'update',
	],
	summary: 'Run Organizr Version Mirgation for specific version',
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
	summary: 'Reset an Organizr feature back to default values',
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
