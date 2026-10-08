<?php

// OpenAPI documentation for the routes in api/plugins/speedTest/api.php (collected by docs/index.php)

namespace Londerland\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Tag(
	name: 'plugins-speedtest',
	description: 'SpeedTest Plugin',
)]
#[OA\Get(
	path: '/api/v2/plugins/speedtest/settings',
	tags: [
		'plugins-speedtest',
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
final class PluginsSpeedTestApi
{
}
