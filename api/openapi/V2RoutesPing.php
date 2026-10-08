<?php

// OpenAPI documentation for the routes in api/v2/routes/ping.php (collected by docs/index.php)

namespace Londerland\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Get(
	path: '/api/v2/ping',
	summary: 'Ping the Londerland API',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/ping',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
		),
	],
)]
final class V2RoutesPing
{
}
