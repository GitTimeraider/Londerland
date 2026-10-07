<?php

// OpenAPI documentation for the routes in api/plugins/chat/api.php (collected by docs/index.php)

namespace Organizr\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Tag(
	name: 'plugins-chat',
	description: 'Pusher Chat Plugin',
)]
#[OA\Schema(
	schema: 'getChatMessages',
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
								example: 'causefx',
								property: 'username',
							),
							new OA\Property(
								type: 'string',
								example: '2018-09-01 02:02:24',
								property: 'date',
							),
							new OA\Property(
								type: 'string',
								example: 'https://www.gravatar.com/avatar/a47c4a4b915ddf9601cd228f890bc366?s=100&d=mm',
								property: 'gravatar',
							),
							new OA\Property(
								type: 'string',
								example: 'ok first message!',
								property: 'message',
							),
							new OA\Property(
								type: 'string',
								example: 'f5287',
								property: 'uid',
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
	schema: 'submitMessageData',
	properties: [
		new OA\Property(
			type: 'string',
			example: 'This is my message',
			property: 'message',
		),
	],
	type: 'object',
)]
#[OA\Schema(
	schema: 'submitMessage',
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
					example: 'message has been accepted',
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
#[OA\Get(
	path: '/api/v2/plugins/chat/settings',
	tags: [
		'plugins-chat',
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
	path: '/api/v2/plugins/chat/message',
	tags: [
		'plugins-chat',
	],
	summary: 'Get all messages',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/getChatMessages',
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
#[OA\Post(
	path: '/api/v2/plugins/chat/message',
	tags: [
		'plugins-chat',
	],
	summary: 'Submit a message',
	requestBody: new OA\RequestBody(
		description: 'Success',
		required: true,
		content: [
			new OA\MediaType(
				mediaType: 'application/x-www-form-urlencoded',
				schema: new OA\Schema(
					properties: [
						new OA\Property(
							description: 'message to send',
							type: 'string',
							property: 'message',
						),
					],
					type: 'object',
				),
			),
			new OA\JsonContent(
				ref: '#/components/schemas/submitMessageData',
			),
		],
	),
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/submitMessage',
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
final class PluginsChatApi
{
}
