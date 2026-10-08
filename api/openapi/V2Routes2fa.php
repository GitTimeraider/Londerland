<?php

// OpenAPI documentation for the routes in api/v2/routes/2fa.php (collected by docs/index.php)

namespace Londerland\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Tag(
	name: '2fa',
	description: 'Two Form Authentication',
)]
#[OA\Schema(
	schema: 'submit-2fa-verify',
	properties: [
		new OA\Property(
			type: 'string',
			example: 'OX1R4GA3425GSDF',
			property: 'secret',
		),
		new OA\Property(
			type: 'string',
			example: '145047',
			property: 'code',
		),
		new OA\Property(
			type: 'string',
			example: 'google',
			property: 'type',
		),
	],
	type: 'object',
)]
#[OA\Schema(
	schema: 'submit-2fa-save',
	properties: [
		new OA\Property(
			type: 'string',
			example: 'OX1R4GA3425GSDF',
			property: 'secret',
		),
		new OA\Property(
			type: 'string',
			example: 'google',
			property: 'type',
		),
	],
	type: 'object',
)]
#[OA\Schema(
	schema: 'submit-2fa-create',
	properties: [
		new OA\Property(
			type: 'string',
			example: 'google',
			property: 'type',
		),
	],
	type: 'object',
)]
#[OA\Post(
	path: '/api/v2/2fa',
	tags: [
		'2fa',
	],
	summary: 'Verify 2FA code',
	requestBody: new OA\RequestBody(
		description: 'Success',
		required: true,
		content: new OA\JsonContent(
			ref: '#/components/schemas/submit-2fa-verify',
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
#[OA\Put(
	path: '/api/v2/2fa',
	tags: [
		'2fa',
	],
	summary: 'Save 2FA code',
	requestBody: new OA\RequestBody(
		description: 'Success',
		required: true,
		content: new OA\JsonContent(
			ref: '#/components/schemas/submit-2fa-save',
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
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
#[OA\Post(
	path: '/api/v2/2fa/{type}',
	tags: [
		'2fa',
	],
	summary: 'Create 2FA code',
	parameters: [
		new OA\Parameter(
			name: 'type',
			in: 'path',
			description: 'The type of 2FA',
			required: true,
			schema: new OA\Schema(
				type: 'string',
			),
			example: 'google',
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
	],
)]
#[OA\Delete(
	path: '/api/v2/2fa',
	tags: [
		'2fa',
	],
	summary: 'Delete 2FA code',
	responses: [
		new OA\Response(
			response: '204',
			description: 'Success',
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
			content: new OA\JsonContent(
				ref: '#/components/schemas/unauthorized-message',
			),
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
final class V2Routes2fa
{
}
