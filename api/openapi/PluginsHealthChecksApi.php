<?php

// OpenAPI documentation for the routes in api/plugins/healthChecks/api.php (collected by docs/index.php)

namespace Londerland\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Tag(
	name: 'plugins-healthchecks',
	description: 'Healthchecks.io Ping Plugin',
)]
#[OA\Schema(
	schema: 'healthChecksRun',
	properties: [
		new OA\Property(
			properties: [
				new OA\Property(
					description: 'success or error',
					type: 'string',
					example: 'success',
					property: 'result',
				),
				new OA\Property(
					description: 'success or error message',
					type: 'string',
					example: null,
					property: 'message',
				),
				new OA\Property(
					description: 'data from api',
					type: 'array',
					items: new OA\Items(
						properties: [
							new OA\Property(
								type: 'string',
								example: 'Radarr',
								property: 'Service Name',
							),
							new OA\Property(
								type: 'string',
								example: '883f0097-8f4c-4ca5-a9cf-053cfab8e334',
								property: 'UUID',
							),
							new OA\Property(
								type: 'string',
								example: 'https://radarr.com',
								property: 'External URL',
							),
							new OA\Property(
								type: 'string',
								example: 'http://radarr:7878',
								property: 'Internal URL',
							),
							new OA\Property(
								type: 'string',
								example: 'true',
								property: 'Enabled',
							),
							new OA\Property(
								type: 'array',
								items: new OA\Items(
									properties: [
										new OA\Property(
											type: 'string',
											example: 'Success',
											property: 'internal',
										),
										new OA\Property(
											type: 'string',
											example: 'Success',
											property: 'external',
										),
									],
								),
								property: 'results',
							),
						],
					),
					property: 'data',
				),
			],
			type: 'object',
			property: 'response',
		),
	],
	type: 'object',
)]
#[OA\Get(
	path: '/api/v2/plugins/healthchecks/settings',
	tags: [
		'plugins-healthchecks',
	],
	summary: 'Get settings',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/pluginSettingsPage',
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
	path: '/api/v2/plugins/healthchecks/run',
	tags: [
		'plugins-healthchecks',
	],
	summary: 'Run Healthchecks.io plugin',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/healthChecksRun',
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
final class PluginsHealthChecksApi
{
}
