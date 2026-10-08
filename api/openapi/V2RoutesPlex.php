<?php

// OpenAPI documentation for the routes in api/v2/routes/plex.php (collected by docs/index.php)

namespace Londerland\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Tag(
	name: 'plex',
)]
#[OA\Schema(
	schema: 'plexRegister',
	properties: [
		new OA\Property(
			type: 'string',
			example: 'username',
			property: 'username',
		),
		new OA\Property(
			type: 'string',
			example: 'user@example.com',
			property: 'email',
		),
		new OA\Property(
			type: 'string',
			example: 'iCanHazPa$$w0Rd',
			property: 'password',
		),
	],
	type: 'object',
)]
#[OA\Post(
	path: '/api/v2/plex/register',
	tags: [
		'plex',
	],
	summary: 'Register a user using Plex API',
	requestBody: new OA\RequestBody(
		description: 'Success',
		required: true,
		content: new OA\JsonContent(
			ref: '#/components/schemas/plexRegister',
		),
	),
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/status',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
		),
	],
)]
final class V2RoutesPlex
{
}
