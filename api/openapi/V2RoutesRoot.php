<?php

// OpenAPI documentation for the routes in api/v2/routes/root.php (collected by docs/index.php)

namespace Londerland\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Get(
	path: '/api/v2/status',
	summary: 'Query Londerland API to perform a Status Check',
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
final class V2RoutesRoot
{
}
