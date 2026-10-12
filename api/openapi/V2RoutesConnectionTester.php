<?php

// OpenAPI documentation for the routes in api/v2/routes/connectionTester.php (collected by docs/index.php)

namespace Londerland\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Tag(
	name: 'test connection',
	description: 'Test Connections',
)]
#[OA\Post(
	path: '/api/v2/test/ldap',
	tags: [
		'test connection',
	],
	summary: 'Test LDAP connection',
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
			response: '409',
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
#[OA\Post(
	path: '/api/v2/test/ldap/login',
	tags: [
		'test connection',
	],
	summary: 'Test LDAP connection using account login',
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
			response: '409',
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
#[OA\Post(
	path: '/api/v2/test/iframe',
	tags: [
		'test connection',
	],
	summary: 'Test if URL can be iFramed',
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
			response: '409',
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
#[OA\Post(
	path: '/api/v2/test/path',
	tags: [
		'test connection',
	],
	summary: 'Test if path has correct permissions',
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
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
#[OA\Post(
	path: '/api/v2/test/cron',
	tags: [
		'test connection',
	],
	summary: 'Test cron schedule',
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
	path: '/api/v2/test/cron',
	tags: [
		'test connection',
	],
	summary: 'Test if cron is setup correctly',
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
#[OA\Post(
	path: '/api/v2/test/folder',
	tags: [
		'test connection',
	],
	summary: 'Test folder path',
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
#[OA\Post(
	path: '/api/v2/test/database',
	tags: [
		'test connection',
	],
	summary: 'Test Database connection',
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
#[OA\Post(
	path: '/api/v2/test/slack-logs',
	tags: [
		'test connection',
	],
	summary: 'Test connection to Slack/Discord',
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

final class V2RoutesConnectionTester
{
}
