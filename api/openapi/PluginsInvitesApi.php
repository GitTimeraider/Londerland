<?php

// OpenAPI documentation for the routes in api/plugins/invites/api.php (collected by docs/index.php)

namespace Organizr\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Tag(
	name: 'plugins-invites',
	description: 'Media Invite Plugin',
)]
#[OA\Schema(
	schema: 'getInvites',
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
								type: 'number',
								example: 1,
								property: 'id',
							),
							new OA\Property(
								type: 'string',
								example: 'NN9JH9',
								property: 'code',
							),
							new OA\Property(
								type: 'string',
								example: '2018-09-01 02:02:24',
								property: 'date',
							),
							new OA\Property(
								type: 'string',
								example: 'causefX@organizr.app',
								property: 'email',
							),
							new OA\Property(
								type: 'string',
								example: 'causefx',
								property: 'username',
							),
							new OA\Property(
								type: 'string',
								example: '2018-09-01 02:02:24',
								property: 'dateused',
							),
							new OA\Property(
								type: 'string',
								example: 'causefx',
								property: 'usedby',
							),
							new OA\Property(
								type: 'string',
								example: '10.0.0.0',
								property: 'ip',
							),
							new OA\Property(
								type: 'string',
								example: 'No',
								property: 'valid',
							),
							new OA\Property(
								type: 'string',
								example: 'Plex',
								property: 'type',
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
	schema: 'createInviteCode',
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
					example: 'Invite Code: XYXYXY has been created',
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
	schema: 'verifyInviteCode',
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
					example: 'Code has been verified',
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
	schema: 'useInviteCode',
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
					example: 'Plex/Emby User now has access to system',
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
	schema: 'deleteInviteCode',
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
					example: 'Code has been deleted',
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
	path: '/api/v2/plugins/invites/settings',
	tags: [
		'plugins-invites',
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
	path: '/api/v2/plugins/invites',
	tags: [
		'plugins-invites',
	],
	summary: 'Get All Invites',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/getInvites',
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
	path: '/api/v2/plugins/invites',
	tags: [
		'plugins-invites',
	],
	summary: 'Create Invite Code',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/createInviteCode',
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
	path: '/api/v2/plugins/invites/{code}',
	tags: [
		'plugins-invites',
	],
	summary: 'Verify Invite Code',
	parameters: [
		new OA\Parameter(
			name: 'code',
			in: 'path',
			description: 'The Invite Code',
			required: true,
			schema: new OA\Schema(
				type: 'integer',
				format: 'int64',
			),
		),
	],
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/verifyInviteCode',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
		),
	],
)]
#[OA\Post(
	path: '/api/v2/plugins/invites/{code}',
	tags: [
		'plugins-invites',
	],
	summary: 'Use Invite Code',
	parameters: [
		new OA\Parameter(
			name: 'code',
			in: 'path',
			description: 'The Invite Code',
			required: true,
			schema: new OA\Schema(
				type: 'integer',
				format: 'int64',
			),
		),
	],
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/useInviteCode',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
		),
	],
)]
#[OA\Delete(
	path: '/api/v2/plugins/invites/{code}',
	tags: [
		'plugins-invites',
	],
	summary: 'Delete Invite Code',
	parameters: [
		new OA\Parameter(
			name: 'code',
			in: 'path',
			description: 'The Invite Code',
			required: true,
			schema: new OA\Schema(
				type: 'integer',
				format: 'int64',
			),
		),
	],
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/deleteInviteCode',
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
final class PluginsInvitesApi
{
}
