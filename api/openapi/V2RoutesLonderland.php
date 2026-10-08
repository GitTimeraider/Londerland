<?php

// OpenAPI documentation for the routes in api/v2/routes/londerland.php (collected by docs/index.php)

namespace Londerland\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Get(
	path: '/api/v2/londerland/{page}',
	tags: [
		'page',
	],
	summary: 'Get HTML for Londerland Pages',
	parameters: [
		new OA\Parameter(
			name: 'page',
			in: 'path',
			description: 'Page to get',
			required: true,
			schema: new OA\Schema(
				type: 'string',
			),
		),
	],
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/get-html',
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
final class V2RoutesLonderland
{
}
