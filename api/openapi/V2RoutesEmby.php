<?php

// OpenAPI documentation for the routes in api/v2/routes/emby.php (collected by docs/index.php)

namespace Organizr\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Tag(
	name: 'emby',
)]
#[OA\Post(
	path: '/api/v2/emby/register',
	tags: [
		'emby',
	],
	summary: 'Register a user using Emby API',
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
final class V2RoutesEmby
{
}
