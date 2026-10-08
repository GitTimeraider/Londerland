<?php

// Shared OpenAPI schemas (collected by docs/index.php)

namespace Londerland\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
	schema: 'ping',
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
					type: 'string',
					example: 'pong',
					property: 'data',
				),
			],
			type: 'object',
			property: 'response',
		),
	],
	type: 'object',
)]
#[OA\Schema(
	schema: 'status',
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
					properties: [
						new OA\Property(
							description: 'success or error',
							type: 'string',
							example: 'ok',
							property: 'status',
						),
						new OA\Property(
							type: 'string',
							example: '2.0',
							property: 'api_version',
						),
						new OA\Property(
							type: 'string',
							example: '2.0.650',
							property: 'londerland_version',
						),
					],
					type: 'object',
					property: 'data',
				),
			],
			type: 'object',
			property: 'response',
		),
	],
	type: 'object',
)]
#[OA\Schema(
	schema: 'pluginSettingsPage',
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
					properties: [
						new OA\Property(
							type: 'object',
							property: 'settingsPageObjectItem1',
						),
						new OA\Property(
							type: 'object',
							property: 'settingsPageObjectItem2',
						),
						new OA\Property(
							type: 'object',
							property: 'settingsPageObjectItem3',
						),
					],
					type: 'object',
					property: 'data',
				),
			],
			type: 'object',
			property: 'response',
		),
	],
	type: 'object',
)]
#[OA\Schema(
	schema: 'successNullData',
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
					description: 'success message or error message',
					type: 'string',
					example: null,
					property: 'message',
				),
				new OA\Property(
					description: 'data from api',
					type: 'string',
					example: null,
					property: 'data',
				),
			],
			type: 'object',
			property: 'response',
		),
	],
	type: 'object',
)]
#[OA\Schema(
	schema: 'success-message',
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
					description: 'success message or error message',
					type: 'string',
					example: 'Successful message here',
					property: 'message',
				),
				new OA\Property(
					description: 'data from api',
					type: 'string',
					example: null,
					property: 'data',
				),
			],
			type: 'object',
			property: 'response',
		),
	],
	type: 'object',
)]
#[OA\Schema(
	schema: 'error-message',
	properties: [
		new OA\Property(
			properties: [
				new OA\Property(
					description: 'success or error',
					type: 'string',
					example: 'error',
					property: 'result',
				),
				new OA\Property(
					description: 'success message or error message',
					type: 'string',
					example: 'Error message here',
					property: 'message',
				),
				new OA\Property(
					description: 'data from api',
					type: 'string',
					example: null,
					property: 'data',
				),
			],
			type: 'object',
			property: 'response',
		),
	],
	type: 'object',
)]
#[OA\Schema(
	schema: 'unauthorized-message',
	properties: [
		new OA\Property(
			properties: [
				new OA\Property(
					description: 'success or error',
					type: 'string',
					example: 'error',
					property: 'result',
				),
				new OA\Property(
					description: 'success message or error message',
					type: 'string',
					example: 'User is not authorized',
					property: 'message',
				),
				new OA\Property(
					description: 'data from api',
					type: 'string',
					example: null,
					property: 'data',
				),
			],
			type: 'object',
			property: 'response',
		),
	],
	type: 'object',
)]
#[OA\Schema(
	schema: 'php-mailer-email-list',
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
								example: 'user@example.com',
								property: 'username',
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
#[OA\Schema(
	schema: 'config-items-example',
	description: 'list of config items to update',
	properties: [
		new OA\Property(
			description: 'config item name',
			type: 'string',
			example: 'My Dashboard',
			property: 'title',
		),
		new OA\Property(
			type: 'boolean',
			example: false,
			property: 'hideRegistration',
		),
		new OA\Property(
			type: 'string',
			example: '1',
			property: 'homepageUnifiAuth',
		),
	],
	type: 'object',
)]
final class V2Schemas
{
}
